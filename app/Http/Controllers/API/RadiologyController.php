<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\RadiologyRequest;
use App\Models\RadiologyStudy;
use App\Models\RadiologyReport;
use App\Models\RadiologySeries;
use App\Models\RadiologyImage;
use App\Models\ImagingModality;
use App\Models\RadiologyDepartment;
use App\Models\RadiologyEquipment;
use App\Models\RadiologyTechnician;
use App\Models\ContrastAgent;
use App\Models\RadiationDose;
use App\Models\RadiologyProtocol;
use App\Models\RadiologyScheduleSlot;
use App\Services\RadiologyPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RadiologyController extends Controller
{
    use ResolvesUserBranch;

    /**
     * Get all radiology requests with filters
     */
    public function getRequests(Request $request): JsonResponse
    {
        $branchId = $this->resolveUserBranchId($this->resolveRadiologyViewPermission());

        $query = RadiologyRequest::with([
            'patient',
            'doctor',
            'modality',
            'department',
            'technician.user',
            'radiologist',
            'study'
        ])->where('branch_id', $branchId);

        $this->applyDoctorRadiologyScope($query);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('modality_id')) {
            $query->where('modality_id', $request->modality_id);
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('radiologist_id')) {
            $query->where('radiologist_id', $request->radiologist_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('requested_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('requested_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($patientQuery) use ($search) {
                      $patientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('patient_id', 'like', "%{$search}%");
                  });
            });
        }

        $requests = $query->orderBy('id', 'desc')
                         ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total()
            ]
        ]);
    }

    /**
     * Create a new radiology request
     */
    public function createRequest(Request $request): JsonResponse
    {
        if (auth()->user()->hasRole('doctor')) {
            $request->merge(['doctor_id' => auth()->id()]);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'radiologist_id' => 'nullable|exists:users,id',
            'modality_id' => 'required|exists:imaging_modalities,id',
            'department_id' => 'required|exists:radiology_departments,id',
            'clinical_history' => 'nullable|string',
            'clinical_question' => 'nullable|string',
            'indication' => 'nullable|string',
            'priority' => 'required|in:routine,urgent,stat,emergency',
            'scheduled_date' => 'nullable|date|after_or_equal:today',
            'scheduled_time' => 'nullable|date_format:H:i'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $branchId = $this->resolveUserBranchId('create_radiology_requests');
        $patient = \App\Models\Patient::findOrFail($request->patient_id);
        if ((int) $patient->branch_id !== $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Patient does not belong to your branch',
            ], 403);
        }

        $data = $validator->validated();
        $data['request_number'] = $this->generateRequestNumber();
        $data['requested_date'] = now()->toDateString();
        $data['status'] = 'requested';
        $data['branch_id'] = $branchId;
        $data['billing_status'] = 'pending';
        // Do not snapshot billing_amount — PendingChargesService recomputes modality price + module fee

        $radiologyRequest = RadiologyRequest::create($data);

        // Update workflow metadata if consultation exists
        if ($data['consultation_id'] ?? null) {
            $consultation = \App\Models\Consultation::find($data['consultation_id']);
            if ($consultation && $consultation->visit) {
                $instance = \App\Models\WorkflowInstance::where('entity_type', 'visit')
                    ->where('entity_id', $consultation->visit->id)
                    ->where('is_active', true)
                    ->first();
                
                if ($instance) {
                    $metadata = $instance->metadata ?? [];
                    $metadata['imaging_ordered'] = true;
                    $instance->update(['metadata' => $metadata]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Radiology request created successfully',
            'data' => $radiologyRequest->load(['patient', 'doctor', 'modality', 'department', 'radiologist', 'consultation'])
        ], 201);
    }

    /**
     * Update radiology request
     */
    public function updateRequest(Request $request, $id): JsonResponse
    {
        $radiologyRequest = RadiologyRequest::findOrFail($id);
        $this->assertRadiologyRequestAccess($radiologyRequest);
        $this->assertRadiologyManagementAccess();

        $validator = Validator::make($request->all(), [
            'clinical_history' => 'nullable|string',
            'clinical_question' => 'nullable|string',
            'indication' => 'nullable|string',
            'priority' => 'sometimes|in:routine,urgent,stat,emergency',
            'status' => 'sometimes|in:requested,scheduled,in_progress,completed,cancelled,rejected',
            'scheduled_date' => 'nullable|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'technician_id' => 'nullable|exists:radiology_technicians,id',
            'radiologist_id' => 'nullable|exists:users,id',
            'technician_notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $radiologyRequest->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Radiology request updated successfully',
            'data' => $radiologyRequest->load(['patient', 'doctor', 'modality', 'department', 'technician.user', 'radiologist'])
        ]);
    }

    /**
     * Schedule a radiology request
     */
    public function scheduleRequest(Request $request, $id): JsonResponse
    {
        $this->assertRadiologyManagementAccess();
        $radiologyRequest = RadiologyRequest::findOrFail($id);
        $this->assertRadiologyRequestAccess($radiologyRequest);

        if (!$radiologyRequest->canBeScheduled()) {
            return response()->json([
                'success' => false,
                'message' => 'Request cannot be scheduled in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|date_format:H:i',
            'equipment_id' => 'required|exists:radiology_equipment,id',
            'technician_id' => 'nullable|exists:radiology_technicians,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['status'] = 'scheduled';

        $radiologyRequest->update($data);

        // Create schedule slot
        RadiologyScheduleSlot::create([
            'equipment_id' => $data['equipment_id'],
            'slot_date' => $data['scheduled_date'],
            'start_time' => $data['scheduled_time'],
            'end_time' => now()->setTimeFromTimeString($data['scheduled_time'])->addMinutes(30),
            'status' => 'booked',
            'booked_by' => auth()->id(),
            'study_id' => null // Will be updated when study is created
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Radiology request scheduled successfully',
            'data' => $radiologyRequest->load(['patient', 'doctor', 'modality', 'department', 'technician.user'])
        ]);
    }

    /**
     * Start a radiology study
     */
    public function startStudy(Request $request, $id): JsonResponse
    {
        $this->assertRadiologyManagementAccess();
        $radiologyRequest = RadiologyRequest::findOrFail($id);
        $this->assertRadiologyRequestAccess($radiologyRequest);

        // Allow starting study from requested or scheduled status
        if (!in_array($radiologyRequest->status, ['requested', 'scheduled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Request must be requested or scheduled before starting study'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'equipment_id' => 'nullable|exists:radiology_equipment,id',
            'study_description' => 'required|string',
            'study_notes' => 'nullable|string',
            'technique_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['study_uid'] = $this->generateStudyUID();
        $data['request_id'] = $radiologyRequest->id;
        $data['patient_id'] = $radiologyRequest->patient_id;
        $data['modality_id'] = $radiologyRequest->modality_id;
        $data['status'] = 'in_progress';
        $data['study_date'] = now();

        // Handle nullable equipment_id - use fallback equipment if empty (equipment_id is required in DB)
        if (empty($data['equipment_id'])) {
            // Use first available equipment as fallback since equipment_id is required in database
            $fallbackEquipment = \App\Models\RadiologyEquipment::where('is_active', true)->first();
            if ($fallbackEquipment) {
                $data['equipment_id'] = $fallbackEquipment->id;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No active radiology equipment available. Please configure equipment first.'
                ], 400);
            }
        }

        // Automatically assign the logged-in user as the radiologist
        $data['radiologist_id'] = auth()->id();
        
        // Automatically assign technician: find RadiologyTechnician record for logged-in user
        $technician = \App\Models\RadiologyTechnician::where('user_id', auth()->id())->first();
        $data['technician_id'] = $technician ? $technician->id : null;

        $study = RadiologyStudy::create($data);

        // Update request status and assign radiologist
        $updateData = [
            'status' => 'in_progress',
            'radiologist_id' => auth()->id()
        ];
        
        $radiologyRequest->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Radiology study started successfully',
            'data' => $study->load(['patient', 'modality', 'equipment', 'technician.user', 'radiologist', 'request'])
        ]);
    }

    /**
     * Complete a radiology study
     */
    public function completeStudy(Request $request, $id): JsonResponse
    {
        $this->assertRadiologyManagementAccess();
        $study = RadiologyStudy::with('request')->findOrFail($id);
        $this->assertRadiologyStudyAccess($study);

        if ($study->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Study must be in progress to complete'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'technique_notes' => 'nullable|string',
            'study_parameters' => 'nullable|array',
            'radiation_dose' => 'nullable|array',
            'contrast_usage' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['status'] = 'completed';
        $data['completed_date'] = now();

        $study->update($data);

        // Update request status
        $study->request->update(['status' => 'completed']);

        // Create radiation dose record if provided
        if (isset($data['radiation_dose'])) {
            RadiationDose::create([
                'study_id' => $study->id,
                ...$data['radiation_dose']
            ]);
        }

        // Create contrast usage record if provided
        if (isset($data['contrast_usage'])) {
            foreach ($data['contrast_usage'] as $contrast) {
                StudyContrastUsage::create([
                    'study_id' => $study->id,
                    'contrast_agent_id' => $contrast['contrast_agent_id'],
                    'dose_ml' => $contrast['dose_ml'],
                    'route' => $contrast['route'],
                    'administered_at' => now(),
                    'administered_by' => auth()->id(),
                    'notes' => $contrast['notes'] ?? null
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Radiology study completed successfully',
            'data' => $study->load(['patient', 'modality', 'equipment', 'technician.user', 'radiologist', 'radiationDose', 'contrastUsage', 'series.images'])
        ]);
    }

    /**
     * Delete a radiology request
     */
    public function deleteRequest($id): JsonResponse
    {
        try {
            $this->assertRadiologyManagementAccess();
            $request = RadiologyRequest::findOrFail($id);
            $this->assertRadiologyRequestAccess($request);
            $request->delete();

            return response()->json([
                'success' => true,
                'message' => 'Radiology request deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete radiology request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update status of radiology requests
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->assertRadiologyManagementAccess();

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:radiology_requests,id',
            'status' => 'required|string|in:requested,scheduled,in_progress,completed,cancelled,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updated = RadiologyRequest::whereIn('id', $request->ids)
                ->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => "Updated {$updated} radiology requests to {$request->status}"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update radiology requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete radiology requests
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->assertRadiologyManagementAccess();

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:radiology_requests,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $deleted = RadiologyRequest::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deleted} radiology requests"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete radiology requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload DICOM images for a study
     */
    public function uploadImages(Request $request, $studyId): JsonResponse
    {
        $study = RadiologyStudy::with(['series.images', 'request'])->findOrFail($studyId);
        $this->assertRadiologyStudyAccess($study);

        if (!$this->canUploadRadiologyImages()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to upload radiology images.'
            ], 403);
        }

        if ($study->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Images can only be uploaded while the study is in progress.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1',
            'images.*' => 'required|file|mimes:jpg,jpeg,png,gif,dcm,dicom|max:50000', // 50MB max per file
            'series_description' => 'required|string',
            'body_part_examined' => 'nullable|string',
            'view_position' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create series
        $series = \App\Models\RadiologySeries::create([
            'series_uid' => $this->generateSeriesUID(),
            'study_id' => $study->id,
            'series_number' => $study->series()->count() + 1,
            'series_description' => $request->series_description,
            'body_part_examined' => $request->body_part_examined,
            'view_position' => $request->view_position,
            'number_of_instances' => count($request->file('images'))
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $index => $file) {
            try {
                $image = RadiologyImage::storeUploadedFile(
                    $series,
                    $file,
                    $index + 1,
                    $this->generateSOPInstanceUID()
                );

                if (str_contains((string) $image->mime_type, 'dicom')) {
                    $image->update(['dicom_tags' => $this->extractDicomTags($file)]);
                }
            } catch (\Exception $e) {
                \Log::error('Error uploading radiology image via API', [
                    'study_id' => $study->id,
                    'series_id' => $series->id ?? null,
                    'file_index' => $index,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }

            $uploadedImages[] = $image;
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => [
                'series' => $series,
                'images' => $uploadedImages
            ]
        ]);
    }

    /**
     * Create radiology report
     */
    public function createReport(Request $request, $studyId): JsonResponse
    {
        $study = RadiologyStudy::with(['series.images', 'request.patient'])->findOrFail($studyId);

        if ($study->hasReport()) {
            return response()->json([
                'success' => false,
                'message' => 'Report already exists for this study'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'findings' => 'required|string',
            'impression' => 'required|string',
            'recommendations' => 'nullable|string',
            'status' => 'sometimes|in:draft,preliminary,final',
            'selected_images' => 'nullable|array',
            'selected_images.*' => 'exists:radiology_images,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['study_id'] = $study->id;
        $data['radiologist_id'] = auth()->id();
        $data['dictated_date'] = now();
        
        // Store selected images as JSON array
        if (isset($data['selected_images']) && !empty($data['selected_images'])) {
            $data['selected_images'] = $data['selected_images'];
        } else {
            $data['selected_images'] = null;
        }

        $report = \App\Models\RadiologyReport::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Radiology report created successfully' . (isset($data['selected_images']) && count($data['selected_images']) > 0 ? ' with ' . count($data['selected_images']) . ' selected images' : ''),
            'data' => $report->load(['radiologist', 'study'])
        ]);
    }

    /**
     * Get imaging modalities
     */
    public function getModalities(): JsonResponse
    {
        $this->assertRadiologyLookupAccess();

        $modalities = ImagingModality::where('is_active', true)
                                   ->orderBy('name')
                                   ->get();

        return response()->json([
            'success' => true,
            'data' => $modalities
        ]);
    }

    /**
     * Get radiology departments
     */
    public function getDepartments(): JsonResponse
    {
        $this->assertRadiologyLookupAccess();

        $departments = RadiologyDepartment::where('is_active', true)
                                        ->orderBy('name')
                                        ->get();

        return response()->json([
            'success' => true,
            'data' => $departments
        ]);
    }

    /**
     * Get available equipment for scheduling
     */
    public function getAvailableEquipment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'modality_id' => 'required|exists:imaging_modalities,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|min:15'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $endTime = now()->setTimeFromTimeString($data['start_time'])
                      ->addMinutes($data['duration_minutes']);

        $equipment = RadiologyEquipment::where('modality_id', $data['modality_id'])
                                     ->where('status', 'operational')
                                     ->where('is_active', true)
                                     ->whereDoesntHave('scheduleSlots', function ($query) use ($data, $endTime) {
                                         $query->where('slot_date', $data['date'])
                                               ->where(function ($timeQuery) use ($data, $endTime) {
                                                   $timeQuery->whereBetween('start_time', [$data['start_time'], $endTime->format('H:i')])
                                                           ->orWhereBetween('end_time', [$data['start_time'], $endTime->format('H:i')])
                                                           ->orWhere(function ($overlapQuery) use ($data, $endTime) {
                                                               $overlapQuery->where('start_time', '<=', $data['start_time'])
                                                                           ->where('end_time', '>=', $endTime->format('H:i'));
                                                           });
                                               });
                                     })
                                     ->with(['modality', 'department'])
                                     ->get();

        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }

    /**
     * Get contrast agents
     */
    public function getContrastAgents(): JsonResponse
    {
        $agents = ContrastAgent::where('is_active', true)
                             ->orderBy('name')
                             ->get();

        return response()->json([
            'success' => true,
            'data' => $agents
        ]);
    }

    /**
     * Get radiology protocols
     */
    public function getProtocols(Request $request): JsonResponse
    {
        $query = RadiologyProtocol::where('is_active', true);

        if ($request->has('modality_id')) {
            $query->where('modality_id', $request->modality_id);
        }

        if ($request->has('body_part')) {
            $query->where('body_part', 'like', "%{$request->body_part}%");
        }

        $protocols = $query->with('modality')
                          ->orderBy('name')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $protocols
        ]);
    }

    /**
     * Generate unique request number
     */
    private function generateRequestNumber(): string
    {
        $prefix = 'RAD';
        $year = now()->year;
        $month = now()->format('m');
        
        $lastRequest = RadiologyRequest::whereYear('created_at', $year)
                                     ->whereMonth('created_at', $month)
                                     ->orderBy('id', 'desc')
                                     ->first();
        
        $sequence = $lastRequest ? (int) substr($lastRequest->request_number, -4) + 1 : 1;
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Generate DICOM Study Instance UID
     */
    private function generateStudyUID(): string
    {
        return '1.2.826.0.1.3680043.8.498.' . time() . '.' . rand(1000, 9999);
    }

    /**
     * Generate DICOM Series Instance UID
     */
    private function generateSeriesUID(): string
    {
        return '1.2.826.0.1.3680043.8.498.' . time() . '.' . rand(10000, 99999);
    }

    /**
     * Generate DICOM SOP Instance UID
     */
    private function generateSOPInstanceUID(): string
    {
        return '1.2.826.0.1.3680043.8.498.' . time() . '.' . rand(100000, 999999);
    }

    /**
     * Get series images with dynamic URLs
     * Matches Web RadiologyController::getSeriesImages()
     */
    public function getSeriesImages(Request $request, $seriesId): JsonResponse
    {
        try {
            $series = RadiologySeries::with(['images', 'study.request'])->findOrFail($seriesId);
            
            if (!auth()->user()->can('view_radiology_studies')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view radiology images.'
                ], 403);
            }

            if ($series->study) {
                $this->assertRadiologyStudyAccess($series->study);
            }

            return response()->json([
                'success' => true,
                'series' => [
                    'id' => $series->id,
                    'series_number' => $series->series_number,
                    'series_description' => $series->series_description,
                    'study_id' => $series->study_id,
                ],
                'images' => $series->images->map(function($image) {
                    $fileUrl = route('api.radiology.images.file', $image->id);
                    
                    return [
                        'id' => $image->id,
                        'instance_number' => $image->instance_number,
                        'file_path' => $image->file_path,
                        'file_url' => $fileUrl,
                        'file_name' => $image->file_name,
                        'file_size' => $image->file_size,
                        'mime_type' => $image->mime_type,
                        'exists' => $image->exists(),
                    ];
                })
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Series not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error getting series images via API', [
                'series_id' => $seriesId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get series images: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Serve radiology image file dynamically
     * Matches Web RadiologyController::serveImage()
     * This route handles file serving to avoid 403 errors and works with all file locations
     */
    public function serveImage($imageId)
    {
        try {
            $image = RadiologyImage::with(['series.study.request', 'series.study.patient'])->findOrFail($imageId);
            
            if (!auth()->check() || !auth()->user()->can('view_radiology_studies')) {
                \Log::warning('Unauthorized access attempt to radiology image via API', [
                    'image_id' => $imageId,
                    'user_id' => auth()->id(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view radiology images.'
                ], 403);
            }

            $this->assertRadiologyImageAccess($image);
            
            $filePath = $image->getFullPath();
            
            // Log path resolution for debugging
            \Log::debug('Serving radiology image via API', [
                'image_id' => $imageId,
                'file_path_db' => $image->file_path,
                'resolved_path' => $filePath,
                'file_exists' => file_exists($filePath),
            ]);
            
            if (!file_exists($filePath) || !is_file($filePath)) {
                \Log::error('Radiology image file not found via API', [
                    'image_id' => $imageId,
                    'file_path_db' => $image->file_path,
                    'resolved_path' => $filePath,
                    'series_id' => $image->series_id,
                    'study_id' => $image->series->study_id ?? null,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Image file not found on disk.'
                ], 404);
            }
            
            // Verify file is readable
            if (!is_readable($filePath)) {
                \Log::error('Radiology image file is not readable via API', [
                    'image_id' => $imageId,
                    'file_path' => $filePath,
                    'permissions' => substr(sprintf('%o', fileperms($filePath)), -4),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Image file is not accessible.'
                ], 500);
            }
            
            // Determine MIME type
            $mimeType = $image->mime_type;
            if (!$mimeType) {
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $mimeType = match(strtolower($extension)) {
                    'dcm', 'dicom' => 'application/dicom',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    default => 'application/octet-stream'
                };
            }
            
            $fileUrl = route('api.radiology.images.file', $image->id);
            
            return response()->json([
                'success' => true,
                'image' => [
                    'id' => $image->id,
                    'file_name' => $image->file_name,
                    'file_url' => $fileUrl,
                    'mime_type' => $mimeType,
                    'file_size' => $image->file_size,
                    'exists' => true,
                ],
                'download_url' => $fileUrl
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Radiology image not found in database via API', [
                'image_id' => $imageId,
                'user_id' => auth()->id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Image not found.'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error serving radiology image via API', [
                'image_id' => $imageId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error serving image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Serve radiology image file (binary response for direct file access)
     * Alternative endpoint that returns actual file content
     */
    public function serveImageFile($imageId)
    {
        try {
            $image = RadiologyImage::with(['series.study.request', 'series.study.patient'])->findOrFail($imageId);
            
            if (!auth()->check() || !auth()->user()->can('view_radiology_studies')) {
                abort(403, 'You do not have permission to view radiology images.');
            }

            $this->assertRadiologyImageAccess($image);
            
            $filePath = $image->getFullPath();
            
            if (!file_exists($filePath) || !is_file($filePath)) {
                abort(404, 'Image file not found on disk.');
            }
            
            if (!is_readable($filePath)) {
                abort(500, 'Image file is not accessible.');
            }
            
            // Determine MIME type
            $mimeType = $image->mime_type;
            if (!$mimeType) {
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $mimeType = match(strtolower($extension)) {
                    'dcm', 'dicom' => 'application/dicom',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    default => 'application/octet-stream'
                };
            }
            
            // Serve the file with appropriate headers
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $image->file_name . '"',
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error serving radiology image file via API', [
                'image_id' => $imageId,
                'error' => $e->getMessage()
            ]);
            abort(404, 'Image not found or access denied.');
        }
    }

    /**
     * Generate radiology report PDF
     */
    public function generateReportPdf($reportId): JsonResponse
    {
        try {
            $pdf = $this->pdfService()->generateRadiologyReportPdf($reportId);
            
            $filename = 'radiology-report-' . $reportId . '-' . now()->format('Y-m-d') . '.pdf';
            
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate study summary PDF
     */
    public function generateStudySummaryPdf($studyId): JsonResponse
    {
        try {
            $pdf = $this->pdfService()->generateStudySummaryPdf($studyId);
            
            $filename = 'radiology-study-' . $studyId . '-' . now()->format('Y-m-d') . '.pdf';
            
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate patient radiology history PDF
     */
    public function generatePatientHistoryPdf($patientId): JsonResponse
    {
        try {
            $pdf = $this->pdfService()->generatePatientRadiologyHistoryPdf($patientId);
            
            $filename = 'patient-radiology-history-' . $patientId . '-' . now()->format('Y-m-d') . '.pdf';
            
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract basic DICOM tags from file
     */
    private function extractDicomTags($file): array
    {
        // This is a simplified version - in production, use a proper DICOM library
        return [
            'patient_name' => 'Unknown',
            'study_date' => now()->format('Ymd'),
            'study_time' => now()->format('His'),
            'modality' => 'Unknown',
            'series_description' => 'Unknown'
        ];
    }

    /**
     * Get radiology technicians
     */
    public function getTechnicians(): JsonResponse
    {
        $technicians = RadiologyTechnician::where('is_active', true)
                                        ->orderBy('name')
                                        ->get();

        return response()->json([
            'success' => true,
            'data' => $technicians
        ]);
    }

    /**
     * Get radiologists
     */
    public function getRadiologists(): JsonResponse
    {
        $radiologists = \App\Models\User::whereHas('roles', function ($query) {
                $query->where('name', 'radiologist');
            })
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get()
            ->map(function ($user) {
                $fullName = trim($user->first_name . ' ' . $user->last_name);
                if (empty($fullName)) {
                    $fullName = $user->name;
                }
                
                return [
                    'id' => $user->id,
                    'full_name' => $fullName,
                    'name' => $fullName,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $radiologists
        ]);
    }

    /**
     * Get radiology studies with filters
     */
    public function getStudies(Request $request): JsonResponse
    {
        $branchId = $this->resolveUserBranchId('view_radiology_studies');

        $query = RadiologyStudy::with([
            'request.patient',
            'request.doctor',
            'modality',
            'equipment',
            'technician.user',
            'radiologist',
            'report',
            'series.images'
        ])->whereHas('request', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
            $this->applyDoctorRadiologyScope($q);
        });

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('modality_id')) {
            $query->where('modality_id', $request->modality_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('request.patient', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('patient_number', 'like', "%{$search}%");
            });
        }

        $studies = $query->orderBy('id', 'desc')
                        ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $studies->items(),
            'meta' => [
                'current_page' => $studies->currentPage(),
                'last_page' => $studies->lastPage(),
                'per_page' => $studies->perPage(),
                'total' => $studies->total()
            ]
        ]);
    }

    /**
     * Get radiology reports with filters
     */
    public function getReports(Request $request): JsonResponse
    {
        $reportPermission = auth()->user()->can('view_radiology_reports')
            ? 'view_radiology_reports'
            : 'view_radiology_results';
        $branchId = $this->resolveUserBranchId($reportPermission);

        $query = RadiologyReport::with([
            'study.request.patient',
            'study.modality',
            'radiologist'
        ])->whereHas('study.request', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
            $this->applyDoctorRadiologyScope($q);
        });

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('study.request.patient', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('patient_number', 'like', "%{$search}%");
            });
        }

        $reports = $query->orderBy('id', 'desc')
                        ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total()
            ]
        ]);
    }

    /**
     * Get a single radiology request by ID
     */
    public function showRequest($id): JsonResponse
    {
        try {
            $request = RadiologyRequest::with([
                'patient',
                'doctor',
                'modality',
                'department',
                'technician.user',
                'radiologist',
                'study.report',
                'study.series.images'
            ])->findOrFail($id);

            try {
                $this->assertRadiologyRequestAccess($request);
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Access denied',
                ], $e->getStatusCode());
            }

            return response()->json([
                'success' => true,
                'data' => $request,
                'message' => 'Radiology request retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve radiology request',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get a single radiology study by ID
     */
    public function showStudy($id): JsonResponse
    {
        try {
            $study = RadiologyStudy::with([
                'request.patient',
                'request.doctor',
                'modality',
                'equipment',
                'technician.user',
                'radiologist',
                'report',
                'series.images'
            ])->findOrFail($id);

            $this->assertRadiologyStudyAccess($study);

            $studyData = $study->toArray();
            if (!empty($studyData['series'])) {
                foreach ($studyData['series'] as &$series) {
                    if (!empty($series['images'])) {
                        foreach ($series['images'] as &$image) {
                            $imageModel = $study->series->flatMap->images->firstWhere('id', $image['id']);
                            if ($imageModel) {
                                $image['file_url'] = route('api.radiology.images.file', $imageModel->id);
                                $image['exists'] = $imageModel->exists();
                            }
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $studyData,
                'message' => 'Radiology study retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve radiology study',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get a single radiology report by ID
     */
    public function showReport($id): JsonResponse
    {
        try {
            $report = RadiologyReport::with([
                'study.request.patient',
                'study.request.doctor',
                'study.modality',
                'radiologist'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Radiology report retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve radiology report',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a radiology report
     */
    public function updateReport(Request $request, $id): JsonResponse
    {
        try {
            $report = RadiologyReport::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'findings' => 'nullable|string',
                'impression' => 'nullable|string',
                'recommendations' => 'nullable|string',
                'status' => 'nullable|in:draft,pending_review,approved,amended',
                'sign' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Handle signing
            if ($request->has('sign') && $request->sign) {
                $data['signed_at'] = now();
                $data['signed_by'] = auth()->id();
                $data['status'] = 'approved';
            }

            $report->update($data);

            return response()->json([
                'success' => true,
                'data' => $report->load(['study.request.patient', 'study.modality', 'radiologist']),
                'message' => 'Radiology report updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update radiology report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enforce branch and role access for a radiology request.
     */
    protected function pdfService(): RadiologyPdfService
    {
        return app(RadiologyPdfService::class);
    }

    /**
     * Block clinical doctors from radiology staff management endpoints.
     */
    protected function assertRadiologyManagementAccess(): void
    {
        $user = auth()->user();

        if ($user->hasRole('doctor') && !$this->userHasRadiologyStaffPermissions()) {
            abort(403, 'Doctors can only create radiology requests and view results for their own patients.');
        }
    }

    /**
     * Allow consultation ordering lookups without radiology admin access.
     */
    protected function assertRadiologyLookupAccess(): void
    {
        $user = auth()->user();

        if ($user->can('manage_radiology_setup') || $user->can('view_radiology_requests')) {
            return;
        }

        if ($user->can('create_radiology_requests') || $user->can('view_radiology_results')) {
            return;
        }

        abort(403, 'Insufficient permissions to access radiology reference data.');
    }
}
