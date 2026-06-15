<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\LabRequest;
use App\Models\LabResult;
use App\Models\LabTestType;
use App\Models\LabTestTemplate;
use App\Models\LabTestParameter;
use App\Models\LabTestResult;
use App\Models\LabTestCategory;
use App\Models\LabTest;
use App\Models\LabQualityControl;
use App\Models\LabReferenceRange;
use App\Models\LabCriticalValue;
use App\Models\LabDeltaCheckRule;
use App\Models\LabResultComment;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\Queue;
use App\Services\LabPdfService;
use App\Services\DiagnosticReportPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LabController extends Controller
{
    use ResolvesUserBranch, WorkflowNavigation;
    // ========================================
    // LAB REQUESTS MANAGEMENT
    // ========================================

    /**
     * Display a listing of lab requests.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = LabRequest::with(['patient', 'doctor', 'technician', 'results', 'template'])
            ->orderBy('id', 'desc');

        // Role-based data filtering
        if ($user->hasRole('patient')) {
            // Patients can only see their own lab requests
            $patient = $user->patient;
            if ($patient) {
                $query->where('patient_id', $patient->id);
            } else {
                // If patient record doesn't exist, return empty result
                $query->where('patient_id', -1); // Impossible ID to return empty
            }
        } elseif ($user->hasRole('lab_technician')) {
            // Lab technicians can see requests from their branch
            if ($user->staffProfile && $user->staffProfile->branch_id) {
                $query->where('branch_id', $user->staffProfile->branch_id);
            }
        } elseif ($user->hasRole(['doctor', 'nurse'])) {
            // Doctors and nurses can see requests from their branch
            if ($user->staffProfile && $user->staffProfile->branch_id) {
                $query->where('branch_id', $user->staffProfile->branch_id);
            }
        }
        // Super admin and other roles can see all requests

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by patient name or request number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($patientQuery) use ($search) {
                      $patientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('patient_number', 'like', "%{$search}%");
                  });
            });
        }

        $labRequests = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $labRequests,
            'message' => 'Lab requests retrieved successfully'
        ]);
    }

    /**
     * Store a newly created lab request.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            // Support both approaches: template_ids (advanced) OR test_type_id (simple, like Web)
            'template_ids' => 'nullable|array|min:1',
            'template_ids.*' => 'required_with:template_ids|exists:lab_test_templates,id',
            'test_type_id' => 'nullable|exists:lab_test_types,id',
            'test_category_id' => 'nullable|exists:lab_test_categories,id',
            'test_type' => 'nullable|string',
            'test_description' => 'nullable|string',
            'clinical_notes' => 'nullable|string',
            'priority' => 'nullable|in:routine,urgent,stat',
            'specimen_type' => 'nullable|string',
            'collection_instructions' => 'nullable|string',
            'special_instructions' => 'nullable|string'
        ]);

        // Ensure we have either template_ids OR test_type_id
        if (empty($request->template_ids) && empty($request->test_type_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Either template_ids or test_type_id must be provided'
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get test type details if test_type_id is provided (Web approach)
            $testType = null;
            $testCategory = null;
            $templateId = null;
            $testTypeName = null;
            $testCategoryName = null;
            
            if ($request->test_type_id) {
                $testType = \App\Models\LabTestType::findOrFail($request->test_type_id);
                $templateId = $testType->getResolvedTemplateId();
                $testTypeName = $testType->test_name;
                
                if ($testType->category_id) {
                    $testCategory = \App\Models\LabTestCategory::find($testType->category_id);
                    $testCategoryName = $testCategory ? $testCategory->name : null;
                }
            } elseif (!empty($request->template_ids)) {
                // Advanced approach: use first template
                $templateId = $request->template_ids[0];
            }

            // Create the lab request
            $labRequestData = [
                'patient_id' => $request->patient_id,
                'consultation_id' => $request->consultation_id,
                'doctor_id' => $request->doctor_id,
                'branch_id' => $request->branch_id,
                'template_id' => $templateId,
                'test_type_id' => $request->test_type_id ?? $testType?->id,
                'test_category_id' => $request->test_category_id ?? $testType?->category_id,
                'test_category_name' => $testCategoryName,
                'test_type_name' => $testTypeName,
                'test_type' => $request->test_type ?? $testTypeName ?? 'Lab Test',
                'test_description' => $request->test_description ?? $testType?->description ?? null,
                'clinical_notes' => $request->clinical_notes,
                'priority' => $request->priority ?? 'routine',
                'specimen_type' => $request->specimen_type ?? $testType?->specimen_type ?? null,
                'collection_instructions' => $request->collection_instructions ?? $testType?->collection_instructions ?? null,
                'special_instructions' => $request->special_instructions,
                'status' => 'pending',
                'created_by' => auth()->id()
            ];

            $labRequest = LabRequest::create($labRequestData);

            // Add all templates to the request (if using template_ids approach)
            if (!empty($request->template_ids)) {
                $labRequest->addTemplates($request->template_ids);
            } elseif ($templateId) {
                // If using test_type_id approach, add the template from test type
                $labRequest->addTemplates([$templateId]);
            }

            DB::commit();

            // Initialize workflow for lab request if not already initialized
            if (!$labRequest->workflowInstance) {
                $this->initializeWorkflowForEntity($labRequest, 'Lab Test');
            }

            // Complete workflow step
            if ($labRequest->workflowInstance) {
                $this->completeWorkflowStep($labRequest, 'laboratory_testing', [
                    'lab_request_id' => $labRequest->id,
                    'test_type' => $request->test_type ?? $testTypeName ?? 'Lab Test',
                ]);
            }

            // Get workflow next step suggestion
            $response = [
                'success' => true,
                'data' => $labRequest->load(['patient', 'doctor', 'templates', 'templateAssignments']),
                'message' => 'Lab request created successfully'
            ];

            if ($labRequest->workflowInstance) {
                $workflowResponse = $this->getNextStepResponse($labRequest, 'Lab request created successfully');
                $response['workflow'] = $workflowResponse->getData(true)['workflow'] ?? null;
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lab request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified lab request.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view_lab_requests')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view lab requests.',
            ], 403);
        }

        $labRequest = LabRequest::with([
            'patient', 
            'doctor', 
            'technician', 
            'results.parameter',
            'results.performedBy',
            'results.verifiedBy',
            'results.approvedBy',
            'template',
            'templates.parameters',
            'templateAssignments.assignedTechnician',
            'templateAssignments.template'
        ])->findOrFail($id);

        $this->assertLabRequestAccess($labRequest);

        return response()->json([
            'success' => true,
            'data' => $labRequest,
            'message' => 'Lab request retrieved successfully'
        ]);
    }

    /**
     * Get single lab request details for patient (mobile app)
     */
    public function getLabRequestDetails($requestId): JsonResponse
    {
        $user = auth()->user();
        
        // Get the patient record associated with this user
        $patient = $user->patient;
        
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found for this user'
            ], 404);
        }
        
        $labRequest = LabRequest::with([
            'patient', 
            'doctor', 
            'technician', 
            'results' => function($q) {
                // Only load results that are verified and approved
                $q->whereNotNull('result_verified_at')
                  ->whereNotNull('result_approved_at')
                  ->whereNotNull('result_entered_at')
                  ->with(['parameter', 'performedBy', 'verifiedBy', 'approvedBy'])
                  ->orderBy('parameter_id');
            },
            'template',
            'testType.template',
            'testType.category',
            'testCategory'
        ])
        ->where('id', $requestId)
        ->where('patient_id', $patient->id)
        ->where('status', 'completed')
        ->whereHas('results', function($q) {
            // Only include lab requests that have at least one verified and approved result
            $q->whereNotNull('result_verified_at')
              ->whereNotNull('result_approved_at')
              ->whereNotNull('result_entered_at');
        })
        ->first();

        if (!$labRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Lab request not found, results not yet available, or you do not have access to this lab result'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $labRequest
        ]);
    }

    /**
     * Update the specified lab request.
     */
    public function update(Request $request, $id)
    {
        $labRequest = LabRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'technician_id' => 'sometimes|exists:users,id',
            'template_id' => 'sometimes|nullable|exists:lab_test_templates,id',
            'clinical_notes' => 'sometimes|string',
            'special_instructions' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only([
            'status', 'technician_id', 'clinical_notes', 'special_instructions', 'template_id'
        ]);
        
        $labRequest->update($updateData);
        
        // If template_id is provided, ensure it's also added to lab_request_templates
        if ($request->has('template_id') && !empty($request->template_id)) {
            $labRequest->addTemplates([$request->template_id]);
        }

        return response()->json([
            'success' => true,
            'data' => $labRequest->load(['patient', 'doctor', 'technician']),
            'message' => 'Lab request updated successfully'
        ]);
    }

    /**
     * Remove the specified lab request.
     */
    public function destroy($id)
    {
        $labRequest = LabRequest::findOrFail($id);
        $labRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lab request deleted successfully'
        ]);
    }

    // ========================================
    // TEST TEMPLATES MANAGEMENT
    // ========================================

    /**
     * Get all test templates with pagination
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $query = LabTestTemplate::with(['parameters' => function($q) {
            $q->where('is_active', true)->orderBy('sort_order');
        }]);

        // Filter by category
        if ($request->has('category') && !empty($request->category)) {
            $query->where('category', $request->category);
        }

        // Filter by test type
        if ($request->has('test_type') && !empty($request->test_type)) {
            $query->where('test_type', $request->test_type);
        }

        // Filter by template bank
        if ($request->has('template_bank') && $request->boolean('template_bank')) {
            $query->where('is_template_bank', true);
        }

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('template_name', 'like', "%{$search}%")
                  ->orWhere('template_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->active()
            ->orderBy('template_name')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * Get a specific test template with parameters
     */
    public function getTemplate($id): JsonResponse
    {
        $template = LabTestTemplate::with([
            'parameters' => function($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            },
            'createdBy',
            'updatedBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $template
        ]);
    }

    /**
     * Get template parameters
     */
    public function getTemplateParameters($id): JsonResponse
    {
        $parameters = LabTestParameter::where('template_id', $id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parameters
        ]);
    }

    /**
     * Create a new test template
     */
    public function createTemplate(Request $request): JsonResponse
    {
        \Log::info('createTemplate method started', [
            'user_id' => auth()->id(),
            'data' => $request->all()
        ]);
        
        $validator = Validator::make($request->all(), [
            'template_code' => 'required|string|max:50|unique:lab_test_templates',
            'template_name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'template_content' => 'nullable|string',
            'quantitative_parameters' => 'nullable|array',
            'qualitative_parameters' => 'nullable|array',
            'template_type' => 'required|in:qualitative,quantitative,narrative,combined',
            'specimen_type' => 'required|string|max:100',
            'parameters' => 'nullable|array',
            'parameters.*.parameter_code' => 'required_with:parameters|string|max:50',
            'parameters.*.parameter_name' => 'required_with:parameters|string|max:255',
            'parameters.*.data_type' => 'required_with:parameters|in:numeric,text,boolean,date,time,datetime',
            'parameters.*.input_type' => 'required_with:parameters|in:text,number,select,radio,checkbox,textarea,rich_text',
            'parameters.*.unit' => 'nullable|string|max:20',
            'parameters.*.is_required' => 'boolean',
            'parameters.*.is_critical' => 'boolean',
            'parameters.*.allows_delta_check' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create template
            $template = LabTestTemplate::create([
                'template_code' => $request->template_code,
                'template_name' => $request->template_name,
                'category' => $request->category,
                'subcategory' => $request->subcategory,
                'description' => $request->description,
                'template_content' => $request->template_content,
                'quantitative_parameters' => $request->quantitative_parameters ?? [],
                'qualitative_parameters' => $request->qualitative_parameters ?? [],
                'template_type' => $request->template_type,
                'specimen_type' => $request->specimen_type,
                'collection_instructions' => $request->collection_instructions ?? [],
                'preparation_instructions' => $request->preparation_instructions ?? [],
                'storage_requirements' => $request->storage_requirements ?? [],
                'transport_requirements' => $request->transport_requirements ?? [],
                'methodology' => $request->methodology,
                'equipment_required' => $request->equipment_required,
                'routine_tat_hours' => $request->routine_tat_hours ?? 24,
                'urgent_tat_hours' => $request->urgent_tat_hours ?? 4,
                'stat_tat_hours' => $request->stat_tat_hours ?? 1,
                'cost' => $request->cost,
                'nhis_cost' => $request->nhis_cost,
                'nhis_covered' => $request->boolean('nhis_covered'),
                'requires_doctor_approval' => $request->boolean('requires_doctor_approval'),
                'requires_consultant_review' => $request->boolean('requires_consultant_review'),
                'requires_pathologist_review' => $request->boolean('requires_pathologist_review'),
                'is_active' => true,
                'is_template_bank' => $request->boolean('is_template_bank'),
                'template_source' => $request->template_source,
                'created_by' => auth()->id()
            ]);

            // Create parameters if provided
            if ($request->has('parameters') && is_array($request->parameters)) {
                foreach ($request->parameters as $index => $param) {
                    LabTestParameter::create([
                        'template_id' => $template->id,
                        'parameter_code' => $param['parameter_code'],
                        'parameter_name' => $param['parameter_name'],
                        'description' => $param['description'] ?? null,
                        'data_type' => $param['data_type'],
                        'input_type' => $param['input_type'],
                        'input_options' => $param['input_options'] ?? null,
                        'unit' => $param['unit'] ?? null,
                        'decimal_places' => $param['decimal_places'] ?? 0,
                        'is_required' => $param['is_required'] ?? true,
                        'is_critical' => $param['is_critical'] ?? false,
                        'allows_delta_check' => $param['allows_delta_check'] ?? false,
                        'validation_rules' => $param['validation_rules'] ?? null,
                        'sort_order' => $index + 1,
                        'is_active' => true
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test template created successfully',
                'data' => $template->load('parameters')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('createTemplate error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a test template
     */
    public function updateTemplate(Request $request, $id): JsonResponse
    {
        $template = LabTestTemplate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'template_name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'template_content' => 'nullable|string',
            'quantitative_parameters' => 'nullable|array',
            'qualitative_parameters' => 'nullable|array',
            'test_type' => 'sometimes|required|in:qualitative,quantitative,narrative,combined',
            'specimen_type' => 'sometimes|required|string|max:100',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template->update(array_merge(
                $request->only([
                    'template_name', 'category', 'subcategory', 'description',
                    'template_content', 'quantitative_parameters', 'qualitative_parameters',
                    'test_type', 'specimen_type', 'collection_instructions',
                    'preparation_instructions', 'storage_requirements',
                    'transport_requirements', 'methodology', 'equipment_required',
                    'routine_tat_hours', 'urgent_tat_hours', 'stat_tat_hours',
                    'cost', 'nhis_cost', 'nhis_covered', 'requires_doctor_approval',
                    'requires_consultant_review', 'requires_pathologist_review',
                    'is_active', 'is_template_bank', 'template_source'
                ]),
                ['updated_by' => auth()->id()]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Test template updated successfully',
                'data' => $template->load('parameters')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update test template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a test template
     */
    public function deleteTemplate($id): JsonResponse
    {
        $template = LabTestTemplate::findOrFail($id);
        
        // Check if template is in use
        $inUse = LabTestResult::where('template_id', $id)->exists();
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete template that is in use'
            ], 422);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Test template deleted successfully'
        ]);
    }

    // ========================================
    // TEST RESULTS MANAGEMENT
    // ========================================

    /**
     * Get template-based results entry form structure
     */
    public function getTemplateResultsForm($templateId): JsonResponse
    {
        try {
            $template = LabTestTemplate::with(['parameters' => function($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            }])->findOrFail($templateId);

            $formStructure = [
                'template' => [
                    'id' => $template->id,
                    'template_code' => $template->template_code,
                    'template_name' => $template->template_name,
                    'category' => $template->category,
                    'test_type' => $template->test_type,
                    'specimen_type' => $template->specimen_type,
                    'methodology' => $template->methodology,
                    'equipment_required' => $template->equipment_required,
                    'routine_tat_hours' => $template->routine_tat_hours,
                    'urgent_tat_hours' => $template->urgent_tat_hours,
                    'stat_tat_hours' => $template->stat_tat_hours,
                    'cost' => $template->cost,
                    'nhis_cost' => $template->nhis_cost,
                    'nhis_covered' => $template->nhis_covered,
                    'is_template_bank' => $template->is_template_bank,
                    'template_source' => $template->template_source,
                    'description' => $template->description,
                    'collection_instructions' => $template->collection_instructions,
                    'preparation_instructions' => $template->preparation_instructions,
                    'storage_requirements' => $template->storage_requirements,
                    'transport_requirements' => $template->transport_requirements
                ],
                'parameters' => $template->parameters->map(function($param) {
                    return [
                        'id' => $param->id,
                        'parameter_code' => $param->parameter_code,
                        'parameter_name' => $param->parameter_name,
                        'description' => $param->description,
                        'data_type' => $param->data_type,
                        'input_type' => $param->input_type,
                        'input_options' => $param->input_options,
                        'unit' => $param->unit,
                        'decimal_places' => $param->decimal_places,
                        'is_required' => $param->is_required,
                        'is_critical' => $param->is_critical,
                        'allows_delta_check' => $param->allows_delta_check,
                        'sort_order' => $param->sort_order,
                        'validation_rules' => $param->validation_rules,
                        'reference_ranges' => $param->reference_ranges,
                        'critical_values' => $param->critical_values,
                        'flagging_rules' => $param->flagging_rules
                    ];
                }),
                'form_config' => [
                    'supports_qualitative' => $template->supportsQualitative(),
                    'supports_quantitative' => $template->supportsQuantitative(),
                    'supports_narrative' => $template->supportsNarrative(),
                    'requires_doctor_approval' => $template->requiresApproval(),
                    'requires_consultant_review' => $template->requiresReview(),
                    'requires_pathologist_review' => $template->requiresPathologistReview(),
                    'test_type_display' => $template->getTestTypeDisplayName(),
                    'category_display' => $template->getCategoryDisplayName(),
                    'subcategory_display' => $template->getSubcategoryDisplayName()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $formStructure,
                'message' => 'Template results form structure retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get template form structure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get test results for a lab request
     * For patients: only shows verified and approved results
     * For staff: shows all results
     */
    public function getTestResults($requestId): JsonResponse
    {
        $labRequest = LabRequest::with(['patient', 'doctor', 'technician'])->findOrFail($requestId);
        
        // Check if user is a patient
        $isPatient = auth()->user()->hasRole('patient');
        
        $testResultsQuery = LabTestResult::with([
            'parameter',
            'performedBy',
            'verifiedBy',
            'approvedBy',
            'comments' => function($q) {
                $q->where('is_public', true)->orderBy('commented_at');
            }
        ])
        ->where('lab_request_id', $requestId);
        
        // If patient, only show verified and approved results
        if ($isPatient) {
            // Verify this lab request belongs to the patient
            $patient = auth()->user()->patient;
            if (!$patient || $labRequest->patient_id !== $patient->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this lab result'
                ], 403);
            }
            
            // Only allow access to completed requests with approved results
            if ($labRequest->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Lab results are not yet available. Results must be completed, verified, and approved.'
                ], 400);
            }
            
            // Verify that results are verified and approved
            $hasApprovedResults = $labRequest->results()
                ->whereNotNull('result_verified_at')
                ->whereNotNull('result_approved_at')
                ->whereNotNull('result_entered_at')
                ->exists();

            if (!$hasApprovedResults) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lab results are not yet available. Results must be verified and approved before they can be viewed.'
                ], 400);
            }
            
            // Only show verified and approved results
            $testResultsQuery->whereNotNull('result_verified_at')
                            ->whereNotNull('result_approved_at')
                            ->whereNotNull('result_entered_at');
        }
        
        $testResults = $testResultsQuery->orderBy('parameter_id')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'lab_request' => $labRequest,
                'test_results' => $testResults
            ]
        ]);
    }

    /**
     * Enter test results based on template structure
     */
    public function enterTemplateResults(Request $request, $requestId, $templateId): JsonResponse
    {
        $labRequest = LabRequest::with(['patient', 'templateAssignments'])->findOrFail($requestId);
        $template = LabTestTemplate::with('parameters')->findOrFail($templateId);

        // Check if template is assigned to this request
        $templateAssignment = $labRequest->templateAssignments()
            ->where('template_id', $templateId)
            ->first();

        if (!$templateAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Template is not assigned to this lab request'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'results' => 'required|array|min:1',
            'results.*.parameter_id' => 'required|exists:lab_test_parameters,id',
            'results.*.result_value' => 'required',
            'results.*.clinical_interpretation' => 'nullable|string',
            'results.*.technical_notes' => 'nullable|string',
            'results.*.quality_control_notes' => 'nullable|string',
            'methodology_used' => 'nullable|string',
            'equipment_used' => 'nullable|string',
            'reagent_lot_number' => 'nullable|string',
            'reagent_expiry_date' => 'nullable|date',
            'test_performed_at' => 'nullable|date',
            'technician_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Mark template assignment as started
            $templateAssignment->markAsStarted(auth()->id());

            $patient = $labRequest->patient;
            $ageGroup = $this->determineAgeGroup($patient->date_of_birth);
            $gender = $patient->gender;
            $isPregnant = $this->isPatientPregnant($patient);

            $enteredResults = [];

            foreach ($request->results as $resultData) {
                $parameter = LabTestParameter::findOrFail($resultData['parameter_id']);
                
                // Validate parameter belongs to template
                if ($parameter->template_id !== $template->id) {
                    throw new \Exception("Parameter {$parameter->parameter_name} does not belong to template {$template->template_name}");
                }
                
                // Get reference range and critical values
                $referenceRange = $parameter->getReferenceRange($ageGroup, $gender, $isPregnant);
                $criticalValues = $parameter->getCriticalValues($ageGroup, $gender, $isPregnant);
                
                // Determine result status and flag based on template test type
                $resultStatus = $this->determineResultStatusForTemplate($resultData['result_value'], $parameter, $template, $ageGroup, $gender, $isPregnant);
                $abnormalFlag = $this->determineAbnormalFlagForTemplate($resultData['result_value'], $parameter, $template, $ageGroup, $gender, $isPregnant);

                // Format the result value based on parameter configuration
                $formattedValue = $this->formatResultValueForTemplate($resultData['result_value'], $parameter, $template);

                // Create test result
                $testResult = LabTestResult::create([
                    'lab_request_id' => $requestId,
                    'template_id' => $template->id,
                    'parameter_id' => $parameter->id,
                    'parameter_code' => $parameter->parameter_code,
                    'parameter_name' => $parameter->parameter_name,
                    'result_value' => $resultData['result_value'],
                    'formatted_value' => $formattedValue,
                    'unit' => $parameter->unit,
                    'reference_range' => $referenceRange ? $referenceRange->getFormattedRange() : null,
                    'age_group' => $ageGroup,
                    'gender' => $gender,
                    'is_pregnant' => $isPregnant,
                    'result_status' => $resultStatus,
                    'abnormal_flag' => $abnormalFlag,
                    'clinical_interpretation' => $resultData['clinical_interpretation'] ?? null,
                    'technical_notes' => $resultData['technical_notes'] ?? null,
                    'quality_control_notes' => $resultData['quality_control_notes'] ?? null,
                    'methodology_used' => $request->methodology_used ?? $template->methodology,
                    'equipment_used' => $request->equipment_used ?? $template->equipment_required,
                    'reagent_lot_number' => $request->reagent_lot_number,
                    'reagent_expiry_date' => $request->reagent_expiry_date,
                    'test_performed_at' => $request->test_performed_at ?? now(),
                    'result_entered_at' => now(),
                    'performed_by' => auth()->id(),
                    'technician_notes' => $request->technician_notes
                ]);

                $enteredResults[] = $testResult;

                // Add clinical interpretation comment if provided
                if (!empty($resultData['clinical_interpretation'])) {
                    $testResult->comments()->create([
                        'comment_type' => 'interpretation',
                        'comment_content' => $resultData['clinical_interpretation'],
                        'commented_by' => auth()->id(),
                        'commented_at' => now(),
                        'is_public' => true
                    ]);
                }
            }

            // Mark template assignment as completed
            $templateAssignment->markAsCompleted();

            // Update lab request overall status
            $labRequest->updateTemplateCompletion();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'template' => $template,
                    'results' => $enteredResults,
                    'lab_request' => $labRequest->fresh()
                ],
                'message' => 'Template-based test results entered successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to enter template results: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enter test results
     */
    public function enterTestResults(Request $request, $requestId): JsonResponse
    {
        $labRequest = LabRequest::findOrFail($requestId);
        $this->assertLabRequestAccess($labRequest);

        $validator = Validator::make($request->all(), [
            'results' => 'required|array|min:1',
            'results.*.parameter_id' => 'required|exists:lab_test_parameters,id',
            'results.*.result_value' => 'required',
            'results.*.clinical_interpretation' => 'nullable|string',
            'results.*.technical_notes' => 'nullable|string',
            'results.*.quality_control_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $patient = $labRequest->patient;
            $ageGroup = $this->determineAgeGroup($patient->date_of_birth);
            $gender = $patient->gender;
            $isPregnant = $this->isPatientPregnant($patient);

            foreach ($request->results as $resultData) {
                $parameter = LabTestParameter::findOrFail($resultData['parameter_id']);
                
                // Get reference range and critical values
                $referenceRange = $parameter->getReferenceRange($ageGroup, $gender, $isPregnant);
                $criticalValues = $parameter->getCriticalValues($ageGroup, $gender, $isPregnant);
                
                // Determine result status and flag
                $resultStatus = 'normal';
                $abnormalFlag = null;
                
                if ($criticalValues && $parameter->isCriticalValue($resultData['result_value'], $ageGroup, $gender, $isPregnant)) {
                    $resultStatus = 'critical';
                    $abnormalFlag = $criticalValues->getFlag($resultData['result_value']);
                } elseif ($referenceRange && !$parameter->isWithinRange($resultData['result_value'], $ageGroup, $gender, $isPregnant)) {
                    $resultStatus = 'abnormal';
                    $abnormalFlag = $parameter->getFlag($resultData['result_value'], $ageGroup, $gender, $isPregnant);
                }

                // Format the result value
                $formattedValue = $parameter->formatValue($resultData['result_value']);

                // Create test result
                $testResult = LabTestResult::create([
                    'lab_request_id' => $requestId,
                    'template_id' => $parameter->template_id,
                    'parameter_id' => $parameter->id,
                    'parameter_code' => $parameter->parameter_code,
                    'parameter_name' => $parameter->parameter_name,
                    'result_value' => $resultData['result_value'],
                    'formatted_value' => $formattedValue,
                    'unit' => $parameter->unit,
                    'reference_range' => $referenceRange ? $referenceRange->getFormattedRange() : null,
                    'age_group' => $ageGroup,
                    'gender' => $gender,
                    'is_pregnant' => $isPregnant,
                    'result_status' => $resultStatus,
                    'abnormal_flag' => $abnormalFlag,
                    'clinical_interpretation' => $resultData['clinical_interpretation'] ?? null,
                    'technical_notes' => $resultData['technical_notes'] ?? null,
                    'quality_control_notes' => $resultData['quality_control_notes'] ?? null,
                    'methodology_used' => $parameter->template->methodology,
                    'equipment_used' => $parameter->template->equipment_required,
                    'test_performed_at' => now(),
                    'result_entered_at' => now(),
                    'performed_by' => auth()->id()
                ]);

                // Add clinical interpretation comment if provided
                if (!empty($resultData['clinical_interpretation'])) {
                    $testResult->comments()->create([
                        'comment_type' => 'interpretation',
                        'comment_content' => $resultData['clinical_interpretation'],
                        'commented_by' => auth()->id(),
                        'commented_at' => now(),
                        'is_public' => true
                    ]);
                }
            }

            // Update lab request status
            $labRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
                'technician_id' => auth()->id()
            ]);

            // Send notification to patient that results are ready
            $this->sendLabResultReadyNotification($labRequest);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test results entered successfully',
                'data' => $this->getTestResults($requestId)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to enter test results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify test results
     */
    public function verifyTestResults(Request $request, $requestId): JsonResponse
    {
        $labRequest = LabRequest::findOrFail($requestId);
        $this->assertLabRequestAccess($labRequest);

        $validator = Validator::make($request->all(), [
            'result_ids' => 'required|array|min:1',
            'result_ids.*' => 'required|exists:lab_test_results,id',
            'verification_notes' => 'nullable|string',
            'action' => 'required|in:verify,approve,reject'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $results = LabTestResult::whereIn('id', $request->result_ids)
                ->where('lab_request_id', $requestId)
                ->get();

            if ($results->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No results found for verification'
                ], 404);
            }

            $updatedResults = [];

            foreach ($results as $result) {
                $updateData = [
                    'technical_notes' => $request->verification_notes
                ];

                if ($request->action === 'verify') {
                    $updateData['verification_status'] = 'verified';
                    $updateData['verified_by'] = auth()->id();
                    $updateData['result_verified_at'] = now();
                } elseif ($request->action === 'approve') {
                    $updateData['verification_status'] = 'approved';
                    $updateData['verified_by'] = auth()->id();
                    $updateData['approved_by'] = auth()->id();
                    $updateData['result_verified_at'] = now();
                    $updateData['result_approved_at'] = now();
                } elseif ($request->action === 'reject') {
                    $updateData['verification_status'] = 'rejected';
                    $updateData['technical_notes'] = 'REJECTED: ' . $request->verification_notes;
                }

                $result->update($updateData);
                $updatedResults[] = $result;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test results ' . $request->action . 'd successfully',
                'data' => $updatedResults
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to ' . $request->action . ' test results: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lab request templates for result entry
     */
    public function getRequestTemplates($requestId): JsonResponse
    {
        $labRequest = LabRequest::with([
            'templateAssignments.template.parameters',
            'templateAssignments.assignedTechnician'
        ])->findOrFail($requestId);

        return response()->json([
            'success' => true,
            'data' => $labRequest->templateAssignments,
            'message' => 'Request templates retrieved successfully'
        ]);
    }

    /**
     * Assign technician to specific template
     */
    public function assignTechnicianToTemplate(Request $request, $requestId, $templateId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'technician_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $templateAssignment = LabRequestTemplate::where('lab_request_id', $requestId)
            ->where('template_id', $templateId)
            ->first();

        if (!$templateAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Template assignment not found'
            ], 404);
        }

        $templateAssignment->update([
            'assigned_technician_id' => $request->technician_id
        ]);

        return response()->json([
            'success' => true,
            'data' => $templateAssignment->load('assignedTechnician'),
            'message' => 'Technician assigned to template successfully'
        ]);
    }

    /**
     * Get template assignments
     */
    public function getTemplateAssignments(Request $request): JsonResponse
    {
        $query = DB::table('template_assignments')
            ->join('lab_test_templates', 'template_assignments.template_id', '=', 'lab_test_templates.id')
            ->select(
                'template_assignments.*',
                'lab_test_templates.template_code',
                'lab_test_templates.template_name',
                'lab_test_templates.category',
                'lab_test_templates.subcategory',
                'lab_test_templates.test_type',
                'lab_test_templates.specimen_type'
            );

        // Filter by test type
        if ($request->has('test_type') && !empty($request->test_type)) {
            $query->where('template_assignments.test_type', $request->test_type);
        }

        // Filter by category
        if ($request->has('category') && !empty($request->category)) {
            $query->where('template_assignments.category', $request->category);
        }

        // Filter by specimen type
        if ($request->has('specimen_type') && !empty($request->specimen_type)) {
            $query->where('template_assignments.specimen_type', $request->specimen_type);
        }

        $assignments = $query->orderBy('template_assignments.priority')
            ->orderBy('template_assignments.created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Create template assignment
     */
    public function createTemplateAssignment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'test_type' => 'required|string|max:50',
            'category' => 'required|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'specimen_type' => 'required|string|max:100',
            'template_id' => 'required|exists:lab_test_templates,id',
            'is_default' => 'boolean',
            'priority' => 'integer|min:1|max:10',
            'auto_select' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if assignment already exists
            $existingAssignment = DB::table('template_assignments')
                ->where('test_type', $request->test_type)
                ->where('category', $request->category)
                ->where('specimen_type', $request->specimen_type)
                ->where('template_id', $request->template_id)
                ->first();

            if ($existingAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template assignment already exists for this combination'
                ], 400);
            }

            // If this is set as default, remove default from other assignments
            if ($request->boolean('is_default')) {
                DB::table('template_assignments')
                    ->where('test_type', $request->test_type)
                    ->where('category', $request->category)
                    ->where('specimen_type', $request->specimen_type)
                    ->update(['is_default' => false]);
            }

            $assignmentId = DB::table('template_assignments')->insertGetId([
                'test_type' => $request->test_type,
                'category' => $request->category,
                'subcategory' => $request->subcategory,
                'specimen_type' => $request->specimen_type,
                'template_id' => $request->template_id,
                'is_default' => $request->boolean('is_default'),
                'priority' => $request->get('priority', 1),
                'auto_select' => $request->boolean('auto_select'),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template assignment created successfully',
                'data' => ['id' => $assignmentId]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create template assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete template assignment
     */
    public function deleteTemplateAssignment($id): JsonResponse
    {
        try {
            $deleted = DB::table('template_assignments')->where('id', $id)->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Template assignment deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Template assignment not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete template assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending verification results
     */
    public function getPendingVerification(Request $request): JsonResponse
    {
        $query = LabTestResult::with([
            'labRequest.patient',
            'parameter',
            'performedBy'
        ])->where('verification_status', 'pending');

        // Filter by branch if user is not admin
        if (!auth()->user()->hasRole('admin')) {
            $query->whereHas('labRequest', function($q) {
                $q->where('branch_id', auth()->user()->branch_id);
            });
        }

        $results = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $results,
            'message' => 'Pending verification results retrieved successfully'
        ]);
    }

    /**
     * Legacy method for backward compatibility
     */
    public function verifyTestResultsLegacy(Request $request, $requestId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'result_ids' => 'required|array|min:1',
            'result_ids.*' => 'exists:lab_test_results,id',
            'verification_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            LabTestResult::whereIn('id', $request->result_ids)
                ->where('lab_request_id', $requestId)
                ->update([
                    'result_verified_at' => now(),
                    'verified_by' => auth()->id()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Test results verified successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify test results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve test results
     */
    public function approveTestResults(Request $request, $requestId): JsonResponse
    {
        $labRequest = LabRequest::findOrFail($requestId);
        $this->assertLabRequestAccess($labRequest);

        $validator = Validator::make($request->all(), [
            'result_ids' => 'required|array|min:1',
            'result_ids.*' => 'exists:lab_test_results,id',
            'approval_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            LabTestResult::whereIn('id', $request->result_ids)
                ->where('lab_request_id', $requestId)
                ->update([
                    'result_approved_at' => now(),
                    'approved_by' => auth()->id()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Test results approved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve test results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // PDF GENERATION
    // ========================================

    /**
     * Generate PDF test results report
     */
    public function generateTestResultsPdf(Request $request, $requestId): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // If user is a patient, verify ownership
            if ($user->hasRole('patient')) {
                $patient = $user->patient;
                
                if (!$patient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient record not found for this user'
                    ], 404);
                }
                
                $labRequest = LabRequest::findOrFail($requestId);
                
                // Verify this lab request belongs to the patient
                if ($labRequest->patient_id !== $patient->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this lab result'
                    ], 403);
                }
                
                // Only allow download if results are completed, verified, and approved
                if ($labRequest->status !== 'completed') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Results are not yet available for download'
                    ], 400);
                }
                
                // Verify that results are verified and approved
                $hasApprovedResults = $labRequest->results()
                    ->whereNotNull('result_verified_at')
                    ->whereNotNull('result_approved_at')
                    ->whereNotNull('result_entered_at')
                    ->exists();

                if (!$hasApprovedResults) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Results are not yet available for download. Results must be verified and approved first.'
                    ], 400);
                }
            } else {
                // For non-patients, use existing authorization checks
                $labRequest = LabRequest::findOrFail($requestId);
            }
            
            $pdfService = new LabPdfService();
            $options = $request->all();
            // Mark if this is a patient request
            if ($user->hasRole('patient')) {
                $options['is_patient'] = true;
            }
            $pdf = $pdfService->generateTestResultsPdf($requestId, $options);
            
            $filename = $pdfService->generateFilename($labRequest, 'results');
            
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lab request not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate dynamic PDF for specific template results
     */
    public function generateDynamicTemplateResultPdf(Request $request, $requestId, $templateId): JsonResponse
    {
        try {
            $pdfService = new LabPdfService();
            $pdf = $pdfService->generateDynamicTestResultsPdf($requestId, $templateId, $request->all());
            
            $labRequest = LabRequest::findOrFail($requestId);
            $template = LabTestTemplate::findOrFail($templateId);
            $filename = $pdfService->generateFilename($labRequest, $template->template_code . '_results');
            
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate dynamic PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF for specific template results
     */
    public function generateTemplateResultPdf(Request $request, $requestId, $templateId): JsonResponse
    {
        try {
            $pdfService = new LabPdfService();
            $pdf = $pdfService->generateTemplateResultPdf($requestId, $templateId, $request->all());
            
            $labRequest = LabRequest::findOrFail($requestId);
            $template = LabTestTemplate::findOrFail($templateId);
            $filename = "template_{$template->template_code}_{$labRequest->request_number}_" . now()->format('Y-m-d') . ".pdf";
            
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename, [
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
     * Generate critical results alert PDF
     */
    public function generateCriticalResultsPdf(Request $request, $requestId): JsonResponse
    {
        try {
            $pdfService = new LabPdfService();
            $pdf = $pdfService->generateCriticalResultsPdf($requestId, $request->all());
            
            $labRequest = LabRequest::findOrFail($requestId);
            $filename = "critical_alert_{$labRequest->request_number}_" . now()->format('Y-m-d_H-i-s') . ".pdf";
            
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename, [
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
     * Generate quality control report PDF
     */
    public function generateQualityControlPdf(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            
            $qcRecords = LabQualityControl::whereBetween('test_date', [$startDate, $endDate])
                ->with('parameter')
                ->orderBy('test_date', 'desc')
                ->get();
            
            $pdfService = new LabPdfService();
            $pdf = $pdfService->generateQualityControlPdf($qcRecords, $request->all());
            
            $filename = "qc_report_{$startDate}_to_{$endDate}.pdf";
            
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename, [
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
     * Generate diagnostic report style test results PDF
     */
    public function generateDiagnosticReportPdf(Request $request, $requestId): JsonResponse
    {
        try {
            $templateId = $request->input('template_id');
            $pdfService = new DiagnosticReportPdfService();
            $pdf = $pdfService->generateDiagnosticReportPdf($requestId, $templateId, $request->all());
            
            $labRequest = LabRequest::findOrFail($requestId);
            $filename = $pdfService->generateFilename($labRequest, 'diagnostic_report');
            
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate diagnostic report PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient's lab results (for mobile app)
     */
    public function getPatientResults(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Get the patient record associated with this user
        $patient = $user->patient;
        
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found for this user'
            ], 404);
        }
        
        // Only show lab requests that have verified and approved results
        $query = LabRequest::with([
            'patient', 
            'doctor', 
            'technician', 
            'template', 
            'testType.template', 
            'testType.category',
            'results' => function($q) {
                // Only load results that are verified and approved
                $q->whereNotNull('result_verified_at')
                  ->whereNotNull('result_approved_at')
                  ->whereNotNull('result_entered_at')
                  ->orderBy('parameter_id');
            }
        ])
        ->where('patient_id', $patient->id)
        ->where('status', 'completed')
        ->whereHas('results', function($q) {
            // Only include lab requests that have at least one verified and approved result
            $q->whereNotNull('result_verified_at')
              ->whereNotNull('result_approved_at')
              ->whereNotNull('result_entered_at');
        })
        ->orderBy('created_at', 'desc');

        // Apply filters
        // NOTE: Status filter is NOT applied here because patients can only see completed requests with approved results
        // The status filter would be a security vulnerability as it could override the 'completed' constraint
        
        if ($request->has('date') && $request->date) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $results = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Get patient's lab requests (for mobile app)
     * Shows all requests regardless of status, but only includes verified and approved results
     */
    public function getPatientRequests(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Get the patient record associated with this user
        $patient = $user->patient;
        
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found for this user'
            ], 404);
        }
        
        $query = LabRequest::with([
            'patient', 
            'doctor', 
            'technician', 
            'template', 
            'testType.template', 
            'testType.category', 
            'results' => function($q) {
                // Only load results that are verified and approved (if any)
                // This allows patients to see their requests even if results aren't ready
                $q->whereNotNull('result_verified_at')
                  ->whereNotNull('result_approved_at')
                  ->whereNotNull('result_entered_at')
                  ->orderBy('parameter_id');
            }
        ])
        ->where('patient_id', $patient->id)
        ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Get patient lab statistics (for mobile app)
     */
    public function getPatientLabStatistics(): JsonResponse
    {
        $user = auth()->user();
        
        // Get the patient record associated with this user
        $patient = $user->patient;
        
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found for this user'
            ], 404);
        }
        
        // Statistics should match what patients can actually see
        // Total available results = only completed requests with approved results
        $totalAvailableResults = LabRequest::where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->whereHas('results', function($q) {
                $q->whereNotNull('result_verified_at')
                  ->whereNotNull('result_approved_at')
                  ->whereNotNull('result_entered_at');
            })
            ->count();
        
        // All requests (for tracking purposes)
        $totalRequests = LabRequest::where('patient_id', $patient->id)->count();
        $completedRequests = LabRequest::where('patient_id', $patient->id)
            ->where('status', 'completed')->count();
        $pendingRequests = LabRequest::where('patient_id', $patient->id)
            ->where('status', 'pending')->count();
        $inProgressRequests = LabRequest::where('patient_id', $patient->id)
            ->where('status', 'in_progress')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_requests' => $totalRequests,
                'available_results' => $totalAvailableResults, // Results that can be viewed
                'completed_requests' => $completedRequests,
                'pending_requests' => $pendingRequests,
                'in_progress_requests' => $inProgressRequests,
                'completion_rate' => $totalRequests > 0 ? round(($completedRequests / $totalRequests) * 100, 2) : 0
            ]
        ]);
    }

    /**
     * Calculate overdue tests based on TAT
     */
    private function calculateOverdueTests()
    {
        $overdueCount = 0;
        
        $pendingRequests = LabRequest::where('status', 'pending')
            ->orWhere('status', 'in_progress')
            ->get();
            
        foreach ($pendingRequests as $request) {
            $template = $request->template;
            if (!$template) continue;
            
            $tatHours = $request->priority === 'stat' ? $template->stat_tat_hours :
                       ($request->priority === 'urgent' ? $template->urgent_tat_hours : $template->routine_tat_hours);
            
            $expectedCompletion = $request->created_at->addHours($tatHours);
            
            if (now()->isAfter($expectedCompletion)) {
                $overdueCount++;
            }
        }
        
        return $overdueCount;
    }

    /**
     * Get quality control statistics
     */
    private function getQualityControlStats()
    {
        // This would need to be implemented with actual QC data
        // For now, return a calculated percentage based on recent results
        $totalResults = LabTestResult::where('created_at', '>=', now()->subDays(30))->count();
        $passedResults = LabTestResult::where('created_at', '>=', now()->subDays(30))
            ->where('result_status', '!=', 'critical')
            ->count();
            
        return $totalResults > 0 ? round(($passedResults / $totalResults) * 100, 1) : 0;
    }

    /**
     * Get equipment calibration statistics
     */
    private function getEquipmentCalibrationStats()
    {
        // This would need to be implemented with actual calibration data
        // For now, return count of active equipment
        return \App\Models\LabEquipment::where('status', 'active')->count();
    }

    // ========================================
    // FILTERING & UTILITIES
    // ========================================

    /**
     * Get categories for filtering
     */
    public function getCategories(): JsonResponse
    {
        $categories = LabTestTemplate::active()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get test types for filtering
     */
    public function getTestTypes(): JsonResponse
    {
        $testTypes = LabTestTemplate::active()
            ->select('template_type')
            ->distinct()
            ->orderBy('template_type')
            ->pluck('template_type');

        return response()->json([
            'success' => true,
            'data' => $testTypes
        ]);
    }

    /**
     * Get lab test types (legacy compatibility)
     */
    public function getTestTypesLegacy()
    {
        $query = LabTestType::where('is_active', true);

        // Support both 'category' (string) and 'category_id' (int) for filtering
        if (request()->has('category_id')) {
            $query->where('category_id', request()->category_id);
        } elseif (request()->has('category')) {
            $query->where('category', request()->category);
        }

        if (request()->has('search')) {
            $search = request()->search;
            $query->where(function($q) use ($search) {
                $q->where('test_name', 'like', "%{$search}%")
                  ->orWhere('test_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $testTypes = $query->orderBy('test_name')->get();

        return response()->json([
            'success' => true,
            'data' => $testTypes,
            'message' => 'Test types retrieved successfully'
        ]);
    }

    /**
     * Get test categories (legacy compatibility)
     */
    public function getTestCategories()
    {
        $categories = LabTestType::select('category')
            ->distinct()
            ->where('is_active', true)
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Test categories retrieved successfully'
        ]);
    }

    // ========================================
    // QUALITY CONTROL
    // ========================================

    /**
     * Get quality control records
     */
    public function getQualityControlRecords(Request $request)
    {
        $query = DB::table('lab_quality_control')
            ->leftJoin('users', 'lab_quality_control.performed_by', '=', 'users.id')
            ->select('lab_quality_control.*', 'users.first_name', 'users.last_name')
            ->orderBy('performed_at', 'desc');

        if ($request->has('date_from')) {
            $query->whereDate('performed_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('performed_at', '<=', $request->date_to);
        }
        if ($request->has('qc_type')) {
            $query->where('qc_type', $request->qc_type);
        }

        $records = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $records,
            'message' => 'Quality control records retrieved successfully'
        ]);
    }

    /**
     * Create quality control record
     */
    public function createQualityControlRecord(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parameter_id' => 'required|integer',
            'qc_type' => 'required|in:Internal,External,Calibration',
            'qc_level' => 'required|string',
            'qc_material' => 'required|string',
            'lot_number' => 'required|string',
            'expiry_date' => 'required|date',
            'target_value' => 'required|numeric',
            'acceptable_range_low' => 'required|numeric',
            'acceptable_range_high' => 'required|numeric',
            'measured_value' => 'required|numeric',
            'performed_at' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $isAcceptable = $request->measured_value >= $request->acceptable_range_low && 
                       $request->measured_value <= $request->acceptable_range_high;

        $record = DB::table('lab_quality_control')->insertGetId([
            'parameter_id' => $request->parameter_id,
            'qc_type' => $request->qc_type,
            'qc_level' => $request->qc_level,
            'qc_material' => $request->qc_material,
            'lot_number' => $request->lot_number,
            'expiry_date' => $request->expiry_date,
            'target_value' => $request->target_value,
            'acceptable_range_low' => $request->acceptable_range_low,
            'acceptable_range_high' => $request->acceptable_range_high,
            'measured_value' => $request->measured_value,
            'is_acceptable' => $isAcceptable,
            'performed_at' => $request->performed_at,
            'performed_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => ['id' => $record],
            'message' => 'Quality control record created successfully'
        ], 201);
    }

    /**
     * Get equipment calibrations
     */
    public function getEquipmentCalibrations(Request $request)
    {
        $query = DB::table('lab_equipment_calibration')
            ->leftJoin('users', 'lab_equipment_calibration.calibrated_by', '=', 'users.id')
            ->select('lab_equipment_calibration.*', 'users.first_name', 'users.last_name')
            ->orderBy('calibrated_at', 'desc');

        if ($request->has('equipment_name')) {
            $query->where('equipment_name', 'like', '%' . $request->equipment_name . '%');
        }
        if ($request->has('calibration_type')) {
            $query->where('calibration_type', $request->calibration_type);
        }

        $calibrations = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $calibrations,
            'message' => 'Equipment calibrations retrieved successfully'
        ]);
    }

    /**
     * Create equipment calibration
     */
    public function createEquipmentCalibration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'equipment_name' => 'required|string',
            'equipment_model' => 'required|string',
            'serial_number' => 'required|string',
            'calibration_type' => 'required|in:Daily,Weekly,Monthly,Quarterly,Annual',
            'calibrated_at' => 'required|date',
            'next_calibration_due' => 'required|date',
            'is_acceptable' => 'required|boolean',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $calibration = DB::table('lab_equipment_calibration')->insertGetId([
            'equipment_name' => $request->equipment_name,
            'equipment_model' => $request->equipment_model,
            'serial_number' => $request->serial_number,
            'calibration_type' => $request->calibration_type,
            'calibrated_at' => $request->calibrated_at,
            'next_calibration_due' => $request->next_calibration_due,
            'is_acceptable' => $request->is_acceptable,
            'notes' => $request->notes,
            'calibrated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => ['id' => $calibration],
            'message' => 'Equipment calibration created successfully'
        ], 201);
    }

    /**
     * Update quality control record
     */
    public function updateQualityControlRecord(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'qc_type' => 'required|in:Internal,External,Calibration',
            'qc_level' => 'required|string',
            'qc_material' => 'nullable|string',
            'lot_number' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'target_value' => 'nullable|numeric',
            'acceptable_range_low' => 'nullable|numeric',
            'acceptable_range_high' => 'nullable|numeric',
            'measured_value' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'performed_at' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Calculate acceptability
        $isAcceptable = true;
        if ($request->measured_value && $request->acceptable_range_low && $request->acceptable_range_high) {
            $isAcceptable = $request->measured_value >= $request->acceptable_range_low && 
                           $request->measured_value <= $request->acceptable_range_high;
        }

        $updated = DB::table('lab_quality_control')
            ->where('id', $id)
            ->update([
                'qc_type' => $request->qc_type,
                'qc_level' => $request->qc_level,
                'qc_material' => $request->qc_material,
                'lot_number' => $request->lot_number,
                'expiry_date' => $request->expiry_date,
                'target_value' => $request->target_value,
                'acceptable_range_low' => $request->acceptable_range_low,
                'acceptable_range_high' => $request->acceptable_range_high,
                'measured_value' => $request->measured_value,
                'is_acceptable' => $isAcceptable,
                'notes' => $request->notes,
                'performed_at' => $request->performed_at,
                'updated_at' => now()
            ]);

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'Quality control record updated successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Quality control record not found'
            ], 404);
        }
    }

    /**
     * Delete quality control record
     */
    public function deleteQualityControlRecord($id)
    {
        $deleted = DB::table('lab_quality_control')->where('id', $id)->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Quality control record deleted successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Quality control record not found'
            ], 404);
        }
    }

    /**
     * Update equipment calibration
     */
    public function updateEquipmentCalibration(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'equipment_name' => 'required|string',
            'equipment_model' => 'required|string',
            'serial_number' => 'required|string',
            'calibration_type' => 'required|in:Daily,Weekly,Monthly,Quarterly,Annual',
            'calibrated_at' => 'required|date',
            'next_calibration_due' => 'required|date',
            'is_acceptable' => 'required|boolean',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updated = DB::table('lab_equipment_calibration')
            ->where('id', $id)
            ->update([
                'equipment_name' => $request->equipment_name,
                'equipment_model' => $request->equipment_model,
                'serial_number' => $request->serial_number,
                'calibration_type' => $request->calibration_type,
                'calibrated_at' => $request->calibrated_at,
                'next_calibration_due' => $request->next_calibration_due,
                'is_acceptable' => $request->is_acceptable,
                'notes' => $request->notes,
                'updated_at' => now()
            ]);

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'Equipment calibration updated successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Equipment calibration not found'
            ], 404);
        }
    }

    /**
     * Delete equipment calibration
     */
    public function deleteEquipmentCalibration($id)
    {
        $deleted = DB::table('lab_equipment_calibration')->where('id', $id)->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Equipment calibration deleted successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Equipment calibration not found'
            ], 404);
        }
    }

    // ========================================
    // REPORTS & STATISTICS
    // ========================================

    /**
     * Get lab reports
     */
    public function getLabReports(Request $request)
    {
        $query = DB::table('lab_reports')
            ->leftJoin('users', 'lab_reports.created_by', '=', 'users.id')
            ->select('lab_reports.*', 'users.first_name', 'users.last_name')
            ->orderBy('created_at', 'desc');

        if ($request->has('report_type')) {
            $query->where('report_type', $request->report_type);
        }
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reports = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reports,
            'message' => 'Lab reports retrieved successfully'
        ]);
    }

    /**
     * Get report templates
     */
    public function getReportTemplates()
    {
        $templates = [
            [
                'id' => 1,
                'name' => 'Daily Lab Summary',
                'type' => 'daily',
                'description' => 'Summary of all lab activities for a specific day'
            ],
            [
                'id' => 2,
                'name' => 'Weekly Lab Report',
                'type' => 'weekly',
                'description' => 'Comprehensive weekly lab performance report'
            ],
            [
                'id' => 3,
                'name' => 'Monthly Lab Statistics',
                'type' => 'monthly',
                'description' => 'Monthly statistics and trends analysis'
            ],
            [
                'id' => 4,
                'name' => 'Quality Control Report',
                'type' => 'quality_control',
                'description' => 'Quality control performance and compliance report'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $templates,
            'message' => 'Report templates retrieved successfully'
        ]);
    }

    /**
     * Generate lab report
     */
    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|integer',
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'include_charts' => 'boolean',
            'include_details' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate report data based on template and date range
        $reportData = $this->generateReportData($request->template_id, $request->date_from, $request->date_to, $request->all());

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'message' => 'Report generated successfully'
        ]);
    }

    /**
     * Download lab report
     */
    public function downloadReport($id)
    {
        $report = DB::table('lab_reports')->find($id);
        
        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $report,
                'download_url' => '/lab/reports/' . $id . '/download'
            ],
            'message' => 'Report download initiated'
        ]);
    }

    /**
     * Get lab statistics
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_requests' => LabRequest::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'pending_requests' => LabRequest::where('status', 'pending')->count(),
            'completed_requests' => LabRequest::where('status', 'completed')->count(),
            'critical_results' => LabTestResult::where('result_status', 'critical')->count(),
            'abnormal_results' => LabTestResult::where('result_status', 'abnormal')->count(),
            'overdue_tests' => $this->calculateOverdueTests(),
            'quality_control_passed' => $this->getQualityControlStats(),
            'equipment_calibrated' => $this->getEquipmentCalibrationStats(),
            'templates_available' => LabTestTemplate::where('is_active', true)->count(),
            'turnaround_time' => $this->calculateAverageTurnaroundTime($dateFrom, $dateTo),
            'test_categories' => $this->getTestCategoryStats($dateFrom, $dateTo)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Lab statistics retrieved successfully'
        ]);
    }

    // ========================================
    // LEGACY COMPATIBILITY METHODS
    // ========================================

    /**
     * Store lab results with intelligent result handling (legacy)
     */
    public function storeResults(Request $request, $labRequestId)
    {
        $labRequest = LabRequest::findOrFail($labRequestId);

        $validator = Validator::make($request->all(), [
            'results' => 'required|array',
            'results.*.parameter_name' => 'required|string',
            'results.*.result_value' => 'required|string',
            'results.*.unit' => 'nullable|string',
            'results.*.reference_range' => 'nullable|string',
            'results.*.methodology' => 'nullable|string',
            'results.*.equipment_used' => 'nullable|string',
            'results.*.reagent_lot' => 'nullable|string',
            'results.*.reagent_expiry' => 'nullable|date',
            'performed_by' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $results = [];
            $hasCriticalValues = false;
            $hasAbnormalValues = false;

            foreach ($request->results as $resultData) {
                // Get test type for reference ranges
                $testType = LabTestType::where('test_code', $labRequest->test_type)->first();
                
                // Determine result status and flags
                $resultStatus = $this->determineResultStatus($resultData, $testType);
                $abnormalFlag = $this->determineAbnormalFlag($resultData, $testType);
                
                if ($resultStatus === 'critical') {
                    $hasCriticalValues = true;
                } elseif ($resultStatus === 'abnormal') {
                    $hasAbnormalValues = true;
                }

                $result = LabResult::create([
                    'lab_request_id' => $labRequestId,
                    'test_type_id' => $testType?->id,
                    'parameter_name' => $resultData['parameter_name'],
                    'parameter_code' => $this->generateParameterCode($resultData['parameter_name']),
                    'result_value' => $resultData['result_value'],
                    'unit' => $resultData['unit'] ?? null,
                    'reference_range' => $resultData['reference_range'] ?? $this->getReferenceRange($resultData['parameter_name'], $testType),
                    'result_status' => $resultStatus,
                    'abnormal_flag' => $abnormalFlag,
                    'methodology' => $resultData['methodology'] ?? $testType?->methodology,
                    'equipment_used' => $resultData['equipment_used'] ?? $testType?->equipment_required,
                    'reagent_lot' => $resultData['reagent_lot'] ?? null,
                    'reagent_expiry' => $resultData['reagent_expiry'] ?? null,
                    'test_performed_at' => now(),
                    'result_entered_at' => now(),
                    'performed_by' => $request->performed_by,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $results[] = $result;
            }

            // Update lab request status
            $labRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
                'technician_id' => $request->performed_by
            ]);

            // Send notifications for critical values
            if ($hasCriticalValues) {
                $this->sendCriticalValueNotification($labRequest, $results);
            }

            // Send notification to patient that results are ready
            $this->sendLabResultReadyNotification($labRequest);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Lab results stored successfully',
                'has_critical_values' => $hasCriticalValues,
                'has_abnormal_values' => $hasAbnormalValues
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error storing lab results: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lab results for a specific request (legacy)
     */
    public function getResults($labRequestId)
    {
        if (!auth()->user()->can('view_lab_results')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view lab results.',
            ], 403);
        }

        $labRequest = LabRequest::findOrFail($labRequestId);
        $this->assertLabRequestAccess($labRequest);
        $results = $labRequest->results()->with(['testType', 'performedBy', 'verifiedBy', 'approvedBy'])->get();

        return response()->json([
            'success' => true,
            'data' => $results,
            'message' => 'Lab results retrieved successfully'
        ]);
    }

    /**
     * Get critical results
     */
    public function getCriticalResults(Request $request)
    {
        $query = LabTestResult::with(['labRequest.patient', 'labRequest.doctor', 'parameter'])
            ->where('result_status', 'critical');

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $criticalResults = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $criticalResults,
            'message' => 'Critical results retrieved successfully'
        ]);
    }

    /**
     * Get abnormal results
     */
    public function getAbnormalResults(Request $request)
    {
        $query = LabTestResult::with(['labRequest.patient', 'labRequest.doctor', 'parameter'])
            ->where('result_status', 'abnormal');

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $abnormalResults = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $abnormalResults,
            'message' => 'Abnormal results retrieved successfully'
        ]);
    }

    /**
     * Get all lab results for a patient (legacy method)
     */
    public function getPatientResultsLegacy(Request $request)
    {
        $query = LabTestResult::with(['labRequest.patient', 'labRequest.doctor', 'parameter', 'performedBy'])
            ->whereHas('labRequest', function($q) use ($request) {
                if ($request->has('patient_id')) {
                    $q->where('patient_id', $request->patient_id);
                }
            });

        // Filter by test type
        if ($request->has('test_type_id')) {
            $query->where('template_id', $request->test_type_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by result status
        if ($request->has('result_status')) {
            $query->where('result_status', $request->result_status);
        }

        $results = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $results,
            'message' => 'Patient lab results retrieved successfully'
        ]);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Determine result status for template-based results
     */
    private function determineResultStatusForTemplate($resultValue, $parameter, $template, $ageGroup, $gender, $isPregnant)
    {
        // For narrative tests, always return normal status
        if ($template->test_type === 'narrative') {
            return 'normal';
        }

        // For qualitative tests, check against reference ranges
        if ($template->test_type === 'qualitative') {
            $referenceRange = $parameter->getReferenceRange($ageGroup, $gender, $isPregnant);
            if ($referenceRange && !$parameter->isWithinRange($resultValue, $ageGroup, $gender, $isPregnant)) {
                return 'abnormal';
            }
            return 'normal';
        }

        // For quantitative and combined tests, check critical values first
        if (in_array($template->test_type, ['quantitative', 'combined'])) {
            $criticalValues = $parameter->getCriticalValues($ageGroup, $gender, $isPregnant);
            if ($criticalValues && $parameter->isCriticalValue($resultValue, $ageGroup, $gender, $isPregnant)) {
                return 'critical';
            }

            $referenceRange = $parameter->getReferenceRange($ageGroup, $gender, $isPregnant);
            if ($referenceRange && !$parameter->isWithinRange($resultValue, $ageGroup, $gender, $isPregnant)) {
                return 'abnormal';
            }
        }

        return 'normal';
    }

    /**
     * Determine abnormal flag for template-based results
     */
    private function determineAbnormalFlagForTemplate($resultValue, $parameter, $template, $ageGroup, $gender, $isPregnant)
    {
        // For narrative tests, no flags
        if ($template->test_type === 'narrative') {
            return null;
        }

        // For qualitative tests, check against reference ranges
        if ($template->test_type === 'qualitative') {
            $referenceRange = $parameter->getReferenceRange($ageGroup, $gender, $isPregnant);
            if ($referenceRange && !$parameter->isWithinRange($resultValue, $ageGroup, $gender, $isPregnant)) {
                return $parameter->getFlag($resultValue, $ageGroup, $gender, $isPregnant);
            }
            return null;
        }

        // For quantitative and combined tests
        if (in_array($template->test_type, ['quantitative', 'combined'])) {
            $criticalValues = $parameter->getCriticalValues($ageGroup, $gender, $isPregnant);
            if ($criticalValues && $parameter->isCriticalValue($resultValue, $ageGroup, $gender, $isPregnant)) {
                return $criticalValues->getFlag($resultValue);
            }

            $referenceRange = $parameter->getReferenceRange($ageGroup, $gender, $isPregnant);
            if ($referenceRange && !$parameter->isWithinRange($resultValue, $ageGroup, $gender, $isPregnant)) {
                return $parameter->getFlag($resultValue, $ageGroup, $gender, $isPregnant);
            }
        }

        return null;
    }

    /**
     * Format result value for template-based results
     */
    private function formatResultValueForTemplate($resultValue, $parameter, $template)
    {
        // For narrative tests, return as-is
        if ($template->test_type === 'narrative') {
            return $resultValue;
        }

        // For qualitative tests, return as-is
        if ($template->test_type === 'qualitative') {
            return $resultValue;
        }

        // For quantitative and combined tests, format based on parameter settings
        if (in_array($template->test_type, ['quantitative', 'combined'])) {
            if ($parameter->data_type === 'numeric' && $parameter->decimal_places > 0) {
                return number_format(floatval($resultValue), $parameter->decimal_places);
            }
            return $resultValue;
        }

        return $resultValue;
    }

    /**
     * Determine result status based on reference ranges and critical values
     */
    private function determineResultStatus($resultData, $testType)
    {
        if (!$testType || !$resultData['reference_range']) {
            return 'normal';
        }

        $parameterName = $resultData['parameter_name'];
        $resultValue = floatval($resultData['result_value']);
        $referenceRange = $resultData['reference_range'];

        // Get critical values for this parameter
        $criticalValues = $testType->critical_values ? json_decode($testType->critical_values, true) : [];
        $parameterCriticalValues = $criticalValues[$parameterName] ?? [];

        // Check for critical values
        foreach ($parameterCriticalValues as $type => $threshold) {
            $thresholdValue = floatval(str_replace(['<', '>', '='], '', $threshold));
            if (strpos($threshold, '<') !== false && $resultValue < $thresholdValue) {
                return 'critical';
            }
            if (strpos($threshold, '>') !== false && $resultValue > $thresholdValue) {
                return 'critical';
            }
        }

        // Check for abnormal values (outside reference range)
        if ($this->isValueOutsideRange($resultValue, $referenceRange)) {
            return 'abnormal';
        }

        return 'normal';
    }

    /**
     * Determine abnormal flag based on result value and reference range
     */
    private function determineAbnormalFlag($resultData, $testType)
    {
        if (!$testType || !$resultData['reference_range']) {
            return null;
        }

        $resultValue = floatval($resultData['result_value']);
        $referenceRange = $resultData['reference_range'];

        if ($this->isValueOutsideRange($resultValue, $referenceRange)) {
            // Determine if high or low
            $rangeValues = $this->parseReferenceRange($referenceRange);
            if ($rangeValues && $resultValue > $rangeValues['high']) {
                return 'H'; // High
            } elseif ($rangeValues && $resultValue < $rangeValues['low']) {
                return 'L'; // Low
            }
        }

        return null;
    }

    /**
     * Check if value is outside reference range
     */
    private function isValueOutsideRange($value, $referenceRange)
    {
        $rangeValues = $this->parseReferenceRange($referenceRange);
        if (!$rangeValues) {
            return false;
        }

        return $value < $rangeValues['low'] || $value > $rangeValues['high'];
    }

    /**
     * Parse reference range string to extract low and high values
     */
    private function parseReferenceRange($referenceRange)
    {
        // Handle formats like "13.8-17.2 g/dL", "4.5-11.0 x 10³/μL", etc.
        if (preg_match('/(\d+\.?\d*)\s*-\s*(\d+\.?\d*)/', $referenceRange, $matches)) {
            return [
                'low' => floatval($matches[1]),
                'high' => floatval($matches[2])
            ];
        }

        return null;
    }

    /**
     * Generate parameter code from parameter name
     */
    private function generateParameterCode($parameterName)
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $parameterName));
    }

    /**
     * Get reference range for parameter from test type
     */
    private function getReferenceRange($parameterName, $testType)
    {
        if (!$testType || !$testType->normal_ranges) {
            return null;
        }

        $normalRanges = json_decode($testType->normal_ranges, true);
        return $normalRanges[$parameterName] ?? null;
    }

    /**
     * Send critical value notification
     */
    private function sendCriticalValueNotification($labRequest, $results)
    {
        // Implementation for sending critical value notifications
        \Log::info('Critical lab values detected', [
            'lab_request_id' => $labRequest->id,
            'patient_id' => $labRequest->patient_id,
            'critical_results' => collect($results)->where('result_status', 'critical')->pluck('parameter_name')
        ]);
    }

    /**
     * Calculate average turnaround time
     */
    private function calculateAverageTurnaroundTime($dateFrom, $dateTo)
    {
        $completedRequests = LabRequest::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('completed_at')
            ->get();

        if ($completedRequests->isEmpty()) {
            return 0;
        }

        $totalHours = $completedRequests->sum(function($request) {
            return $request->created_at->diffInHours($request->completed_at);
        });

        return round($totalHours / $completedRequests->count(), 2);
    }

    /**
     * Get test category statistics
     */
    private function getTestCategoryStats($dateFrom, $dateTo)
    {
        return LabRequest::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('test_type, COUNT(*) as count')
            ->groupBy('test_type')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Generate report data based on template
     */
    private function generateReportData($templateId, $dateFrom, $dateTo, $options = [])
    {
        $data = [
            'template_id' => $templateId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'generated_at' => now(),
            'generated_by' => auth()->id()
        ];

        switch ($templateId) {
            case 1: // Daily Lab Summary
                $data['summary'] = [
                    'total_requests' => LabRequest::whereDate('created_at', $dateFrom)->count(),
                    'completed_requests' => LabRequest::whereDate('created_at', $dateFrom)->where('status', 'completed')->count(),
                    'pending_requests' => LabRequest::whereDate('created_at', $dateFrom)->where('status', 'pending')->count(),
                    'critical_results' => LabTestResult::whereDate('created_at', $dateFrom)->where('result_status', 'critical')->count()
                ];
                break;
            case 2: // Weekly Lab Report
                $data['summary'] = [
                    'total_requests' => LabRequest::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                    'completed_requests' => LabRequest::whereBetween('created_at', [$dateFrom, $dateTo])->where('status', 'completed')->count(),
                    'average_turnaround_time' => $this->calculateAverageTurnaroundTime($dateFrom, $dateTo),
                    'quality_control_passed' => DB::table('lab_quality_control')->whereBetween('performed_at', [$dateFrom, $dateTo])->where('is_acceptable', true)->count()
                ];
                break;
            case 3: // Monthly Lab Statistics
                $data['summary'] = [
                    'total_requests' => LabRequest::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                    'revenue' => LabRequest::whereBetween('created_at', [$dateFrom, $dateTo])->sum('cost'),
                    'test_categories' => $this->getTestCategoryStats($dateFrom, $dateTo),
                    'equipment_utilization' => $this->getEquipmentUtilizationStats($dateFrom, $dateTo)
                ];
                break;
            case 4: // Quality Control Report
                $data['summary'] = [
                    'total_qc_tests' => DB::table('lab_quality_control')->whereBetween('performed_at', [$dateFrom, $dateTo])->count(),
                    'passed_tests' => DB::table('lab_quality_control')->whereBetween('performed_at', [$dateFrom, $dateTo])->where('is_acceptable', true)->count(),
                    'failed_tests' => DB::table('lab_quality_control')->whereBetween('performed_at', [$dateFrom, $dateTo])->where('is_acceptable', false)->count(),
                    'equipment_calibrations' => DB::table('lab_equipment_calibration')->whereBetween('calibrated_at', [$dateFrom, $dateTo])->count()
                ];
                break;
        }

        return $data;
    }

    /**
     * Get equipment utilization statistics
     */
    private function getEquipmentUtilizationStats($dateFrom, $dateTo)
    {
        return DB::table('lab_equipment_calibration')
            ->select('equipment_name', DB::raw('COUNT(*) as usage_count'))
            ->whereBetween('calibrated_at', [$dateFrom, $dateTo])
            ->groupBy('equipment_name')
            ->get();
    }

    /**
     * Determine age group from date of birth
     */
    private function determineAgeGroup($dateOfBirth)
    {
        $age = now()->diffInYears($dateOfBirth);
        
        if ($age < 1) return 'Infant';
        if ($age < 2) return 'Toddler';
        if ($age < 12) return 'Child';
        if ($age < 18) return 'Adolescent';
        if ($age < 65) return 'Adult';
        return 'Elderly';
    }

    /**
     * Check if patient is pregnant (simplified logic)
     */
    private function isPatientPregnant($patient)
    {
        // This would typically check patient's medical history or current status
        // For now, return false as a default
        return false;
    }

    /**
     * Get recent lab activity
     */
    public function getRecentActivity(Request $request)
    {
        $activities = collect();

        // Get recent critical results
        $criticalResults = LabTestResult::with(['labRequest.patient', 'parameter'])
            ->where('result_status', 'critical')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($criticalResults as $result) {
            $activities->push([
                'id' => 'critical_' . $result->id,
                'type' => 'critical',
                'message' => "Critical {$result->parameter_name} result for {$result->labRequest->patient->first_name} {$result->labRequest->patient->last_name}",
                'timestamp' => $result->created_at->diffForHumans(),
                'priority' => 'critical',
                'created_at' => $result->created_at
            ]);
        }

        // Get recent completed results
        $completedResults = LabTestResult::with(['labRequest.patient', 'parameter'])
            ->where('result_status', 'normal')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($completedResults as $result) {
            $activities->push([
                'id' => 'result_' . $result->id,
                'type' => 'result',
                'message' => "{$result->parameter_name} results completed for {$result->labRequest->patient->first_name} {$result->labRequest->patient->last_name}",
                'timestamp' => $result->created_at->diffForHumans(),
                'priority' => 'medium',
                'created_at' => $result->created_at
            ]);
        }

        // Get recent lab requests
        $recentRequests = LabRequest::with(['patient', 'doctor'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentRequests as $request) {
            $activities->push([
                'id' => 'request_' . $request->id,
                'type' => 'request',
                'message' => "New lab request from Dr. {$request->doctor->first_name} {$request->doctor->last_name}",
                'timestamp' => $request->created_at->diffForHumans(),
                'priority' => $request->priority === 'urgent' ? 'high' : 'low',
                'created_at' => $request->created_at
            ]);
        }

        // Sort by created_at and limit to 10 most recent
        $activities = $activities->sortByDesc('created_at')->take(10)->values();

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    // ========================================
    // WALK-IN LAB WORKFLOW
    // ========================================

    /**
     * Create lab request from walk-in visit.
     */
    public function createFromWalkInVisit(Request $request, $visitId)
    {
        $visit = Visit::with(['patient', 'queues'])->findOrFail($visitId);

        if ($visit->status !== 'active' || $visit->visit_type !== 'LabOnly') {
            return response()->json([
                'success' => false,
                'message' => 'Visit is not active or not a lab-only visit'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'template_ids' => 'required|array|min:1',
            'template_ids.*' => 'required|exists:lab_test_templates,id',
            'clinical_notes' => 'nullable|string',
            'priority' => 'required|in:routine,urgent,stat',
            'specimen_type' => 'required|string',
            'collection_instructions' => 'nullable|string',
            'special_instructions' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Get the first template for basic info
            $firstTemplate = LabTestTemplate::findOrFail($request->template_ids[0]);
            
            // Create a single lab request with multiple templates
            $labRequest = LabRequest::create([
                'patient_id' => $visit->patient_id,
                'consultation_id' => null, // Walk-in lab doesn't have consultation
                'doctor_id' => $request->doctor_id ?? auth()->id(),
                'branch_id' => $visit->branch_id,
                'template_id' => $firstTemplate->id, // Keep first template for backward compatibility
                'test_type' => $firstTemplate->template_name,
                'test_description' => $firstTemplate->description,
                'clinical_notes' => $request->clinical_notes,
                'priority' => $request->priority,
                'specimen_type' => $request->specimen_type,
                'collection_instructions' => $request->collection_instructions,
                'special_instructions' => $request->special_instructions,
                'status' => 'pending',
                'created_by' => auth()->id()
            ]);

            // Add all templates to the request
            $labRequest->addTemplates($request->template_ids);

            // Update queue status to serving
            $visit->queues()->where('queue_type', 'Lab')->update([
                'status' => 'serving',
                'serving_at' => now(),
                'served_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $labRequest->load(['patient', 'templates', 'templateAssignments']),
                'message' => 'Lab request created successfully with multiple templates'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating lab request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete walk-in lab service.
     */
    public function completeWalkInService(Request $request, $visitId)
    {
        $visit = Visit::with(['queues'])->findOrFail($visitId);

        if ($visit->status !== 'active' || $visit->visit_type !== 'LabOnly') {
            return response()->json([
                'success' => false,
                'message' => 'Visit is not active or not a lab-only visit'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update queue status to completed
            $visit->queues()->where('queue_type', 'Lab')->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Complete visit
            $visit->update([
                'status' => 'completed',
                'check_out_time' => now(),
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $visit,
                'message' => 'Walk-in lab service completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error completing walk-in lab service: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lab queue status.
     */
    public function getLabQueueStatus(Request $request)
    {
        $branchId = $request->get('branch_id', 1);

        $queues = Queue::with(['patient', 'visit'])
                      ->where('queue_type', 'Lab')
                      ->where('branch_id', $branchId)
                      ->whereIn('status', ['waiting', 'called', 'serving'])
                      ->orderBy('position')
                      ->get();

        $stats = [
            'total_waiting' => $queues->where('status', 'waiting')->count(),
            'total_called' => $queues->where('status', 'called')->count(),
            'total_serving' => $queues->where('status', 'serving')->count(),
            'current_serving' => $queues->where('status', 'serving')->first(),
            'next_in_line' => $queues->where('status', 'waiting')->first(),
            'average_wait_time' => $this->calculateAverageWaitTime('Lab', $branchId),
            'estimated_wait_time' => $this->calculateEstimatedWaitTime('Lab', $branchId)
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'queues' => $queues,
                'stats' => $stats
            ],
            'message' => 'Lab queue status retrieved successfully'
        ]);
    }

    /**
     * Call next patient in lab queue.
     */
    public function callNextLabPatient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'called_by' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Find next patient in lab queue
            $nextQueue = Queue::where('queue_type', 'Lab')
                            ->where('branch_id', $request->branch_id)
                            ->where('status', 'waiting')
                            ->orderBy('priority', 'asc')
                            ->orderBy('position')
                            ->first();

            if (!$nextQueue) {
                return response()->json([
                    'success' => false,
                    'message' => 'No patients waiting in lab queue'
                ], 404);
            }

            // Update queue status
            $nextQueue->update([
                'status' => 'called',
                'called_at' => now(),
                'called_by' => $request->called_by
            ]);

            // Update all other queues to move them up
            Queue::where('queue_type', 'Lab')
                ->where('branch_id', $request->branch_id)
                ->where('status', 'waiting')
                ->where('position', '>', $nextQueue->position)
                ->decrement('position');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $nextQueue->load(['patient', 'visit']),
                'message' => 'Patient called successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error calling patient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start serving lab patient.
     */
    public function startServingLabPatient(Request $request, $queueId)
    {
        $queue = Queue::findOrFail($queueId);

        if ($queue->status !== 'called' || $queue->queue_type !== 'Lab') {
            return response()->json([
                'success' => false,
                'message' => 'Patient must be called before serving'
            ], 400);
        }

        $queue->update([
            'status' => 'serving',
            'serving_at' => now(),
            'served_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $queue->load(['patient', 'visit']),
            'message' => 'Lab service started'
        ]);
    }

    /**
     * Calculate average wait time for lab queue.
     */
    private function calculateAverageWaitTime(string $queueType, int $branchId): float
    {
        return Queue::where('queue_type', $queueType)
                   ->where('branch_id', $branchId)
                   ->whereNotNull('called_at')
                   ->whereNotNull('queued_at')
                   ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, queued_at, called_at)) as avg_wait')
                   ->value('avg_wait') ?? 0;
    }

    /**
     * Calculate estimated wait time for lab queue.
     */
    private function calculateEstimatedWaitTime(string $queueType, int $branchId): int
    {
        $avgServiceTime = Queue::where('queue_type', $queueType)
                             ->where('branch_id', $branchId)
                             ->where('status', 'completed')
                             ->whereNotNull('serving_at')
                             ->whereNotNull('completed_at')
                             ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, serving_at, completed_at)) as avg_service')
                             ->value('avg_service') ?? 15;

        $patientsAhead = Queue::where('queue_type', $queueType)
                             ->where('branch_id', $branchId)
                             ->where('status', 'waiting')
                             ->count();

        return $patientsAhead * $avgServiceTime;
    }

    /**
     * Send notification when lab results are ready
     */
    private function sendLabResultReadyNotification($labRequest)
    {
        try {
            $labRequest->loadMissing(['patient', 'doctor']);
            $patient = $labRequest->patient;
            $testName = $labRequest->test_type_name ?? $labRequest->testType?->name ?? 'Lab Test';
            $patientName = $patient?->full_name ?? 'Patient';

            if ($patient?->user_id) {
                Notification::create([
                    'recipient_id' => $patient->user_id,
                    'type' => 'lab_result',
                    'title' => 'Lab Results Ready',
                    'message' => "Your {$testName} results are now available. Tap to view.",
                    'priority' => 'high',
                    'data' => [
                        'lab_request_id' => $labRequest->id,
                        'test_name' => $testName,
                        'completed_at' => $labRequest->completed_at,
                        'screen' => 'LabTests',
                        'id' => (string) $labRequest->id,
                    ],
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            if ($labRequest->doctor_id) {
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
                        'screen' => 'LabTests',
                        'id' => (string) $labRequest->id,
                    ],
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send lab result notification: ' . $e->getMessage());
        }
    }

    // ========================================
    // LAB ARCHIVE MANAGEMENT
    // ========================================

    /**
     * Get archived lab requests (completed requests)
     */
    public function getArchive(Request $request): JsonResponse
    {
        $query = LabRequest::with([
            'patient',
            'doctor',
            'template',
            'results' => function($query) {
                $query->with(['parameter', 'performedBy', 'verifiedBy', 'approvedBy']);
            }
        ])
        ->where('status', 'completed')
        ->latest('completed_at');

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

        // Filter by template
        if ($request->filled('template_id')) {
            $query->where('template_id', $request->get('template_id'));
        }

        // Filter by doctor
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->get('doctor_id'));
        }

        $labRequests = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $labRequests,
            'message' => 'Archived lab requests retrieved successfully'
        ]);
    }

    /**
     * Get patient's complete lab archive
     */
    public function getPatientArchive($patientId, Request $request): JsonResponse
    {
        $query = LabRequest::with([
            'doctor',
            'template',
            'results' => function($query) {
                $query->with(['parameter', 'performedBy', 'verifiedBy', 'approvedBy']);
            }
        ])
        ->where('patient_id', $patientId)
        ->where('status', 'completed')
        ->latest('completed_at');

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('completed_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('completed_at', '<=', $request->get('date_to'));
        }

        $labRequests = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $labRequests,
            'message' => 'Patient lab archive retrieved successfully'
        ]);
    }

    /**
     * Get archive statistics
     */
    public function getArchiveStatistics(Request $request): JsonResponse
    {
        $statsQuery = LabRequest::where('status', 'completed');
        
        // Apply filters
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

        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'Archive statistics retrieved successfully'
        ]);
    }

    // ========================================
    // LAB CATEGORIES MANAGEMENT
    // ========================================

    /**
     * Get all lab test categories (CRUD endpoint)
     */
    public function getLabCategories(Request $request): JsonResponse
    {
        $query = LabTestCategory::with(['createdBy', 'updatedBy'])
            ->orderBy('sort_order', 'asc')
            ->latest('id');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Lab test categories retrieved successfully'
        ]);
    }

    /**
     * Create a new lab test category
     */
    public function createCategory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:lab_test_categories,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category = LabTestCategory::create([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description,
                'sort_order' => $request->sort_order ?? 0,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $category->load(['createdBy', 'updatedBy']),
                'message' => 'Lab test category created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific lab test category
     */
    public function getLabCategory($id): JsonResponse
    {
        $category = LabTestCategory::with(['createdBy', 'updatedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Lab test category retrieved successfully'
        ]);
    }

    /**
     * Update a lab test category
     */
    public function updateLabCategory(Request $request, $id): JsonResponse
    {
        $category = LabTestCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:lab_test_categories,code,' . $category->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category->update([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description,
                'sort_order' => $request->sort_order ?? $category->sort_order,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $category->is_active,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $category->load(['createdBy', 'updatedBy']),
                'message' => 'Lab test category updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a lab test category
     */
    public function deleteLabCategory($id): JsonResponse
    {
        $category = LabTestCategory::findOrFail($id);

        // Check if category has associated tests
        if ($category->tests()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated tests'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lab test category deleted successfully'
        ]);
    }

    // ========================================
    // LAB TESTS MANAGEMENT
    // ========================================

    /**
     * Get all lab tests
     */
    public function getTests(Request $request): JsonResponse
    {
        $query = \App\Models\LabTest::with(['category', 'testType', 'template', 'createdBy'])
            ->orderBy('sort_order', 'asc')
            ->latest('id');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('test_name', 'like', "%{$search}%")
                  ->orWhere('test_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $tests = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tests,
            'message' => 'Lab tests retrieved successfully'
        ]);
    }

    /**
     * Create a new lab test
     */
    public function createTest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'test_code' => 'required|string|unique:lab_tests,test_code',
            'test_name' => 'required|string|max:255',
            'category_id' => 'required|exists:lab_test_categories,id',
            'test_type_id' => 'nullable|exists:lab_test_types,id',
            'template_id' => 'nullable|exists:lab_test_templates,id',
            'description' => 'nullable|string',
            'specimen_type' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'nhis_cost' => 'nullable|numeric|min:0',
            'nhis_covered' => 'nullable|boolean',
            'turnaround_hours' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $test = LabTest::create([
                'test_code' => $request->test_code,
                'test_name' => $request->test_name,
                'category_id' => $request->category_id,
                'test_type_id' => $request->test_type_id,
                'template_id' => $request->template_id,
                'description' => $request->description,
                'specimen_type' => $request->specimen_type,
                'cost' => $request->cost,
                'nhis_cost' => $request->nhis_cost,
                'nhis_covered' => $request->boolean('nhis_covered', false),
                'turnaround_hours' => $request->turnaround_hours,
                'sort_order' => $request->sort_order ?? 0,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $test->load(['category', 'testType', 'template', 'createdBy']),
                'message' => 'Lab test created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific lab test
     */
    public function getLabTest($id): JsonResponse
    {
        $test = LabTest::with(['category', 'testType', 'template', 'createdBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $test,
            'message' => 'Lab test retrieved successfully'
        ]);
    }

    /**
     * Update a lab test
     */
    public function updateLabTest(Request $request, $id): JsonResponse
    {
        $test = LabTest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'test_code' => 'required|string|unique:lab_tests,test_code,' . $test->id,
            'test_name' => 'required|string|max:255',
            'category_id' => 'required|exists:lab_test_categories,id',
            'test_type_id' => 'nullable|exists:lab_test_types,id',
            'template_id' => 'nullable|exists:lab_test_templates,id',
            'description' => 'nullable|string',
            'specimen_type' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'nhis_cost' => 'nullable|numeric|min:0',
            'nhis_covered' => 'nullable|boolean',
            'turnaround_hours' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $test->update([
                'test_code' => $request->test_code,
                'test_name' => $request->test_name,
                'category_id' => $request->category_id,
                'test_type_id' => $request->test_type_id,
                'template_id' => $request->template_id,
                'description' => $request->description,
                'specimen_type' => $request->specimen_type,
                'cost' => $request->cost,
                'nhis_cost' => $request->nhis_cost,
                'nhis_covered' => $request->has('nhis_covered') ? $request->boolean('nhis_covered') : $test->nhis_covered,
                'turnaround_hours' => $request->turnaround_hours,
                'sort_order' => $request->sort_order ?? $test->sort_order,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $test->is_active,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $test->load(['category', 'testType', 'template', 'createdBy']),
                'message' => 'Lab test updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a lab test
     */
    public function deleteLabTest($id): JsonResponse
    {
        $test = LabTest::findOrFail($id);
        $test->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lab test deleted successfully'
        ]);
    }
}