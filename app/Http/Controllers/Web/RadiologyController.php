<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\RadiologyRequest;
use App\Models\RadiologyStudy;
use App\Models\RadiologyReport;
use App\Models\ImagingModality;
use App\Models\RadiologyDepartment;
use App\Models\RadiologyEquipment;
use App\Models\RadiologyTechnician;
use App\Models\ContrastAgent;
use App\Models\RadiationDose;
use App\Models\RadiologyProtocol;
use App\Models\Patient;
use App\Models\User;
use App\Services\RadiologyPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RadiologyController extends Controller
{
    use ExportsListData, ResolvesUserBranch;

    protected function pdfService(): RadiologyPdfService
    {
        return app(RadiologyPdfService::class);
    }

    /**
     * Display a listing of radiology requests
     */
    public function index(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_radiology_requests');

        $query = RadiologyRequest::with([
            'patient',
            'doctor',
            'modality',
            'department',
            'technician',
            'radiologist',
            'study'
        ])->where('branch_id', $branchId);

        $this->applyDoctorRadiologyScope($query);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('modality_id')) {
            $query->where('modality_id', $request->modality_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('radiologist_id')) {
            $query->where('radiologist_id', $request->radiologist_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_number', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('id', 'desc')->paginate(20);

        // Get filter options
        $modalities = ImagingModality::where('is_active', true)->orderBy('name')->get();
        $departments = RadiologyDepartment::where('is_active', true)->orderBy('name')->get();
        $technicians = RadiologyTechnician::where('radiology_technicians.is_active', true)
            ->with('user')
            ->join('users', 'radiology_technicians.user_id', '=', 'users.id')
            ->orderBy('users.first_name')
            ->select('radiology_technicians.*')
            ->get();
        $radiologists = User::whereHas('roles', function ($q) {
            $q->where('name', 'radiologist');
        })->orderBy('first_name')->get();

        return view('radiology.index', compact(
            'requests',
            'modalities',
            'departments',
            'technicians',
            'radiologists'
        ));
    }

    /**
     * Show the form for creating a new radiology request
     */
    public function create()
    {
        $branchId = $this->resolveUserBranchId('create_radiology_requests');
        $patients = Patient::where('branch_id', $branchId)->orderBy('first_name')->get();
        
        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['doctor', 'consultant', 'radiologist']);
            })->orderBy('first_name')->get();
        }
        
        $modalities = ImagingModality::where('is_active', true)->orderBy('name')->get();
        $departments = RadiologyDepartment::where('is_active', true)->orderBy('name')->get();

        return view('radiology.create', compact(
            'patients',
            'doctors',
            'modalities',
            'departments'
        ));
    }

    /**
     * Store a newly created radiology request
     */
    public function store(Request $request)
    {
        try {
            // SECURITY: If user is a doctor, force doctor_id to be their own ID
            if (auth()->user()->hasRole('doctor')) {
                $request->merge(['doctor_id' => auth()->id()]);
            }

            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'doctor_id' => 'required|exists:users,id',
                'consultation_id' => 'nullable|exists:consultations,id',
                'modality_id' => 'required|exists:imaging_modalities,id',
                'department_id' => 'required|exists:radiology_departments,id',
                'clinical_history' => 'required|string',
                'clinical_question' => 'required|string',
                'indication' => 'required|string',
                'priority' => 'required|in:routine,urgent,stat,emergency',
                'scheduled_date' => 'nullable|date|after_or_equal:today',
                'scheduled_time' => 'nullable|date_format:H:i'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $branchId = $this->resolveUserBranchId('create_radiology_requests');
            $patient = Patient::findOrFail($request->patient_id);
            if ((int) $patient->branch_id !== $branchId) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Patient does not belong to your branch.');
            }

            $data = $validator->validated();
            $data['request_number'] = 'RAD-' . strtoupper(Str::random(8));
            $data['requested_date'] = now();
            $data['status'] = 'requested';
            $data['branch_id'] = $branchId;
            $data['billing_status'] = 'pending';
            $modality = ImagingModality::find($data['modality_id']);
            if ($modality) {
                $data['billing_amount'] = $modality->base_cost;
            }

            $radiologyRequest = RadiologyRequest::create($data);

            // Update workflow metadata if consultation exists
            if (($data['consultation_id'] ?? null) && $radiologyRequest->consultation && $radiologyRequest->consultation->visit) {
                $instance = \App\Models\WorkflowInstance::where('entity_type', 'visit')
                    ->where('entity_id', $radiologyRequest->consultation->visit->id)
                    ->where('is_active', true)
                    ->first();

                if ($instance) {
                    $metadata = $instance->metadata ?? [];
                    $metadata['imaging_ordered'] = true;
                    $instance->update(['metadata' => $metadata]);
                }
            }

            // Redirect based on source
            if ($request->has('from_consultation') && ($data['consultation_id'] ?? null)) {
                return redirect()->route('consultations.show', $data['consultation_id'])
                    ->with('success', 'Radiology request created successfully.');
            }

            if ($request->has('from_walk_in')) {
                return redirect()->route('walk-ins.index')
                    ->with('success', 'Radiology request created successfully.');
            }

            return redirect()->route('radiology.index')
                ->with('success', 'Radiology request created successfully.');
        } catch (\Exception $e) {
            \Log::error('Error creating radiology request: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating radiology request: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified radiology request
     */
    public function show(RadiologyRequest $radiology)
    {
        $this->assertRadiologyRequestAccess($radiology);

        $radiology->load([
            'patient',
            'doctor',
            'modality',
            'department',
            'technician',
            'radiologist',
            'study.report',
            'study.images'
        ]);

        return view('radiology.show', compact('radiology'));
    }

    /**
     * Show the form for editing the specified radiology request
     */
    public function edit(RadiologyRequest $radiology)
    {
        $this->assertRadiologyRequestAccess($radiology);

        $branchId = $this->resolveUserBranchId('view_radiology_requests');
        $patients = Patient::where('branch_id', $branchId)->orderBy('first_name')->get();
        
        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['doctor', 'consultant']);
            })->orderBy('first_name')->get();
        }
        $modalities = ImagingModality::where('is_active', true)->orderBy('name')->get();
        $departments = RadiologyDepartment::where('is_active', true)->orderBy('name')->get();
        $technicians = RadiologyTechnician::where('is_active', true)->with('user')->get()->sortBy('user.first_name');
        $radiologists = User::whereHas('roles', function ($q) {
            $q->where('name', 'radiologist');
        })->orderBy('first_name')->get();

        return view('radiology.edit', compact(
            'radiology',
            'patients',
            'doctors',
            'modalities',
            'departments',
            'technicians',
            'radiologists'
        ));
    }

    /**
     * Update the specified radiology request
     */
    public function update(Request $request, RadiologyRequest $radiology)
    {
        $this->assertRadiologyRequestAccess($radiology);

        try {
            // SECURITY: Doctors cannot change doctor_id - preserve their ID
            if (auth()->user()->hasRole('doctor')) {
                $request->merge(['doctor_id' => auth()->id()]);
            }

            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'doctor_id' => 'required|exists:users,id',
                'modality_id' => 'required|exists:imaging_modalities,id',
                'department_id' => 'required|exists:radiology_departments,id',
                'clinical_history' => 'required|string',
                'clinical_question' => 'required|string',
                'indication' => 'required|string',
                'priority' => 'required|in:routine,urgent,stat,emergency',
                'status' => 'required|in:requested,scheduled,in_progress,completed,cancelled,rejected',
                'scheduled_date' => 'nullable|date',
                'scheduled_time' => 'nullable|date_format:H:i',
                'technician_id' => 'nullable|exists:radiology_technicians,id',
                'radiologist_id' => 'nullable|exists:users,id',
                'technician_notes' => 'nullable|string',
                'rejection_reason' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $radiology->update($validator->validated());

            return redirect()->route('radiology.show', $radiology)
                ->with('success', 'Radiology request updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating radiology request: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'radiology_request_id' => $radiology->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update radiology request. Please try again.');
        }
    }

    /**
     * Remove the specified radiology request
     */
    public function destroy(RadiologyRequest $radiology)
    {
        $this->assertRadiologyRequestAccess($radiology);

        try {
            // Check if radiology request has studies or reports
            $hasStudies = $radiology->studies()->count() > 0;
            $hasReports = $radiology->reports()->count() > 0;
            
            if ($hasStudies || $hasReports) {
                return back()
                    ->with('error', 'Cannot delete radiology request with existing studies or reports.');
            }
            
            $radiology->delete();

            return redirect()->route('radiology.index')
                ->with('success', 'Radiology request deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting radiology request: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'radiology_request_id' => $radiology->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete radiology request. Please try again.');
        }
    }

    /**
     * Start a radiology study
     */
    public function startStudy(Request $request, RadiologyRequest $radiology)
    {
        $this->assertRadiologyRequestAccess($radiology);

        $validator = Validator::make($request->all(), [
            'equipment_id' => 'nullable|exists:radiology_equipment,id',
            'study_description' => 'required|string',
            'study_notes' => 'nullable|string',
            'technique_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['study_uid'] = '1.2.826.0.1.3680043.8.498.' . time();
        $data['request_id'] = $radiology->id;
        $data['patient_id'] = $radiology->patient_id;
        $data['modality_id'] = $radiology->modality_id;
        $data['status'] = 'in_progress';
        $data['study_date'] = now();

        // Handle nullable equipment_id - use fallback equipment if empty (equipment_id is required in DB)
        if (empty($data['equipment_id'])) {
            // Use first available equipment as fallback since equipment_id is required in database
            $fallbackEquipment = RadiologyEquipment::where('is_active', true)->first();
            if ($fallbackEquipment) {
                $data['equipment_id'] = $fallbackEquipment->id;
            } else {
                // If no equipment exists, this is a system configuration error
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'No active radiology equipment available. Please configure equipment first.');
            }
        }

        // Automatically assign the logged-in user as the radiologist
        $data['radiologist_id'] = auth()->id();
        
        // Automatically assign technician: find RadiologyTechnician record for logged-in user
        $technician = RadiologyTechnician::where('user_id', auth()->id())->first();
        $data['technician_id'] = $technician ? $technician->id : null;

        $study = RadiologyStudy::create($data);

        // Update request status and assign radiologist
        $updateData = [
            'status' => 'in_progress',
            'radiologist_id' => auth()->id()
        ];

        $radiology->update($updateData);

        return redirect()->route('radiology.studies.show', $study)
            ->with('success', 'Radiology study started successfully.');
    }

    /**
     * Get radiologists for dropdown
     */
    public function getRadiologists()
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
     * Complete a radiology study
     */
    public function completeStudy(Request $request, RadiologyStudy $study)
    {
        $this->assertRadiologyStudyAccess($study);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:completed,needs_review',
            'study_notes' => 'nullable|string',
            'technique_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['completed_date'] = now();

        $study->update($data);

        $study->request->update(['status' => 'completed']);

        return redirect()->route('radiology.studies.show', $study)
            ->with('success', 'Radiology study completed successfully.');
    }

    /**
     * Show radiology studies
     */
    public function studies(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_radiology_studies');

        $query = RadiologyStudy::with([
            'request.patient',
            'request.doctor',
            'modality',
            'equipment',
            'technician',
            'radiologist',
            'report'
        ])->whereHas('request', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
            $this->applyDoctorRadiologyScope($q);
        });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('modality_id')) {
            $query->where('modality_id', $request->modality_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('request.patient', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_number', 'like', "%{$search}%");
            });
        }

        $studies = $query->orderBy('id', 'desc')->paginate(20);

        $modalities = ImagingModality::where('is_active', true)->orderBy('name')->get();

        return view('radiology.studies.index', compact('studies', 'modalities'));
    }

    /**
     * Show specific radiology study
     */
    public function showStudy(RadiologyStudy $study)
    {
        $this->assertRadiologyStudyAccess($study);

        $study->load([
            'request.patient',
            'request.doctor',
            'modality',
            'equipment',
            'technician',
            'radiologist',
            'report',
            'series.images'
        ]);

        return view('radiology.studies.show', compact('study'));
    }

    /**
     * Upload images to a radiology study
     */
    public function uploadImages(Request $request, RadiologyStudy $study)
    {
        $this->assertRadiologyStudyAccess($study);

        if (!$this->canUploadRadiologyImages()) {
            abort(403, 'You do not have permission to upload radiology images.');
        }

        if ($study->status !== 'in_progress') {
            return redirect()->back()
                ->with('error', 'Images can only be uploaded while the study is in progress.');
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1',
            'images.*' => 'required|file|mimes:jpg,jpeg,png,gif,dcm,dicom|max:50000', // 50MB max per file
            'series_description' => 'required|string',
            'body_part_examined' => 'nullable|string',
            'view_position' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        \DB::beginTransaction();

        try {
            $series = \App\Models\RadiologySeries::create([
                'series_uid' => '1.2.826.0.1.3680043.8.498.' . time() . '.' . rand(1000, 9999),
                'study_id' => $study->id,
                'series_number' => $study->series()->count() + 1,
                'series_description' => $request->series_description,
                'body_part_examined' => $request->body_part_examined,
                'view_position' => $request->view_position,
                'number_of_instances' => count($request->file('images'))
            ]);

            $uploadedImages = [];
            $errors = [];

            foreach ($request->file('images') as $index => $file) {
                try {
                    $image = \App\Models\RadiologyImage::storeUploadedFile(
                        $series,
                        $file,
                        $index + 1
                    );

                    $uploadedImages[] = $image;

                } catch (\Exception $e) {
                    \Log::error('Error uploading radiology image', [
                        'study_id' => $study->id,
                        'series_id' => $series->id ?? null,
                        'file_index' => $index,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $errors[] = "File " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            // If no files were successfully uploaded, rollback
            if (empty($uploadedImages)) {
                \DB::rollBack();
                return redirect()->back()
                    ->with('error', 'Failed to upload any images. Errors: ' . implode(', ', $errors))
                    ->withInput();
            }

            // If some files failed, commit but show warning
            if (!empty($errors)) {
                \DB::commit();
                return redirect()->route('radiology.studies.show', $study)
                    ->with('warning', count($uploadedImages) . ' image(s) uploaded successfully, but ' . count($errors) . ' failed: ' . implode(', ', $errors));
            }

            \DB::commit();

            return redirect()->route('radiology.studies.show', $study)
                ->with('success', count($uploadedImages) . ' image(s) uploaded successfully.');

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Critical error in radiology image upload', [
                'study_id' => $study->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error uploading images: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form to create radiology report
     */
    public function createReport(RadiologyStudy $study)
    {
        if ($study->hasReport()) {
            return redirect()->route('radiology.reports.show', $study->report)
                ->with('info', 'Report already exists for this study.');
        }

        $study->load(['request.patient', 'modality', 'images']);

        return view('radiology.reports.create', compact('study'));
    }

    /**
     * Store radiology report
     */
    public function storeReport(Request $request, RadiologyStudy $study)
    {
        $validator = Validator::make($request->all(), [
            'findings' => 'required|string',
            'impression' => 'required|string',
            'recommendations' => 'nullable|string',
            'status' => 'required|in:draft,preliminary,final',
            'selected_images' => 'nullable|array',
            'selected_images.*' => 'exists:radiology_images,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['study_id'] = $study->id;
        $data['radiologist_id'] = auth()->id();
        $data['dictated_date'] = now();

        $report = RadiologyReport::create($data);

        return redirect()->route('radiology.reports.show', $report)
            ->with('success', 'Radiology report created successfully with ' . (count($data['selected_images'] ?? []) > 0 ? count($data['selected_images']) . ' selected images.' : 'no images selected.'));
    }

    /**
     * Show radiology reports
     */
    public function reports(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_radiology_reports');

        $query = RadiologyReport::with([
            'study.request.patient',
            'study.modality',
            'radiologist'
        ])->whereHas('study.request', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
            $this->applyDoctorRadiologyScope($q);
        });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('study.request.patient', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_number', 'like', "%{$search}%");
            });
        }

        $reports = $query->orderBy('id', 'desc')->paginate(20);

        return view('radiology.reports.index', compact('reports'));
    }

    /**
     * Show specific radiology report
     */
    public function showReport(RadiologyReport $report)
    {
        $report->loadMissing('study.request');
        if ($report->study?->request) {
            $this->assertRadiologyRequestAccess($report->study->request);
        }

        $report->load([
            'study.request.patient',
            'study.request.doctor',
            'study.modality',
            'radiologist'
        ]);

        return view('radiology.reports.show', compact('report'));
    }

    /**
     * Edit radiology report
     */
    public function editReport(RadiologyReport $report)
    {
        $report->load(['study.request.patient', 'study.modality']);

        return view('radiology.reports.edit', compact('report'));
    }

    /**
     * Update radiology report
     */
    public function updateReport(Request $request, RadiologyReport $report)
    {
        // Handle signing separately from updating report content
        if ($request->has('signature_confirmation')) {
            $validator = Validator::make($request->all(), [
                'signature_confirmation' => 'required|accepted'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Sign the report
            $report->update([
                'signed_date' => now()
            ]);

            return redirect()->route('radiology.reports.show', $report)
                ->with('success', 'Radiology report signed successfully.');
        }

        // Handle regular report update
        $validator = Validator::make($request->all(), [
            'findings' => 'required|string',
            'impression' => 'required|string',
            'recommendations' => 'nullable|string',
            'status' => 'required|in:draft,preliminary,final,amended',
            'amendment_reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        if ($data['status'] === 'amended') {
            $data['amendment_reason'] = $request->amendment_reason;
        }

        $report->update($data);

        return redirect()->route('radiology.reports.show', $report)
            ->with('success', 'Radiology report updated successfully.');
    }

    /**
     * Generate PDF for radiology report
     */
    public function generateReportPdf(RadiologyReport $report)
    {
        return $this->pdfService()->generateRadiologyReportPdf($report->id)->stream('radiology-report-' . $report->id . '.pdf');
    }

    /**
     * Generate PDF for radiology study summary
     */
    public function generateStudyPdf(RadiologyStudy $study)
    {
        return $this->pdfService()->generateStudySummaryPdf($study->id)->stream('radiology-study-' . $study->id . '.pdf');
    }

    /**
     * Display DICOM viewer for study
     */
    public function viewer(RadiologyStudy $study)
    {
        $this->assertRadiologyStudyAccess($study);

        $study->load([
            'patient:id,first_name,last_name,patient_number',
            'request.patient:id,first_name,last_name,patient_number',
            'request.doctor:id,first_name,last_name',
            'request:id,priority,clinical_question,patient_id',
            'modality:id,name,code',
            'series.images'
        ]);

        if (!$study->patient && $study->request?->patient) {
            $study->setRelation('patient', $study->request->patient);
        }

        return view('radiology.viewer', compact('study'));
    }

    /**
     * Get series images for DICOM viewer (API endpoint)
     */
    public function getSeriesImages($seriesId)
    {
        $series = \App\Models\RadiologySeries::with(['images', 'study.request'])->findOrFail($seriesId);

        if ($series->study) {
            $this->assertRadiologyStudyAccess($series->study);
        }

        return response()->json([
            'success' => true,
            'series_description' => $series->series_description,
            'series_number' => $series->series_number,
            'images' => $series->images->map(function ($image) {
                $fileUrl = route('radiology.images.serve', $image->id);

                return [
                    'id' => $image->id,
                    'instance_number' => $image->instance_number,
                    'file_path' => $image->file_path,
                    'file_url' => $fileUrl,
                    'file_name' => $image->file_name,
                    'mime_type' => $image->mime_type,
                    'exists' => $image->exists(),
                ];
            })
        ]);
    }

    /**
     * Create radiology request from walk-in visit
     */
    public function createFromWalkInVisit(\App\Models\Visit $visit)
    {
        // Ensure this is a radiology-only visit or general walk-in
        if (!in_array($visit->visit_type, ['RadiologyOnly', 'WalkIn'])) {
            return redirect()->back()
                ->with('error', 'This visit is not a radiology walk-in visit.');
        }

        $modalities = ImagingModality::where('is_active', true)->orderBy('name')->get();
        $departments = RadiologyDepartment::where('is_active', true)->orderBy('name')->get();
        
        // SECURITY: If user is a doctor, only show themselves
        if (auth()->user()->hasRole('doctor')) {
            $doctors = collect([auth()->user()]);
        } else {
            $doctors = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['doctor', 'consultant', 'radiologist']);
            })->orderBy('first_name')->get();
        }

        return view('radiology.create-from-walk-in', compact('visit', 'modalities', 'departments', 'doctors'));
    }

    /**
     * Store radiology request from walk-in visit
     */
    public function storeFromWalkInVisit(Request $request, \App\Models\Visit $visit)
    {
        // SECURITY: If user is a doctor, force doctor_id to be their own ID
        if (auth()->user()->hasRole('doctor')) {
            $request->merge(['doctor_id' => auth()->id()]);
        }

        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'radiologist_id' => 'nullable|exists:users,id',
            'modality_id' => 'required|exists:imaging_modalities,id',
            'department_id' => 'required|exists:radiology_departments,id',
            'clinical_history' => 'required|string',
            'clinical_question' => 'required|string',
            'indication' => 'required|string',
            'priority' => 'required|in:routine,urgent,stat,emergency',
            'scheduled_date' => 'nullable|date|after_or_equal:today',
            'scheduled_time' => 'nullable|date_format:H:i'
        ]);

        // Ensure this is a radiology walk-in visit
        if (!in_array($visit->visit_type, ['RadiologyOnly', 'WalkIn'])) {
            return redirect()->back()
                ->with('error', 'This visit is not a radiology walk-in visit.');
        }

        \DB::beginTransaction();

        try {
            $branchId = $this->resolveUserBranchId('create_radiology_requests');
            $modality = ImagingModality::find($validated['modality_id']);

            $radiologyRequest = RadiologyRequest::create([
                'patient_id' => $visit->patient_id,
                'consultation_id' => null,
                'branch_id' => $visit->branch_id ?? $branchId,
                'doctor_id' => $validated['doctor_id'],
                'radiologist_id' => $validated['radiologist_id'] ?? null,
                'modality_id' => $validated['modality_id'],
                'billing_status' => 'pending',
                'billing_amount' => $modality?->base_cost,
                'department_id' => $validated['department_id'],
                'clinical_history' => $validated['clinical_history'],
                'clinical_question' => $validated['clinical_question'],
                'indication' => $validated['indication'],
                'priority' => $validated['priority'],
                'scheduled_date' => $validated['scheduled_date'] ?? null,
                'scheduled_time' => $validated['scheduled_time'] ? now()->parse($validated['scheduled_time'])->format('H:i:s') : null,
                'request_number' => 'RAD-' . strtoupper(Str::random(8)),
                'requested_date' => now(),
                'status' => 'requested',
            ]);

            // Update queue status to serving if exists
            $visit->queues()->where('queue_type', 'Radiology')->update([
                'status' => 'serving',
                'serving_at' => now(),
                'served_by' => auth()->id()
            ]);

            \DB::commit();

            return redirect()->route('walk-ins.index')
                ->with('success', 'Radiology request created successfully! Request #' . $radiologyRequest->request_number);

        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()
                ->with('error', 'Error creating radiology request: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Serve radiology image file dynamically
     * This route handles file serving to avoid 403 errors and works with all file locations
     */
    public function serveImage($imageId)
    {
        try {
            $image = \App\Models\RadiologyImage::with(['series.study.request', 'series.study.patient'])->findOrFail($imageId);

            if (!auth()->check() || !auth()->user()->can('view_radiology_studies')) {
                \Log::warning('Unauthorized access attempt to radiology image', [
                    'image_id' => $imageId,
                    'user_id' => auth()->id(),
                ]);
                abort(403, 'You do not have permission to view radiology images.');
            }

            $this->assertRadiologyImageAccess($image);

            $filePath = $image->getFullPath();

            // Log path resolution for debugging
            \Log::debug('Serving radiology image', [
                'image_id' => $imageId,
                'file_path_db' => $image->file_path,
                'resolved_path' => $filePath,
                'file_exists' => file_exists($filePath),
            ]);

            if (!file_exists($filePath) || !is_file($filePath)) {
                \Log::error('Radiology image file not found', [
                    'image_id' => $imageId,
                    'file_path_db' => $image->file_path,
                    'resolved_path' => $filePath,
                    'series_id' => $image->series_id,
                    'study_id' => $image->series->study_id ?? null,
                ]);
                abort(404, 'Image file not found on disk.');
            }

            // Verify file is readable
            if (!is_readable($filePath)) {
                \Log::error('Radiology image file is not readable', [
                    'image_id' => $imageId,
                    'file_path' => $filePath,
                    'permissions' => substr(sprintf('%o', fileperms($filePath)), -4),
                ]);
                abort(500, 'Image file is not accessible.');
            }

            // Determine MIME type
            $mimeType = $image->mime_type;
            if (!$mimeType) {
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $mimeType = match (strtolower($extension)) {
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

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Radiology image not found in database', [
                'image_id' => $imageId,
                'user_id' => auth()->id(),
            ]);
            abort(404, 'Image not found.');
        } catch (\Exception $e) {
            \Log::error('Error serving radiology image', [
                'image_id' => $imageId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'Error serving image: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_radiology_requests');

        $query = RadiologyRequest::with(['patient', 'doctor', 'modality'])
            ->where('branch_id', $branchId);

        $this->applyDoctorRadiologyScope($query);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('modality_id')) {
            $query->where('modality_id', $request->modality_id);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('patient_number', 'like', "%{$search}%"));
        }

        $query->orderBy('id', 'desc');

        return $this->exportFromQuery($request, $query, [
            'Request #' => 'request_number',
            'Patient' => fn ($r) => $r->patient?->full_name ?? '',
            'Patient Number' => fn ($r) => $r->patient?->patient_number ?? '',
            'Modality' => fn ($r) => $r->modality?->name ?? '',
            'Doctor' => fn ($r) => $this->formatExportUserName($r->doctor),
            'Status' => 'status',
            'Priority' => 'priority',
            'Requested At' => fn ($r) => $this->formatExportDate($r->created_at, 'Y-m-d H:i'),
        ], 'radiology-requests', 'view_radiology_requests');
    }

    public function exportReports(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_radiology_reports');

        $query = RadiologyReport::with(['study.request.patient', 'study.modality', 'radiologist'])
            ->whereHas('study.request', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                $this->applyDoctorRadiologyScope($q);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('study.request.patient', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('patient_number', 'like', "%{$search}%"));
        }

        $query->orderBy('id', 'desc');

        return $this->exportFromQuery($request, $query, [
            'Report ID' => 'id',
            'Patient' => fn ($r) => $r->study?->request?->patient?->full_name ?? '',
            'Patient Number' => fn ($r) => $r->study?->request?->patient?->patient_number ?? '',
            'Modality' => fn ($r) => $r->study?->modality?->name ?? '',
            'Radiologist' => fn ($r) => $this->formatExportUserName($r->radiologist),
            'Status' => 'status',
            'Created At' => fn ($r) => $this->formatExportDate($r->created_at, 'Y-m-d H:i'),
        ], 'radiology-reports', 'view_radiology_reports');
    }
}
