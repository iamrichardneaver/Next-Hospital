<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\LabTestCategory;
use App\Models\LabTestType;
use App\Models\LabTestTemplate;
use App\Models\Visit;
use App\Models\Invoice;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabController extends Controller
{
    use ExportsListData, ResolvesUserBranch, WorkflowNavigation;
    public function index(Request $request)
    {
        // RBAC: Check permission
        if (!auth()->user()->can('view_lab_requests')) {
            abort(403, 'You do not have permission to view lab requests.');
        }
        
        $branchId = $this->resolveUserBranchId('view_lab_requests');
        
        // RBAC scoped: Only show lab requests from user's branch
        $query = LabRequest::with(['patient', 'createdBy', 'templates', 'doctor', 'consultation'])
            ->whereHas('patient')
            ->where('branch_id', $branchId);

        $this->applyDoctorLabScope($query);

        $labRequests = $query->latest('id')->paginate(20);
        
        // Statistics should match the same filtering logic
        $statsQuery = LabRequest::where('branch_id', $branchId);
        $this->applyDoctorLabScope($statsQuery);
        
        $statistics = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'today' => (clone $statsQuery)->whereDate('created_at', today())->count(),
        ];
        
        return view('lab.index', compact('labRequests', 'statistics'));
    }
    
    public function create()
    {
        // RBAC: Check permission
        if (!auth()->user()->can('create_lab_requests')) {
            abort(403, 'You do not have permission to create lab requests.');
        }
        
        $branchId = $this->resolveUserBranchId('create_lab_requests');

        // Only show patients from user's branch (RBAC scoped)
        $patients = Patient::where('branch_id', $branchId)->latest()->get();
        
        // Get categories from LabTestCategory table (proper hierarchy)
        $testCategories = LabTestCategory::active()->ordered()->get();
        
        return view('lab.create', compact('patients', 'testCategories'));
    }
    
    public function store(Request $request)
    {
        // RBAC: Check permission
        if (!auth()->user()->can('create_lab_requests')) {
            abort(403, 'You do not have permission to create lab requests.');
        }
        
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'test_type' => 'nullable|exists:lab_test_types,id',
            'test_types' => 'nullable|array',
            'test_types.*' => 'exists:lab_test_types,id',
            'test_category' => 'required|exists:lab_test_categories,id',
            'notes' => 'nullable|string',
            'consultation_id' => 'nullable|exists:consultations,id',
        ]);
        
        $testTypeIds = array_values(array_unique(array_filter(
            array_merge(
                $validated['test_types'] ?? [],
                $validated['test_type'] ? [$validated['test_type']] : []
            )
        )));
        if (empty($testTypeIds)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Please select at least one test type.');
        }
        
        // Get user's branch with proper scoping
        $branchId = $this->resolveUserBranchId('create_lab_requests');
        
        // Verify patient belongs to user's branch (RBAC scoping)
        $patient = Patient::findOrFail($validated['patient_id']);
        if ($patient->branch_id != $branchId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Patient does not belong to your branch');
        }
        
        $testTypes = LabTestType::whereIn('id', $testTypeIds)->get();
        $templateIds = $testTypes->map(fn (LabTestType $type) => $type->getResolvedTemplateId())
            ->filter()
            ->unique()
            ->values()
            ->all();
        if (empty($templateIds)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Selected test type(s) have no template assigned. Please contact lab setup.');
        }
        
        $firstTestType = $testTypes->first();
        $categoryName = 'Unknown';
        if ($firstTestType->category_id) {
            $category = LabTestCategory::find($firstTestType->category_id);
            $categoryName = $category ? $category->name : 'Unknown';
        }
        $testNames = $testTypes->pluck('test_name')->unique()->implode(', ');
        
        $labRequestData = [
            'patient_id' => $validated['patient_id'],
            'consultation_id' => $validated['consultation_id'] ?? null,
            'doctor_id' => auth()->id(),
            'branch_id' => $branchId,
            'template_id' => $templateIds[0],
            'test_type_id' => $firstTestType->id,
            'test_category_id' => $firstTestType->category_id,
            'test_category_name' => $categoryName,
            'test_type_name' => $testNames,
            'test_type' => $testNames,
            'test_description' => $validated['notes'] ?? $firstTestType->description ?? null,
            'clinical_notes' => $validated['notes'] ?? null,
            'priority' => 'routine',
            'specimen_type' => $firstTestType->specimen_type ?? null,
            'collection_instructions' => $firstTestType->collection_instructions ?? null,
            'special_instructions' => null,
            'status' => 'pending',
            'technician_id' => null,
            'billing_status' => 'pending',
            'has_multiple_templates' => count($templateIds) > 1,
            'total_templates' => count($templateIds),
            'completed_templates' => 0,
            'overall_status' => 'pending',
            'created_by' => auth()->id(),
        ];
        
        try {
            $labRequest = LabRequest::create($labRequestData);
            $labRequest->addTemplates($templateIds);
            
            return redirect()->route('lab.index')
                ->with('success', 'Lab request created successfully with ' . count($templateIds) . ' test(s).');
        } catch (\Exception $e) {
            \Log::error('Error creating lab request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $labRequestData
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating lab request: ' . $e->getMessage());
        }
    }
    
    public function show(LabRequest $lab)
    {
        // RBAC: Check permission
        if (!auth()->user()->can('view_lab_requests')) {
            abort(403, 'You do not have permission to view lab requests.');
        }
        
        $this->assertLabRequestAccess($lab);
        
        $lab->load([
            'patient',
            'createdBy',
            'doctor',
            'template',
            'results.performedBy',
            'results.parameter.referenceRanges'
        ]);
        
        return view('lab.show', compact('lab'));
    }
    
    public function edit(LabRequest $lab)
    {
        // RBAC: Check permission
        if (!auth()->user()->can('edit_lab_requests')) {
            abort(403, 'You do not have permission to edit lab requests.');
        }
        
        $this->assertResourceInUserBranch($lab->branch_id, 'edit_lab_requests');
        
        $templates = \App\Models\LabTestTemplate::where('is_active', true)->orderBy('template_name')->get();
        return view('lab.edit', compact('lab', 'templates'));
    }
    
    public function update(Request $request, LabRequest $lab)
    {
        // RBAC: Check permission
        if (!auth()->user()->can('edit_lab_requests')) {
            abort(403, 'You do not have permission to edit lab requests.');
        }
        
        $this->assertResourceInUserBranch($lab->branch_id, 'edit_lab_requests');
        
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,in_progress,completed,cancelled',
                'template_id' => 'nullable|exists:lab_test_templates,id',
            ]);
            
            $wasCompleted = $lab->status !== 'completed' && $validated['status'] === 'completed';
            
            // Update the lab request
            $lab->update($validated);
            
            // If template_id is provided, ensure it's also added to lab_request_templates
            if (!empty($validated['template_id'])) {
                $lab->addTemplates([$validated['template_id']]);
            }
            
            // Complete workflow step if lab is completed and has visit
            if ($wasCompleted && $lab->consultation && $lab->consultation->visit) {
                $this->completeWorkflowStep($lab->consultation->visit, 'laboratory_testing', [
                    'lab_request_id' => $lab->id,
                ]);
            }
            
            // Use workflow navigation if available
            if ($wasCompleted && $lab->consultation && $lab->consultation->visit) {
                return $this->redirectToNextStep($lab->consultation->visit, 'Lab request completed!');
            }
            
            return redirect()->route('lab.show', $lab)
                ->with('success', 'Lab request updated!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error updating lab request: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'lab_request_id' => $lab->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update lab request. Please try again.');
        }
    }
    
    /**
     * Start a lab test (AJAX endpoint)
     */
    public function startTest(LabRequest $lab)
    {
        // RBAC: Check permission
        if (!auth()->user()->can('edit_lab_requests')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to start lab tests.'
            ], 403);
        }
        
        $this->assertResourceInUserBranch($lab->branch_id, 'edit_lab_requests');
        
        if ($lab->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Lab test must be pending to start.'
            ], 400);
        }
        
        try {
            $lab->update([
                'status' => 'in_progress',
                'technician_id' => auth()->id(),
                'collected_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Lab test started successfully',
                'data' => $lab->load(['patient', 'technician'])
            ]);
        } catch (\Exception $e) {
            \Log::error('Error starting lab test: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'lab_request_id' => $lab->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start lab test: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Complete a lab test (AJAX endpoint)
     */
    public function completeTest(LabRequest $lab)
    {
        // RBAC: Check permission
        if (!auth()->user()->can('edit_lab_requests')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to complete lab tests.'
            ], 403);
        }
        
        $this->assertResourceInUserBranch($lab->branch_id, 'edit_lab_requests');
        
        if ($lab->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Lab test must be in progress to complete.'
            ], 400);
        }
        
        try {
            $wasCompleted = $lab->status !== 'completed';
            
            $lab->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
            
            // Complete workflow step if lab is completed and has visit
            if ($wasCompleted && $lab->consultation && $lab->consultation->visit) {
                $this->completeWorkflowStep($lab->consultation->visit, 'laboratory_testing', [
                    'lab_request_id' => $lab->id,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Lab test completed successfully',
                'data' => $lab->load(['patient', 'technician'])
            ]);
        } catch (\Exception $e) {
            \Log::error('Error completing lab test: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'lab_request_id' => $lab->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete lab test: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function destroy(LabRequest $lab)
    {
        // RBAC: Check permission
        if (!auth()->user()->can('delete_lab_requests')) {
            abort(403, 'You do not have permission to delete lab requests.');
        }
        
        $this->assertResourceInUserBranch($lab->branch_id, 'delete_lab_requests');
        
        try {
            // Check if lab request has results that prevent deletion
            if ($lab->results()->count() > 0) {
                return redirect()->route('lab.index')
                    ->with('error', 'Cannot delete lab request with existing results. Please contact administrator.');
            }
            
            // Log the deletion
            \Log::info('Lab request deleted', [
                'lab_request_id' => $lab->id,
                'request_number' => $lab->request_number,
                'patient_id' => $lab->patient_id,
                'deleted_by' => auth()->id(),
                'deleted_at' => now(),
            ]);
            
            $lab->delete();
            
            return redirect()->route('lab.index')
                ->with('success', 'Lab request deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('Error deleting lab request: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'lab_request_id' => $lab->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete lab request. Please try again.');
        }
    }
    
    /**
     * Get test types by category for AJAX requests
     */
    public function getTestTypesByCategory(Request $request)
    {
        $categoryId = $request->get('category_id');
        
        if (!$categoryId) {
            return response()->json(['test_types' => []]);
        }
        
        $testTypes = LabTestType::active()
            ->where('category_id', $categoryId)
            ->orderBy('test_name')
            ->get([
                'id',
                'template_id',
                'test_code',
                'test_name',
                'category_id',
                'specimen_type',
                'collection_instructions',
                'preparation_instructions'
            ]);
        
        return response()->json(['test_types' => $testTypes]);
    }
    
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:lab_requests,id',
        ]);
        
        $deletedCount = 0;
        $errors = [];
        
        foreach ($validated['ids'] as $id) {
            $labRequest = LabRequest::find($id);
            
            // Check if lab request has results that prevent deletion
            if ($labRequest && $labRequest->results()->count() > 0) {
                $errors[] = "Lab request " . ($labRequest->request_number ?: $labRequest->id) . " has results and cannot be deleted.";
                continue;
            }
            
            if ($labRequest) {
                // Log the deletion
                \Log::info('Lab request deleted (bulk)', [
                    'lab_request_id' => $labRequest->id,
                    'request_number' => $labRequest->request_number,
                    'patient_id' => $labRequest->patient_id,
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);
                
                $labRequest->delete();
                $deletedCount++;
            }
        }
        
        $message = "Successfully deleted {$deletedCount} lab request(s).";
        if (!empty($errors)) {
            $message .= " " . implode(' ', $errors);
        }
        
        $redirect = redirect()->route('lab.index');
        
        if ($deletedCount > 0) {
            $redirect->with('success', $message);
        } else {
            $redirect->with('error', 'No lab requests were deleted. ' . implode(' ', $errors));
        }
        
        return $redirect;
    }

    /**
     * Create lab request from walk-in visit
     */
    public function createFromWalkInVisit(Visit $visit)
    {
        // Ensure this is a lab-only visit
        if ($visit->visit_type !== 'LabOnly') {
            return redirect()->back()
                ->with('error', 'This visit is not a lab-only visit.');
        }

        // Get available lab test templates
        $testTemplates = LabTestTemplate::active()
            ->orderBy('template_name')
            ->get();

        // Get test types for pricing reference
        $testTypes = LabTestType::active()
            ->orderBy('test_name')
            ->get(['id', 'test_name', 'test_code', 'cost', 'nhis_cost', 'nhis_covered']);

        return view('lab.create-from-walk-in', compact('visit', 'testTemplates', 'testTypes'));
    }

    /**
     * Store lab request from walk-in visit with automatic billing
     */
    public function storeFromWalkInVisit(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'template_ids' => 'required|array|min:1',
            'template_ids.*' => 'exists:lab_test_templates,id',
            'clinical_notes' => 'nullable|string',
            'priority' => 'required|in:routine,urgent,stat',
            'specimen_type' => 'nullable|string',
            'collection_instructions' => 'nullable|string',
            'special_instructions' => 'nullable|string',
            'create_invoice' => 'boolean'
        ]);

        // Ensure this is a lab-only visit
        if ($visit->visit_type !== 'LabOnly') {
            return redirect()->back()
                ->with('error', 'This visit is not a lab-only visit.');
        }

        DB::beginTransaction();

        try {
            // Get the first template for basic info
            $firstTemplate = LabTestTemplate::findOrFail($validated['template_ids'][0]);
            
            // Create a single lab request with multiple templates
            $labRequest = LabRequest::create([
                'patient_id' => $visit->patient_id,
                'doctor_id' => auth()->id(),
                'branch_id' => $visit->branch_id,
                'template_id' => $firstTemplate->id, // Keep first template for backward compatibility
                'test_type' => $firstTemplate->template_name,
                'test_description' => $firstTemplate->description ?? $firstTemplate->template_name,
                'clinical_notes' => $validated['clinical_notes'],
                'priority' => $validated['priority'],
                'specimen_type' => $validated['specimen_type'] ?? $firstTemplate->specimen_type,
                'collection_instructions' => $validated['collection_instructions'] ?? $firstTemplate->collection_instructions,
                'special_instructions' => $validated['special_instructions'],
                'status' => 'pending',
                'created_by' => auth()->id()
            ]);

            // Add all templates to the request
            $labRequest->addTemplates($validated['template_ids']);
            $labRequest->load('templateAssignments.template');

            // Update queue status to serving
            $visit->queues()->where('queue_type', 'Lab')->update([
                'status' => 'serving',
                'serving_at' => now(),
                'served_by' => auth()->id()
            ]);

            // ALWAYS create invoice for lab requests (automatic financial tracking)
            $invoice = $this->createLabInvoice($labRequest, $visit);

            DB::commit();

            return redirect()->route('walk-ins.index')
                ->with('success', 'Lab request created successfully! Invoice #' . $invoice->invoice_number . ' generated.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error creating lab request: ' . $e->getMessage());
        }
    }

    /**
     * Create invoice for lab request with dynamic pricing
     */
    private function createLabInvoice(LabRequest $labRequest, Visit $visit)
    {
        $patient = $visit->patient;
        $branch = $visit->branch;
        $pricingService = app(PricingService::class);
        
        // Calculate total cost from templates with dynamic pricing
        $totalCost = 0;
        $items = [];
        
        foreach ($labRequest->templateAssignments as $assignment) {
            $template = $assignment->template;
            
            // Use template cost (already dynamic from database)
            // PricingService can apply additional rules, NHIS pricing, priority surcharges
            $baseCost = $template->cost ?? 0;
            
            // Apply dynamic pricing if available
            try {
                if ($template->test_type_id) {
                    $pricing = $pricingService->calculateLabTestPrice(
                        $template->test_type_id,
                        $patient->id,
                        $branch->id,
                        $labRequest->priority ?? 'routine'
                    );
                    $finalCost = $pricing['final_price'] ?? $baseCost;
                } else {
                    $finalCost = $baseCost;
                }
            } catch (\Exception $e) {
                // Fallback to template cost if pricing service fails
                $finalCost = $baseCost;
                \Log::warning('PricingService failed for lab test, using template cost', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $totalCost += $finalCost;
            
            $items[] = [
                'id' => 'item_' . uniqid(),
                'description' => $template->template_name . ' - ' . $template->description,
                'quantity' => 1,
                'unit_price' => $finalCost,
                'total' => $finalCost,
                'service_type' => 'lab_test',
                'template_id' => $template->id,
                'priority' => $labRequest->priority ?? 'routine'
            ];
        }

        // Create invoice (InvoiceObserver will initialize payment tracking fields)
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => $items,
            'subtotal' => $totalCost,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $totalCost,
            'status' => 'pending', // Pending payment
            'notes' => 'Lab request #' . $labRequest->lab_request_number,
            'created_by' => auth()->id()
        ]);

        \Log::info('Lab invoice created with dynamic pricing', [
            'lab_request_id' => $labRequest->id,
            'invoice_id' => $invoice->id,
            'total_cost' => $totalCost,
            'items_count' => count($items)
        ]);

        return $invoice;
    }

    /**
     * Complete walk-in lab service
     */
    public function completeWalkInService(Visit $visit)
    {
        if ($visit->status !== 'active' || $visit->visit_type !== 'LabOnly') {
            return redirect()->back()
                ->with('error', 'Visit is not active or not a lab-only visit.');
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

            return redirect()->route('walk-ins.index')
                ->with('success', 'Walk-in lab service completed successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error completing walk-in lab service: ' . $e->getMessage());
        }
    }

    /**
     * Show patient's own lab results (for patient portal)
     */
    public function myResults(Request $request)
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

        // Get all lab requests for this patient
        // Only show requests with verified and approved results
        $query = LabRequest::with([
            'doctor', 
            'technician', 
            'testType.template', 
            'testType.category', 
            'template', 
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
        // Note: Status filter is not applied here because we only show completed requests with approved results
        // The status filter would be misleading since we're filtering by approval status, not request status
        
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $labRequests = $query->paginate(15);

        // Get statistics - Only count requests with verified and approved results
        $statistics = [
            'total' => LabRequest::where('patient_id', $patient->id)
                ->where('status', 'completed')
                ->whereHas('results', function($q) {
                    $q->whereNotNull('result_verified_at')
                      ->whereNotNull('result_approved_at')
                      ->whereNotNull('result_entered_at');
                })
                ->count(),
            'completed' => LabRequest::where('patient_id', $patient->id)
                ->where('status', 'completed')
                ->whereHas('results', function($q) {
                    $q->whereNotNull('result_verified_at')
                      ->whereNotNull('result_approved_at')
                      ->whereNotNull('result_entered_at');
                })
                ->count(),
            'pending' => LabRequest::where('patient_id', $patient->id)
                ->where('status', 'pending')->count(),
            'in_progress' => LabRequest::where('patient_id', $patient->id)
                ->where('status', 'in_progress')->count(),
        ];

        return view('lab.my-results', compact('labRequests', 'statistics', 'patient'));
    }

    /**
     * Show patient's own lab result details (for patient portal)
     */
    public function myResultDetails(LabRequest $labRequest)
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

        // Verify lab request has verified and approved results before showing
        $hasApprovedResults = $labRequest->results()
            ->whereNotNull('result_verified_at')
            ->whereNotNull('result_approved_at')
            ->whereNotNull('result_entered_at')
            ->exists();

        if (!$hasApprovedResults) {
            abort(404, 'Lab results are not yet available. Results must be verified and approved before they can be viewed.');
        }

        // Load all necessary relationships - only verified and approved results
        $labRequest->load([
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
                  ->with(['parameter', 'performedBy', 'verifiedBy', 'approvedBy'])
                  ->orderBy('parameter_id');
            }
        ]);

        return view('lab.my-result-details', compact('labRequest', 'patient'));
    }

    public function export(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_lab_requests');

        $query = LabRequest::with(['patient', 'doctor'])
            ->whereHas('patient')
            ->where('branch_id', $branchId);

        $this->applyDoctorLabScope($query);
        $query->latest('id');

        return $this->exportFromQuery($request, $query, [
            'Request #' => 'request_number',
            'Patient' => fn ($r) => $r->patient?->full_name ?? '',
            'Patient Number' => fn ($r) => $r->patient?->patient_number ?? '',
            'Doctor' => fn ($r) => $this->formatExportUserName($r->doctor),
            'Status' => 'status',
            'Priority' => 'priority',
            'Requested At' => fn ($r) => $this->formatExportDate($r->created_at, 'Y-m-d H:i'),
        ], 'lab-requests', 'view_lab_requests');
    }
}
