<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\LabRequest;
use App\Models\LabRequestTemplate;
use App\Models\LabTestResult;
use App\Models\LabTestTemplate;
use App\Models\LabTestParameter;
use App\Models\LabReferenceRange;
use App\Models\Notification;
use App\Services\LabPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabResultsController extends Controller
{
    use ResolvesUserBranch;

    protected $labPdfService;

    public function __construct(LabPdfService $labPdfService)
    {
        $this->labPdfService = $labPdfService;
    }

    /**
     * Show form for entering test results (all tests in this request, one form).
     */
    public function enterResults(LabRequest $labRequest)
    {
        $this->assertLabRequestAccess($labRequest);

        $labRequest->load([
            'patient',
            'doctor',
            'template.parameters.referenceRanges',
            'testType.template.parameters.referenceRanges',
            'templates.parameters.referenceRanges',
            'results'
        ]);
        
        $templatesToShow = collect();
        
        if ($labRequest->templates && $labRequest->templates->count() > 0) {
            $templatesToShow = $labRequest->templates;
        } else {
            $template = null;
            if ($labRequest->template) {
                $template = $labRequest->template;
            } elseif ($labRequest->testType && $labRequest->testType->template) {
                $template = $labRequest->testType->template;
            } elseif ($labRequest->test_type || ($labRequest->testType && $labRequest->testType->test_name)) {
                $testName = $labRequest->test_type ?: $labRequest->testType->test_name;
                $template = LabTestTemplate::where('template_name', $testName)
                    ->orWhere('template_code', 'LIKE', '%' . substr($testName, 0, 4) . '%')
                    ->first();
            }
            if ($template) {
                if (!$labRequest->template_id) {
                    $labRequest->update(['template_id' => $template->id]);
                    $labRequest->addTemplates([$template->id]);
                }
                $templatesToShow = collect([$template]);
            }
        }
        
        if ($templatesToShow->isEmpty()) {
            return redirect()->route('lab.index')
                ->with('error', 'No template assigned to this lab request. Please assign a template first.');
        }
        
        $labRequest->refresh();
        $labRequest->load(['template.parameters.referenceRanges', 'templates.parameters.referenceRanges']);
        if ($templatesToShow->count() === 1 && !$labRequest->template_id) {
            $labRequest->update(['template_id' => $templatesToShow->first()->id]);
        }
        
        return view('lab.results.enter', compact('labRequest', 'templatesToShow'));
    }

    /**
     * Store test results (supports single or multiple templates in one request).
     */
    public function storeResults(Request $request, LabRequest $labRequest)
    {
        $this->assertLabRequestAccess($labRequest);

        $rawResults = $request->input('results', []);
        $resultsByTemplate = [];
        
        if (empty($rawResults)) {
            return redirect()->back()->withInput()->with('error', 'No results submitted.');
        }
        
        if (isset($rawResults[0]) && isset($rawResults[0]['parameter_id'])) {
            $template = $labRequest->template ?? $labRequest->templates->first();
            if (!$template) {
                return redirect()->route('lab.index')->with('error', 'No template assigned to this lab request');
            }
            $resultsByTemplate[(string) $template->id] = $rawResults;
        } else {
            foreach ($rawResults as $templateId => $rows) {
                if (!is_array($rows)) {
                    continue;
                }
                $resultsByTemplate[(string) $templateId] = array_values($rows);
            }
        }
        
        if (empty($resultsByTemplate)) {
            return redirect()->back()->withInput()->with('error', 'No results to save.');
        }
        
        $validated = $request->validate([
            'methodology_used' => 'nullable|string',
            'equipment_used' => 'nullable|string',
            'reagent_lot_number' => 'nullable|string',
            'reagent_expiry_date' => 'nullable|date',
            'technician_notes' => 'nullable|string'
        ]);
        
        DB::beginTransaction();
        try {
            foreach ($resultsByTemplate as $templateId => $rows) {
                $template = LabTestTemplate::find($templateId);
                if (!$template) {
                    continue;
                }
                foreach ($rows as $resultData) {
                    if (empty($resultData['parameter_id'] ?? null)) {
                        continue;
                    }
                    $parameter = LabTestParameter::find($resultData['parameter_id']);
                    if (!$parameter) {
                        continue;
                    }
                
                // Get patient info for reference range matching
                $patient = $labRequest->patient;
                $gender = $patient->gender ?? 'Both';
                $age = $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : null;
                
                // Determine age group
                $ageGroup = $this->determineAgeGroup($age);
                
                // Get appropriate reference range
                $referenceRange = $parameter->referenceRanges()
                    ->where(function($query) use ($gender) {
                        $query->where('gender', $gender)
                              ->orWhere('gender', 'Both');
                    })
                    ->where('age_group', $ageGroup)
                    ->where('is_active', true)
                    ->first();
                
                // Determine result status and abnormal flag
                $resultStatus = 'normal';
                $abnormalFlag = null;
                
                // Handle qualitative results (text-based)
                if ($parameter->data_type === 'text' || $template->template_type === 'qualitative') {
                    $resultValue = strtolower(trim($resultData['result_value']));
                    
                    // Values that indicate abnormal/positive results
                    $abnormalValues = ['positive', 'pos', 'present', 'detected', 'reactive', 'abnormal', 'yes', 'y', 'true', '1'];
                    // Values that indicate normal/negative results
                    $normalValues = ['negative', 'neg', 'absent', 'not detected', 'non-reactive', 'normal', 'no', 'n', 'false', '0'];
                    
                    if (in_array($resultValue, $abnormalValues)) {
                        $resultStatus = 'abnormal';
                        $abnormalFlag = 'POS';
                    } elseif (in_array($resultValue, $normalValues)) {
                        $resultStatus = 'normal';
                        $abnormalFlag = null;
                    } else {
                        // Check reference range if available for qualitative tests
                        if ($referenceRange) {
                            // If reference range specifies what's normal, check against it
                            $normalRange = strtolower($referenceRange->getFormattedRange() ?? '');
                            if (strpos($normalRange, 'negative') !== false || strpos($normalRange, 'normal') !== false) {
                                if (!in_array($resultValue, $normalValues)) {
                                    $resultStatus = 'abnormal';
                                    $abnormalFlag = 'POS';
                                }
                            }
                        }
                    }
                }
                // Handle numeric/quantitative results
                elseif ($parameter->data_type === 'numeric' && $referenceRange) {
                    $numericValue = floatval($resultData['result_value']);
                    
                    if (!$referenceRange->isValueWithinRange($numericValue)) {
                        $resultStatus = 'abnormal';
                        
                        // Determine if high or low
                        if ($referenceRange->min_value && $numericValue < $referenceRange->min_value) {
                            $abnormalFlag = 'L';
                        } elseif ($referenceRange->max_value && $numericValue > $referenceRange->max_value) {
                            $abnormalFlag = 'H';
                        }
                        
                        // Check for critical values
                        $criticalValue = $parameter->criticalValues()
                            ->where('age_group', $ageGroup)
                            ->where(function($query) use ($gender) {
                                $query->where('gender', $gender)
                                      ->orWhere('gender', 'Both');
                            })
                            ->where('is_active', true)
                            ->first();
                        
                        if ($criticalValue) {
                            if (($criticalValue->critical_low && $numericValue <= $criticalValue->critical_low) ||
                                ($criticalValue->critical_high && $numericValue >= $criticalValue->critical_high)) {
                                $resultStatus = 'critical';
                                $abnormalFlag = 'CRITICAL';
                            }
                            
                            if (($criticalValue->panic_low && $numericValue <= $criticalValue->panic_low) ||
                                ($criticalValue->panic_high && $numericValue >= $criticalValue->panic_high)) {
                                $resultStatus = 'critical';
                                $abnormalFlag = 'PANIC';
                            }
                        }
                    }
                }
                
                    // Create or update test result (include template_id so multi-template requests don't overwrite)
                    LabTestResult::updateOrCreate(
                        [
                            'lab_request_id' => $labRequest->id,
                            'parameter_id' => $parameter->id,
                            'template_id' => $template->id
                        ],
                        [
                            'template_id' => $template->id,
                            'parameter_code' => $parameter->parameter_code,
                            'parameter_name' => $parameter->parameter_name,
                            'result_value' => $resultData['result_value'],
                            'formatted_value' => $this->formatResultValue($resultData['result_value'], $parameter),
                            'unit' => $parameter->unit,
                            'reference_range' => $referenceRange ? $referenceRange->getFormattedRange() : null,
                            'age_group' => $ageGroup,
                            'gender' => $gender,
                            'result_status' => $resultStatus,
                            'abnormal_flag' => $abnormalFlag,
                            'clinical_interpretation' => $resultData['clinical_interpretation'] ?? null,
                            'technical_notes' => $resultData['technical_notes'] ?? null,
                            'methodology_used' => $validated['methodology_used'] ?? $template->methodology,
                            'equipment_used' => $validated['equipment_used'] ?? $template->equipment_required,
                            'reagent_lot_number' => $validated['reagent_lot_number'] ?? null,
                            'reagent_expiry_date' => $validated['reagent_expiry_date'] ?? null,
                            'technician_notes' => $validated['technician_notes'] ?? null,
                            'test_performed_at' => now(),
                            'result_entered_at' => now(),
                            'performed_by' => auth()->id()
                        ]
                    );
                }
                // Mark this template assignment as completed so updateTemplateCompletion() counts it
                $assignment = LabRequestTemplate::where('lab_request_id', $labRequest->id)
                    ->where('template_id', $templateId)
                    ->first();
                if ($assignment) {
                    $assignment->markAsCompleted();
                }
            }
            
            $labRequest->update(['technician_id' => auth()->id()]);
            $wasNotCompleted = $labRequest->status !== 'completed';
            $labRequest->updateTemplateCompletion();
            if ($labRequest->isFullyCompleted()) {
                $labRequest->update(['completed_at' => now(), 'status' => 'completed']);
            }

            if ($wasNotCompleted && $labRequest->fresh()->status === 'completed') {
                $this->notifyOrderingDoctor($labRequest->fresh(['patient', 'doctor']));
            }
            
            DB::commit();

            $redirect = redirect()->route('lab.show', $labRequest)
                ->with('success', 'Test results entered successfully!');

            if (session('inventory_warning')) {
                $redirect->with('warning', session('inventory_warning'));
            }

            return $redirect;
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error entering results: ' . $e->getMessage());
        }
    }

    /**
     * Verify test results
     */
    public function verifyResults(LabRequest $labRequest)
    {
        $this->assertLabRequestAccess($labRequest);

        DB::beginTransaction();
        try {
            $labRequest->results()->update([
                'verified_by' => auth()->id(),
                'result_verified_at' => now()
            ]);
            
            DB::commit();
            
            return redirect()->route('lab.show', $labRequest)
                ->with('success', 'Results verified successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error verifying results: ' . $e->getMessage());
        }
    }

    /**
     * Approve test results
     */
    public function approveResults(LabRequest $labRequest)
    {
        $this->assertLabRequestAccess($labRequest);

        // Check if results are verified
        $unverifiedResults = $labRequest->results()->whereNull('result_verified_at')->count();
        
        if ($unverifiedResults > 0) {
            return redirect()->back()
                ->with('error', 'Cannot approve unverified results!');
        }
        
        DB::beginTransaction();
        try {
            $labRequest->results()->update([
                'approved_by' => auth()->id(),
                'result_approved_at' => now()
            ]);
            
            // Status remains 'completed' - approval is tracked in the results table
            // via approved_by and result_approved_at fields
            
            DB::commit();
            
            return redirect()->route('lab.show', $labRequest)
                ->with('success', 'Results approved successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error approving results: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF report
     */
    public function generatePdf(LabRequest $labRequest)
    {
        if (!auth()->user()->can('print_lab_results')) {
            abort(403, 'You do not have permission to print lab results.');
        }

        $this->assertLabRequestAccess($labRequest);

        try {
            $pdf = $this->labPdfService->generateTestResultsPdf($labRequest->id);
            
            // Sanitize filename by replacing invalid characters
            $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $labRequest->request_number);
            
            return $pdf->download('lab-results-' . $safeFilename . '.pdf');
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF report for patient (patient portal access)
     */
    public function generatePdfForPatient(LabRequest $labRequest)
    {
        // Only allow patients to access this
        if (!auth()->user()->hasRole('patient')) {
            abort(403, 'Only patients can access this page.');
        }

        $user = auth()->user();
        $patient = $user->patient;

        if (!$patient) {
            abort(404, 'Patient record not found for this user.');
        }

        // Verify this lab request belongs to the patient
        if ($labRequest->patient_id !== $patient->id) {
            abort(403, 'You do not have access to this lab result.');
        }

        // Only allow download if results are completed, verified, and approved
        if ($labRequest->status !== 'completed') {
            return redirect()->route('lab.my-result-details', $labRequest)
                ->with('error', 'Results are not yet available for download.');
        }

        // Verify that results are verified and approved
        $hasApprovedResults = $labRequest->results()
            ->whereNotNull('result_verified_at')
            ->whereNotNull('result_approved_at')
            ->whereNotNull('result_entered_at')
            ->exists();

        if (!$hasApprovedResults) {
            return redirect()->route('lab.my-result-details', $labRequest)
                ->with('error', 'Results are not yet available for download. Results must be verified and approved first.');
        }

        try {
            // Mark as patient request to filter results (only verified and approved)
            $pdf = $this->labPdfService->generateTestResultsPdf($labRequest->id, ['is_patient' => true]);
            
            // Sanitize filename by replacing invalid characters
            $requestNumber = $labRequest->lab_request_number ?? $labRequest->request_number ?? 'lab-result';
            $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $requestNumber);
            
            return $pdf->download('lab-results-' . $safeFilename . '.pdf');
            
        } catch (\Exception $e) {
            return redirect()->route('lab.my-result-details', $labRequest)
                ->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Determine age group from age
     */
    private function determineAgeGroup($age)
    {
        if ($age === null) {
            return 'Adult';
        }
        
        if ($age < 1) {
            return 'Newborn';
        } elseif ($age < 2) {
            return 'Infant';
        } elseif ($age < 12) {
            return 'Child';
        } elseif ($age < 18) {
            return 'Adolescent';
        } elseif ($age < 65) {
            return 'Adult';
        } else {
            return 'Elderly';
        }
    }

    /**
     * Format result value based on parameter settings
     */
    private function notifyOrderingDoctor(LabRequest $labRequest): void
    {
        if (!$labRequest->doctor_id) {
            return;
        }

        try {
            $testName = $labRequest->test_type_name ?? $labRequest->test_type ?? 'Lab Test';
            $patientName = $labRequest->patient?->full_name ?? 'Patient';

            Notification::create([
                'recipient_id' => $labRequest->doctor_id,
                'type' => 'lab_result_ready',
                'title' => 'Lab Results Ready',
                'message' => "{$testName} results for {$patientName} are now available.",
                'priority' => 'high',
                'data' => [
                    'lab_request_id' => $labRequest->id,
                    'patient_id' => $labRequest->patient_id,
                    'consultation_id' => $labRequest->consultation_id,
                    'test_name' => $testName,
                    'completed_at' => $labRequest->completed_at,
                ],
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to notify ordering doctor of lab results: ' . $e->getMessage());
        }
    }

    private function formatResultValue($value, $parameter)
    {
        if ($parameter->data_type === 'numeric') {
            $decimalPlaces = $parameter->decimal_places ?? 2;
            $formattedValue = number_format((float)$value, $decimalPlaces, '.', '');
            
            if ($parameter->unit) {
                return $formattedValue . ' ' . $parameter->unit;
            }
            
            return $formattedValue;
        }
        
        return $value;
    }
}

