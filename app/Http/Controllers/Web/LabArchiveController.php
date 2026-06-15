<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\LabRequest;
use App\Models\LabTestResult;
use App\Models\Patient;
use App\Models\LabTestTemplate;
use App\Models\LabTestParameter;
use App\Models\LabTestCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LabArchiveController extends Controller
{
    use ResolvesUserBranch;

    /**
     * Display the laboratory archive/history dashboard
     */
    public function index(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_lab_requests');

        $query = LabRequest::with([
            'patient',
            'doctor',
            'template',
            'results' => function($query) {
                $query->with(['parameter', 'performedBy', 'verifiedBy', 'approvedBy']);
            }
        ])
        ->where('branch_id', $branchId)
        ->where('status', 'completed')
        ->latest('completed_at');

        $this->applyDoctorLabScope($query);

        // Apply search filters
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('request_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('patient', function($patientQuery) use ($searchTerm) {
                      $patientQuery->where('first_name', 'like', "%{$searchTerm}%")
                                  ->orWhere('last_name', 'like', "%{$searchTerm}%")
                                  ->orWhere('patient_number', 'like', "%{$searchTerm}%")
                                  ->orWhere('contact', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('template', function($templateQuery) use ($searchTerm) {
                      $templateQuery->where('template_name', 'like', "%{$searchTerm}%")
                                   ->orWhere('category', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('completed_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('completed_at', '<=', $request->get('date_to'));
        }

        // Filter by patient
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->get('patient_id'));
        }

        // Filter by template/category
        if ($request->filled('template_id')) {
            $query->where('template_id', $request->get('template_id'));
        }

        // Filter by test category
        if ($request->filled('category_id')) {
            $query->whereHas('template', function($q) use ($request) {
                $q->where('category_id', $request->get('category_id'));
            });
        }

        // Filter by doctor
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->get('doctor_id'));
        }

        // Filter by result status
        if ($request->filled('result_status')) {
            $query->whereHas('results', function($q) use ($request) {
                $q->where('result_status', $request->get('result_status'));
            });
        }

        // Filter by abnormal flag
        if ($request->filled('abnormal_flag')) {
            $query->whereHas('results', function($q) use ($request) {
                $q->where('abnormal_flag', $request->get('abnormal_flag'));
            });
        }

        // Check if export is requested
        if ($request->get('export') === 'excel') {
            return $this->exportToExcel($query, $request);
        }

        $labRequests = $query->paginate(20);

        // Get filter options
        $patients = Patient::where('branch_id', $branchId)->orderBy('first_name')->get();
        $templates = LabTestTemplate::with(['category' => function($query) {
            $query->select('id', 'name');
        }])->orderBy('template_name')->get();
        $categories = LabTestCategory::orderBy('name')->get();
        $doctors = \App\Models\User::whereHas('roles', function($q) {
            $q->where('name', 'doctor');
        })->orderBy('first_name')->get();

        // Statistics - Apply same filters to statistics for accuracy
        $statsQuery = LabRequest::where('branch_id', $branchId)->where('status', 'completed');
        $this->applyDoctorLabScope($statsQuery);
        
        // Apply filters to statistics as well
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('completed_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('completed_at', '<=', $request->get('date_to'));
        }
        if ($request->filled('patient_id')) {
            $statsQuery->where('patient_id', $request->get('patient_id'));
        }
        if ($request->filled('doctor_id')) {
            $statsQuery->where('doctor_id', $request->get('doctor_id'));
        }
        
        $statistics = [
            'total_requests' => $statsQuery->count(),
            'today_requests' => LabRequest::where('status', 'completed')->whereDate('completed_at', today())->count(),
            'this_month' => LabRequest::where('status', 'completed')->whereMonth('completed_at', now()->month)->count(),
            'abnormal_results' => LabTestResult::whereIn('result_status', ['abnormal', 'critical'])->count(),
            'critical_alerts' => LabTestResult::where('result_status', 'critical')->count(),
        ];

        return view('lab.archive.index', compact(
            'labRequests', 
            'patients', 
            'templates', 
            'categories', 
            'doctors',
            'statistics'
        ));
    }

    /**
     * Show patient's complete lab history
     */
    public function patientHistory(Patient $patient, Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_lab_requests');
        $this->assertResourceInUserBranch($patient->branch_id, 'view_lab_requests');

        $query = LabRequest::with([
            'doctor',
            'template',
            'results' => function($query) {
                $query->with(['parameter', 'performedBy', 'verifiedBy', 'approvedBy']);
            }
        ])
        ->where('branch_id', $branchId)
        ->where('patient_id', $patient->id)
        ->where('status', 'completed')
        ->latest('completed_at');

        $this->applyDoctorLabScope($query);

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('completed_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('completed_at', '<=', $request->get('date_to'));
        }

        // Filter by test type
        if ($request->filled('test_type')) {
            $query->whereHas('template', function($q) use ($request) {
                $q->where('test_type', $request->get('test_type'));
            });
        }

        $labRequests = $query->paginate(15);

        // Get patient's test summary
        $testSummary = $this->getPatientTestSummary($patient->id);

        // Get trending analysis
        $trendingAnalysis = $this->getTrendingAnalysis($patient->id);

        return view('lab.archive.patient-history', compact(
            'patient', 
            'labRequests', 
            'testSummary',
            'trendingAnalysis'
        ));
    }

    /**
     * Show detailed comparison between two lab results
     */
    public function compareResults(Request $request)
    {
        $request1Id = $request->get('request1');
        $request2Id = $request->get('request2');

        if (!$request1Id || !$request2Id) {
            return redirect()->route('lab.archive.index')
                ->with('error', 'Please select two lab requests to compare');
        }

        $labRequest1 = LabRequest::with(['patient', 'doctor', 'template', 'results.parameter'])
            ->whereHas('patient')
            ->findOrFail($request1Id);
        $labRequest2 = LabRequest::with(['patient', 'doctor', 'template', 'results.parameter'])
            ->whereHas('patient')
            ->findOrFail($request2Id);

        $this->assertResourceInUserBranch($labRequest1->branch_id, 'view_lab_requests');
        $this->assertResourceInUserBranch($labRequest2->branch_id, 'view_lab_requests');

        // Ensure both requests are from the same patient
        if ($labRequest1->patient_id !== $labRequest2->patient_id) {
            return redirect()->route('lab.archive.index')
                ->with('error', 'Cannot compare results from different patients');
        }

        $comparison = $this->generateComparison($labRequest1, $labRequest2);

        return view('lab.archive.compare-results', compact(
            'labRequest1', 
            'labRequest2', 
            'comparison'
        ));
    }

    /**
     * Show trending analysis for a specific parameter
     */
    public function parameterTrend(Patient $patient, LabTestParameter $parameter, Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_lab_requests');
        $this->assertResourceInUserBranch($patient->branch_id, 'view_lab_requests');

        $dateFrom = $request->get('date_from', now()->subMonths(6)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $results = LabTestResult::with(['labRequest', 'parameter'])
            ->whereHas('labRequest', function($q) use ($patient, $dateFrom, $dateTo, $branchId) {
                $q->where('branch_id', $branchId)
                  ->where('patient_id', $patient->id)
                  ->whereBetween('completed_at', [$dateFrom, $dateTo]);
            })
            ->where('parameter_id', $parameter->id)
            ->orderBy('result_entered_at')
            ->get();

        // Get reference ranges for the parameter
        $referenceRanges = $parameter->referenceRanges()
            ->where('is_active', true)
            ->get();

        return view('lab.archive.parameter-trend', compact(
            'patient', 
            'parameter', 
            'results', 
            'referenceRanges',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Export patient lab history to PDF
     */
    public function exportPatientHistory(Patient $patient, Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_lab_requests');
        $this->assertResourceInUserBranch($patient->branch_id, 'view_lab_requests');

        $dateFrom = $request->get('date_from', $patient->created_at);
        $dateTo = $request->get('date_to', now());

        $labRequests = LabRequest::with([
            'doctor',
            'template',
            'results' => function($query) {
                $query->with(['parameter', 'performedBy']);
            }
        ])
        ->where('patient_id', $patient->id)
        ->where('status', 'completed')
        ->whereBetween('completed_at', [$dateFrom, $dateTo])
        ->orderBy('completed_at')
        ->get();

        $testSummary = $this->getPatientTestSummary($patient->id, $dateFrom, $dateTo);

        $pdf = app('App\Services\LabPdfService')->generatePatientHistoryPdf($patient, $labRequests, $testSummary);

        return $pdf->download("lab-history-{$patient->patient_number}.pdf");
    }

    /**
     * Get patient test summary statistics
     */
    private function getPatientTestSummary($patientId, $dateFrom = null, $dateTo = null)
    {
        $query = LabRequest::where('patient_id', $patientId)->where('status', 'completed');
        
        if ($dateFrom) {
            $query->where('completed_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('completed_at', '<=', $dateTo);
        }

        $totalRequests = $query->count();
        
        $totalResults = LabTestResult::whereHas('labRequest', function($q) use ($patientId, $dateFrom, $dateTo) {
            $q->where('patient_id', $patientId);
            if ($dateFrom) $q->where('completed_at', '>=', $dateFrom);
            if ($dateTo) $q->where('completed_at', '<=', $dateTo);
        })->count();

        $abnormalResults = LabTestResult::whereHas('labRequest', function($q) use ($patientId, $dateFrom, $dateTo) {
            $q->where('patient_id', $patientId);
            if ($dateFrom) $q->where('completed_at', '>=', $dateFrom);
            if ($dateTo) $q->where('completed_at', '<=', $dateTo);
        })->whereIn('result_status', ['abnormal', 'critical'])->count();

        $criticalResults = LabTestResult::whereHas('labRequest', function($q) use ($patientId, $dateFrom, $dateTo) {
            $q->where('patient_id', $patientId);
            if ($dateFrom) $q->where('completed_at', '>=', $dateFrom);
            if ($dateTo) $q->where('completed_at', '<=', $dateTo);
        })->where('result_status', 'critical')->count();

        return [
            'total_requests' => $totalRequests,
            'total_results' => $totalResults,
            'abnormal_results' => $abnormalResults,
            'critical_results' => $criticalResults,
            'abnormal_percentage' => $totalResults > 0 ? round(($abnormalResults / $totalResults) * 100, 2) : 0,
        ];
    }

    /**
     * Get trending analysis for patient
     */
    private function getTrendingAnalysis($patientId)
    {
        $sixMonthsAgo = now()->subMonths(6);
        
        $recentResults = LabTestResult::whereHas('labRequest', function($q) use ($patientId, $sixMonthsAgo) {
            $q->where('patient_id', $patientId)
              ->where('completed_at', '>=', $sixMonthsAgo);
        })->get();

        $trendingUp = [];
        $trendingDown = [];
        $stable = [];

        foreach ($recentResults->groupBy('parameter_id') as $parameterId => $results) {
            if ($results->count() < 2) continue;

            $firstResult = $results->sortBy('result_entered_at')->first();
            $lastResult = $results->sortByDesc('result_entered_at')->first();

            if ($firstResult->parameter->data_type === 'numeric') {
                $firstValue = floatval($firstResult->result_value);
                $lastValue = floatval($lastResult->result_value);
                
                $change = (($lastValue - $firstValue) / $firstValue) * 100;
                
                if ($change > 10) {
                    $trendingUp[] = [
                        'parameter' => $firstResult->parameter,
                        'change' => $change,
                        'first_value' => $firstValue,
                        'last_value' => $lastValue
                    ];
                } elseif ($change < -10) {
                    $trendingDown[] = [
                        'parameter' => $firstResult->parameter,
                        'change' => $change,
                        'first_value' => $firstValue,
                        'last_value' => $lastValue
                    ];
                } else {
                    $stable[] = [
                        'parameter' => $firstResult->parameter,
                        'change' => $change,
                        'first_value' => $firstValue,
                        'last_value' => $lastValue
                    ];
                }
            }
        }

        return [
            'trending_up' => $trendingUp,
            'trending_down' => $trendingDown,
            'stable' => $stable
        ];
    }

    /**
     * Generate comparison between two lab requests
     */
    private function generateComparison($labRequest1, $labRequest2)
    {
        $comparison = [];
        
        $results1 = $labRequest1->results->keyBy('parameter_id');
        $results2 = $labRequest2->results->keyBy('parameter_id');

        $allParameters = $results1->keys()->merge($results2->keys())->unique();

        foreach ($allParameters as $parameterId) {
            $result1 = $results1->get($parameterId);
            $result2 = $results2->get($parameterId);

            if ($result1 && $result2) {
                $comparison[] = [
                    'parameter' => $result1->parameter,
                    'result1' => $result1,
                    'result2' => $result2,
                    'change' => $this->calculateChange($result1, $result2),
                    'status_change' => $this->getStatusChange($result1, $result2)
                ];
            } elseif ($result1) {
                $comparison[] = [
                    'parameter' => $result1->parameter,
                    'result1' => $result1,
                    'result2' => null,
                    'change' => 'New in first test',
                    'status_change' => 'new'
                ];
            } else {
                $comparison[] = [
                    'parameter' => $result2->parameter,
                    'result1' => null,
                    'result2' => $result2,
                    'change' => 'New in second test',
                    'status_change' => 'new'
                ];
            }
        }

        return $comparison;
    }

    /**
     * Calculate change between two results
     */
    private function calculateChange($result1, $result2)
    {
        if ($result1->parameter->data_type !== 'numeric') {
            return $result1->result_value === $result2->result_value ? 'No change' : 'Changed';
        }

        $value1 = floatval($result1->result_value);
        $value2 = floatval($result2->result_value);

        if ($value1 == 0) {
            return $value2 > 0 ? 'New value' : 'No change';
        }

        $change = (($value2 - $value1) / $value1) * 100;
        
        if ($change > 0) {
            return "+" . round($change, 1) . "%";
        } elseif ($change < 0) {
            return round($change, 1) . "%";
        } else {
            return "No change";
        }
    }

    /**
     * Get status change between two results
     */
    private function getStatusChange($result1, $result2)
    {
        $status1 = $result1->result_status;
        $status2 = $result2->result_status;

        if ($status1 === $status2) {
            return 'stable';
        }

        if (($status1 === 'normal' && $status2 === 'abnormal') || 
            ($status1 === 'abnormal' && $status2 === 'critical')) {
            return 'worsened';
        }

        if (($status1 === 'abnormal' && $status2 === 'normal') || 
            ($status1 === 'critical' && $status2 === 'abnormal')) {
            return 'improved';
        }

        return 'changed';
    }

    /**
     * Export filtered lab results to Excel
     */
    private function exportToExcel($query, Request $request)
    {
        // Get all filtered results (no pagination for export)
        $labRequests = $query->get();

        // Prepare export data
        $exportData = [];
        $exportData[] = [
            'Request #',
            'Patient Number',
            'Patient Name',
            'Test Name',
            'Category',
            'Test Date',
            'Completed Date',
            'Doctor',
            'Result Status',
            'Total Parameters',
            'Abnormal Count',
            'Critical Count'
        ];

        foreach ($labRequests as $labRequest) {
            $abnormalCount = $labRequest->results->whereIn('result_status', ['abnormal'])->count();
            $criticalCount = $labRequest->results->where('result_status', 'critical')->count();
            $resultStatus = $criticalCount > 0 ? 'Critical' : ($abnormalCount > 0 ? 'Abnormal' : 'Normal');

            $exportData[] = [
                $labRequest->request_number,
                $labRequest->patient->patient_number ?? 'N/A',
                $labRequest->patient->full_name ?? 'Unknown',
                $labRequest->template->template_name ?? 'N/A',
                $labRequest->template->category->name ?? 'N/A',
                $labRequest->test_date ? $labRequest->test_date->format('Y-m-d') : 'N/A',
                $labRequest->completed_at ? $labRequest->completed_at->format('Y-m-d H:i') : 'N/A',
                $labRequest->doctor ? "Dr. {$labRequest->doctor->first_name} {$labRequest->doctor->last_name}" : 'N/A',
                $resultStatus,
                $labRequest->results->count(),
                $abnormalCount,
                $criticalCount
            ];
        }

        // Generate CSV content
        $filename = 'lab-archive-' . date('Y-m-d-His') . '.csv';
        $handle = fopen('php://temp', 'r+');
        
        foreach ($exportData as $row) {
            fputcsv($handle, $row);
        }
        
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        // Return as downloadable file
        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
