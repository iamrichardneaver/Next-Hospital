<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EyeTestRequest;
use App\Models\EyeService;
use App\Models\EyeTestTemplate;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\User;
use App\Models\Branch;
use App\Models\Appointment;
use App\Services\EyeTestPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EyeTestRequestController extends Controller
{
    /**
     * Display a listing of eye test requests.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = EyeTestRequest::with([
                'patient',
                'appointment',
                'consultation',
                'service',
                'template',
                'requestedBy',
                'assignedTo',
                'branch',
                'resultsEnteredBy',
                'resultsVerifiedBy'
            ]);

            // Filter by patient
            if ($request->has('patient_id')) {
                $query->byPatient($request->patient_id);
            }

            // Filter by assigned user
            if ($request->has('assigned_to')) {
                $query->byAssignedTo($request->assigned_to);
            }

            // Filter by branch
            if ($request->has('branch_id')) {
                $query->byBranch($request->branch_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by priority
            if ($request->has('priority')) {
                $query->byPriority($request->priority);
            }

            // Filter by service
            if ($request->has('service_id')) {
                $query->where('service_id', $request->service_id);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhere('reason_for_test', 'like', "%{$search}%")
                      ->orWhereHas('patient', function ($patientQuery) use ($search) {
                          $patientQuery->where('first_name', 'like', "%{$search}%")
                                     ->orWhere('last_name', 'like', "%{$search}%")
                                     ->orWhere('patient_id', 'like', "%{$search}%");
                      });
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $requests = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $requests,
                'message' => 'Eye test requests retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve eye test requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created eye test request.
     * This method creates an appointment first, then creates the eye test request.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'service_id' => 'required|exists:eye_services,id',
                'template_id' => 'required|exists:eye_test_templates,id',
                'doctor_id' => 'required|exists:users,id', // Doctor for appointment
                'assigned_to' => 'nullable|exists:users,id', // Optometrist/Ophthalmologist
                'branch_id' => 'required|exists:branches,id',
                'appointment_date' => 'required|date|after_or_equal:today',
                'appointment_time' => 'required|date_format:H:i',
                'appointment_type' => 'required|in:in-person,teleconsultation',
                'clinical_notes' => 'nullable|string',
                'reason_for_test' => 'nullable|string',
                'priority' => 'required|in:routine,urgent,emergency',
                'requires_dilation' => 'boolean',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get service details for pricing
            $service = EyeService::findOrFail($request->service_id);
            $serviceCost = $service->base_price;

            // Check for appointment conflicts
            $conflict = $this->checkAppointmentConflict(
                $request->doctor_id, 
                $request->appointment_date, 
                $request->appointment_time, 
                $service->duration_minutes
            );
            
            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment time conflict detected',
                    'conflict' => $conflict
                ], 409);
            }

            // Create appointment first
            $appointment = Appointment::create([
                'patient_id' => $request->patient_id,
                'doctor_id' => $request->doctor_id,
                'branch_id' => $request->branch_id,
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $request->appointment_time,
                'appointment_type' => $request->appointment_type,
                'reason' => $request->reason_for_test ?: 'Eye examination - ' . $service->service_name,
                'status' => 'scheduled',
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            // Create eye test request linked to appointment
            $testRequest = EyeTestRequest::create([
                'patient_id' => $request->patient_id,
                'appointment_id' => $appointment->id,
                'consultation_id' => $request->consultation_id,
                'service_id' => $request->service_id,
                'template_id' => $request->template_id,
                'requested_by' => auth()->id(),
                'assigned_to' => $request->assigned_to,
                'branch_id' => $request->branch_id,
                'clinical_notes' => $request->clinical_notes,
                'reason_for_test' => $request->reason_for_test,
                'priority' => $request->priority,
                'requires_dilation' => $request->requires_dilation,
                'scheduled_at' => $request->appointment_date . ' ' . $request->appointment_time,
                'service_cost' => $serviceCost,
                'created_by' => auth()->id(),
            ]);

            $testRequest->load([
                'patient',
                'appointment',
                'consultation',
                'service',
                'template',
                'requestedBy',
                'assignedTo',
                'branch'
            ]);

            return response()->json([
                'success' => true,
                'data' => $testRequest,
                'message' => 'Eye test appointment and request created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create eye test request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create eye test request from existing appointment.
     */
    public function createFromAppointment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'appointment_id' => 'required|exists:appointments,id',
                'service_id' => 'required|exists:eye_services,id',
                'template_id' => 'required|exists:eye_test_templates,id',
                'assigned_to' => 'nullable|exists:users,id',
                'clinical_notes' => 'nullable|string',
                'reason_for_test' => 'nullable|string',
                'priority' => 'required|in:routine,urgent,emergency',
                'requires_dilation' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get appointment details
            $appointment = Appointment::with(['patient', 'doctor', 'branch'])->findOrFail($request->appointment_id);
            
            // Check if appointment is scheduled
            if ($appointment->status !== 'scheduled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment must be scheduled to create eye test request'
                ], 422);
            }

            // Check if eye test request already exists for this appointment
            $existingRequest = EyeTestRequest::where('appointment_id', $request->appointment_id)->first();
            if ($existingRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Eye test request already exists for this appointment'
                ], 422);
            }

            // Get service details for pricing
            $service = EyeService::findOrFail($request->service_id);
            $serviceCost = $service->base_price;

            // Create eye test request linked to appointment
            $testRequest = EyeTestRequest::create([
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'consultation_id' => $request->consultation_id,
                'service_id' => $request->service_id,
                'template_id' => $request->template_id,
                'requested_by' => auth()->id(),
                'assigned_to' => $request->assigned_to,
                'branch_id' => $appointment->branch_id,
                'clinical_notes' => $request->clinical_notes,
                'reason_for_test' => $request->reason_for_test,
                'priority' => $request->priority,
                'requires_dilation' => $request->requires_dilation,
                'scheduled_at' => $appointment->appointment_date . ' ' . $appointment->appointment_time,
                'service_cost' => $serviceCost,
                'created_by' => auth()->id(),
            ]);

            $testRequest->load([
                'patient',
                'appointment',
                'consultation',
                'service',
                'template',
                'requestedBy',
                'assignedTo',
                'branch'
            ]);

            return response()->json([
                'success' => true,
                'data' => $testRequest,
                'message' => 'Eye test request created from appointment successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create eye test request from appointment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified eye test request.
     */
    public function show(EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            $eyeTestRequest->load([
                'patient',
                'appointment',
                'consultation',
                'service',
                'template',
                'requestedBy',
                'assignedTo',
                'branch',
                'resultsEnteredBy',
                'resultsVerifiedBy',
                'qualityControlBy',
                'testResults.parameter',
                'testImages',
                'comments.commentedBy',
                'billingItems.service'
            ]);

            return response()->json([
                'success' => true,
                'data' => $eyeTestRequest,
                'message' => 'Eye test request retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve eye test request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified eye test request.
     */
    public function update(Request $request, EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'assigned_to' => 'nullable|exists:users,id',
                'clinical_notes' => 'nullable|string',
                'reason_for_test' => 'nullable|string',
                'priority' => 'in:routine,urgent,emergency',
                'requires_dilation' => 'boolean',
                'dilation_completed' => 'boolean',
                'dilation_notes' => 'nullable|string',
                'scheduled_at' => 'nullable|date',
                'status' => 'in:pending,in_progress,completed,cancelled,failed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $eyeTestRequest->update($request->validated());

            $eyeTestRequest->load([
                'patient',
                'consultation',
                'service',
                'template',
                'requestedBy',
                'assignedTo',
                'branch'
            ]);

            return response()->json([
                'success' => true,
                'data' => $eyeTestRequest,
                'message' => 'Eye test request updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update eye test request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start the eye test.
     */
    public function start(EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            if (!$eyeTestRequest->canBeStarted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test cannot be started at this time'
                ], 422);
            }

            $eyeTestRequest->startTest();

            return response()->json([
                'success' => true,
                'data' => $eyeTestRequest->fresh(),
                'message' => 'Eye test started successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start eye test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete the eye test.
     */
    public function complete(EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            if (!$eyeTestRequest->canBeCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test cannot be completed at this time'
                ], 422);
            }

            $eyeTestRequest->completeTest();

            return response()->json([
                'success' => true,
                'data' => $eyeTestRequest->fresh(),
                'message' => 'Eye test completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete eye test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel the eye test.
     */
    public function cancel(Request $request, EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            if (!$eyeTestRequest->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test cannot be cancelled at this time'
                ], 422);
            }

            $reason = $request->input('reason', 'Test cancelled');
            $eyeTestRequest->cancelTest($reason);

            return response()->json([
                'success' => true,
                'data' => $eyeTestRequest->fresh(),
                'message' => 'Eye test cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel eye test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark results as entered.
     */
    public function markResultsEntered(EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            $eyeTestRequest->markResultsEntered(auth()->id());

            return response()->json([
                'success' => true,
                'data' => $eyeTestRequest->fresh(),
                'message' => 'Results marked as entered successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark results as entered',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark results as verified.
     */
    public function markResultsVerified(EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            $eyeTestRequest->markResultsVerified(auth()->id());

            return response()->json([
                'success' => true,
                'data' => $eyeTestRequest->fresh(),
                'message' => 'Results marked as verified successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark results as verified',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark quality control as passed.
     */
    public function markQualityControlPassed(Request $request, EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            $notes = $request->input('notes');
            $eyeTestRequest->markQualityControlPassed(auth()->id(), $notes);

            return response()->json([
                'success' => true,
                'data' => $eyeTestRequest->fresh(),
                'message' => 'Quality control marked as passed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark quality control as passed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get test request statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = EyeTestRequest::query();

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter by branch
            if ($request->has('branch_id')) {
                $query->byBranch($request->branch_id);
            }

            $stats = [
                'total_requests' => $query->count(),
                'pending' => $query->clone()->pending()->count(),
                'in_progress' => $query->clone()->inProgress()->count(),
                'completed' => $query->clone()->completed()->count(),
                'cancelled' => $query->clone()->cancelled()->count(),
                'has_results' => $query->clone()->hasResults()->count(),
                'quality_control_passed' => $query->clone()->qualityControlPassed()->count(),
                'by_priority' => $query->clone()
                    ->select('priority', DB::raw('count(*) as count'))
                    ->groupBy('priority')
                    ->get(),
                'by_status' => $query->clone()
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->get(),
                'average_duration' => $query->clone()
                    ->whereNotNull('actual_duration_minutes')
                    ->avg('actual_duration_minutes'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Test request statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve test request statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard data.
     */
    public function dashboard(): JsonResponse
    {
        try {
            $data = [
                'today_requests' => EyeTestRequest::whereDate('created_at', today())->count(),
                'pending_requests' => EyeTestRequest::pending()->count(),
                'in_progress_requests' => EyeTestRequest::inProgress()->count(),
                'completed_today' => EyeTestRequest::completed()
                    ->whereDate('completed_at', today())->count(),
                'urgent_requests' => EyeTestRequest::byPriority('urgent')->count(),
                'emergency_requests' => EyeTestRequest::byPriority('emergency')->count(),
                'recent_requests' => EyeTestRequest::with(['patient', 'service', 'assignedTo'])
                    ->latest()
                    ->limit(10)
                    ->get(),
                'my_requests' => EyeTestRequest::byAssignedTo(auth()->id())
                    ->with(['patient', 'service'])
                    ->latest()
                    ->limit(5)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Dashboard data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check for appointment conflicts.
     */
    private function checkAppointmentConflict($doctorId, $appointmentDate, $appointmentTime, $durationMinutes, $excludeId = null)
    {
        $startTime = Carbon::parse($appointmentDate . ' ' . $appointmentTime);
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        $query = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', $appointmentDate)
            ->where('status', 'scheduled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('appointment_time', [
                    $startTime->format('H:i:s'),
                    $endTime->format('H:i:s')
                ])
                ->orWhere(function ($subQ) use ($startTime, $endTime) {
                    $subQ->where('appointment_time', '<', $startTime->format('H:i:s'))
                         ->whereRaw("ADDTIME(appointment_time, '00:30:00') > ?", [$startTime->format('H:i:s')]);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflictingAppointment = $query->first();

        if ($conflictingAppointment) {
            return [
                'conflicting_appointment_id' => $conflictingAppointment->id,
                'conflicting_time' => $conflictingAppointment->appointment_time->format('H:i'),
                'conflicting_patient' => $conflictingAppointment->patient->first_name . ' ' . $conflictingAppointment->patient->last_name,
                'conflicting_reason' => $conflictingAppointment->reason
            ];
        }

        return null;
    }

    /**
     * Generate PDF report for eye test results.
     */
    public function generatePdfReport(EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            $pdfService = new EyeTestPdfService();
            $filepath = $pdfService->generateEyeTestReport($eyeTestRequest);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'filepath' => $filepath,
                    'download_url' => $pdfService->getDownloadUrl($filepath),
                    'file_size' => $pdfService->getFileSize($filepath),
                ],
                'message' => 'PDF report generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate custom PDF report for eye test results.
     */
    public function generateCustomPdfReport(Request $request, EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'template' => 'required|in:standard,detailed,simple,nhis'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pdfService = new EyeTestPdfService();
            $filepath = $pdfService->generateCustomEyeTestReport($eyeTestRequest, $request->template);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'filepath' => $filepath,
                    'download_url' => $pdfService->getDownloadUrl($filepath),
                    'file_size' => $pdfService->getFileSize($filepath),
                    'template' => $request->template,
                ],
                'message' => 'Custom PDF report generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate custom PDF report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate summary PDF report for multiple eye tests.
     */
    public function generateSummaryPdfReport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_request_ids' => 'required|array|min:1',
                'test_request_ids.*' => 'exists:eye_test_requests,id',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pdfService = new EyeTestPdfService();
            $filepath = $pdfService->generateSummaryReport(
                $request->test_request_ids,
                $request->date_from,
                $request->date_to
            );
            
            return response()->json([
                'success' => true,
                'data' => [
                    'filepath' => $filepath,
                    'download_url' => $pdfService->getDownloadUrl($filepath),
                    'file_size' => $pdfService->getFileSize($filepath),
                ],
                'message' => 'Summary PDF report generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate summary PDF report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download PDF report.
     */
    public function downloadPdfReport(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filepath' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pdfService = new EyeTestPdfService();
            
            if (!$pdfService->pdfExists($request->filepath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF file not found'
                ], 404);
            }

            $fullPath = storage_path('app/public/' . $request->filepath);
            $filename = basename($request->filepath);

            return response()->download($fullPath, $filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download PDF report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified eye test request.
     */
    public function destroy(EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            // Check if test can be deleted
            if (in_array($eyeTestRequest->status, ['in_progress', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete test that is in progress or completed'
                ], 422);
            }

            $eyeTestRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Eye test request deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting eye test request: ' . $e->getMessage()
            ], 500);
        }
    }
}
