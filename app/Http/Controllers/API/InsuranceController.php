<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\InsuranceProvider;
use App\Models\InsurancePolicy;
use App\Models\InsuranceClaim;
use App\Models\ClaimItem;
use App\Models\PreAuthorization;
use App\Models\InsuranceServiceCategory;
use App\Models\InsuranceCoveragePolicy;
use App\Models\Patient;
use App\Models\Invoice;
use App\Services\InsuranceCoverageService;
use App\Services\InsuranceReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InsuranceController extends Controller
{
    protected $insuranceCoverageService;
    protected $insuranceReportingService;

    public function __construct(InsuranceCoverageService $insuranceCoverageService, InsuranceReportingService $insuranceReportingService)
    {
        $this->insuranceCoverageService = $insuranceCoverageService;
        $this->insuranceReportingService = $insuranceReportingService;
    }
    /**
     * Display a listing of insurance providers.
     */
    public function index(Request $request)
    {
        $query = InsuranceProvider::orderBy('name');

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $providers = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $providers,
            'message' => 'Insurance providers retrieved successfully'
        ]);
    }

    /**
     * Store a newly created insurance provider.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:insurance_providers,code',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string',
            'api_endpoint' => 'nullable|url',
            'api_username' => 'nullable|string',
            'api_password' => 'nullable|string',
            'coverage_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $provider = InsuranceProvider::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $provider,
            'message' => 'Insurance provider created successfully'
        ], 201);
    }

    /**
     * Display the specified insurance provider.
     */
    public function show($id)
    {
        $provider = InsuranceProvider::with(['policies', 'claims'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $provider,
            'message' => 'Insurance provider retrieved successfully'
        ]);
    }

    /**
     * Update the specified insurance provider.
     */
    public function update(Request $request, $id)
    {
        $provider = InsuranceProvider::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:insurance_providers,code,' . $id,
            'contact_person' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'address' => 'sometimes|string',
            'api_endpoint' => 'nullable|url',
            'api_username' => 'nullable|string',
            'api_password' => 'nullable|string',
            'coverage_percentage' => 'sometimes|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $provider->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $provider,
            'message' => 'Insurance provider updated successfully'
        ]);
    }

    /**
     * Create insurance policy for patient.
     */
    public function createPolicy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'policy_number' => 'required|string|unique:insurance_policies,policy_number',
            'coverage_type' => 'required|in:individual,family,group',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'coverage_percentage' => 'required|numeric|min:0|max:100',
            'annual_limit' => 'nullable|numeric|min:0',
            'deductible' => 'nullable|numeric|min:0',
            'co_pay_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $policy = InsurancePolicy::create([
            'patient_id' => $request->patient_id,
            'insurance_provider_id' => $request->insurance_provider_id,
            'policy_number' => $request->policy_number,
            'coverage_type' => $request->coverage_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'coverage_percentage' => $request->coverage_percentage,
            'annual_limit' => $request->annual_limit,
            'deductible' => $request->deductible,
            'co_pay_amount' => $request->co_pay_amount,
            'is_active' => $request->is_active ?? true,
            'created_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $policy->load(['patient', 'insuranceProvider']),
            'message' => 'Insurance policy created successfully'
        ], 201);
    }

    /**
     * Get patient's insurance policies.
     */
    public function getPatientPolicies($patientId)
    {
        $patient = Patient::findOrFail($patientId);
        $user = auth()->user();

        if ($user->hasRole('patient')) {
            $ownPatient = $user->patient;
            if (!$ownPatient || (int) $ownPatient->id !== (int) $patientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to patient policies'
                ], 403);
            }
        }

        $policies = InsurancePolicy::with(['insuranceProvider'])
            ->where('patient_id', $patientId)
            ->where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $policies,
            'message' => 'Patient insurance policies retrieved successfully'
        ]);
    }

    /**
     * Get authenticated patient's insurance policies.
     */
    public function getMyPolicies()
    {
        $patient = auth()->user()->patient;

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient profile not found'
            ], 404);
        }

        return $this->getPatientPolicies($patient->id);
    }

    /**
     * Register insurance policy for the authenticated patient (patient portal).
     */
    public function registerMyPolicy(Request $request)
    {
        $user = auth()->user();
        $patient = $user->patient;

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient profile not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'policy_number' => 'required|string|unique:insurance_policies,policy_number',
            'coverage_type' => 'nullable|in:individual,family,group',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'coverage_percentage' => 'nullable|numeric|min:0|max:100',
            'annual_limit' => 'nullable|numeric|min:0',
            'deductible' => 'nullable|numeric|min:0',
            'co_pay_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $policy = InsurancePolicy::create([
            'patient_id' => $patient->id,
            'insurance_provider_id' => $request->insurance_provider_id,
            'policy_number' => $request->policy_number,
            'coverage_type' => $request->coverage_type ?? 'individual',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'coverage_percentage' => $request->coverage_percentage ?? 80,
            'annual_limit' => $request->annual_limit,
            'deductible' => $request->deductible,
            'co_pay_amount' => $request->co_pay_amount,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $policy->load(['insuranceProvider']),
            'message' => 'Insurance policy registered successfully'
        ], 201);
    }

    /**
     * List active insurance providers (for patient enrollment picker).
     */
    public function listActiveProviders(Request $request)
    {
        $providers = InsuranceProvider::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'coverage_percentage']);

        return response()->json([
            'success' => true,
            'data' => $providers,
            'message' => 'Insurance providers retrieved successfully'
        ]);
    }


    /**
     * Submit insurance claim.
     */
    public function submitClaim(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'invoice_id' => 'required|exists:invoices,id',
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'policy_number' => 'nullable|string',
            'policy_id' => 'nullable|exists:insurance_policies,id',
            'claim_amount' => 'nullable|numeric|min:0',
            'co_pay_amount' => 'required|numeric|min:0',
            'claim_items' => 'required|array|min:1',
            'claim_items.*.service_type' => 'required|string',
            'claim_items.*.description' => 'required|string',
            'claim_items.*.amount' => 'required|numeric|min:0',
            'claim_items.*.quantity' => 'nullable|integer|min:1',
            'claim_items.*.covered_amount' => 'required|numeric|min:0',
            'claim_items.*.co_pay_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
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
            // Find policy by policy_number if provided, otherwise use policy_id
            $policy = null;
            if ($request->has('policy_number')) {
                $policy = InsurancePolicy::where('policy_number', $request->policy_number)
                    ->where('patient_id', $request->patient_id)
                    ->first();
                if (!$policy) {
                    throw new \Exception('Policy not found with the provided policy number');
                }
            } elseif ($request->has('policy_id')) {
                $policy = InsurancePolicy::findOrFail($request->policy_id);
            } else {
                throw new \Exception('Either policy_number or policy_id is required');
            }

            // Calculate total amount from claim items if not provided
            $totalAmount = $request->claim_amount ?? array_sum(array_column($request->claim_items, 'amount'));

            // Create claim
            $claim = InsuranceClaim::create([
                'patient_id' => $request->patient_id,
                'invoice_id' => $request->invoice_id,
                'insurance_provider_id' => $request->insurance_provider_id,
                'policy_id' => $policy->id,
                'total_amount' => $totalAmount,
                'covered_amount' => $request->claim_amount - $request->co_pay_amount,
                'co_pay_amount' => $request->co_pay_amount,
                'status' => 'submitted',
                'submitted_date' => now()->toDateString(),
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            // Create claim items
            foreach ($request->claim_items as $item) {
                ClaimItem::create([
                    'claim_id' => $claim->id,
                    'service_type' => $item['service_type'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['amount'] / ($item['quantity'] ?? 1),
                    'total_amount' => $item['amount'],
                    'covered_amount' => $item['covered_amount'],
                    'co_pay_amount' => $item['co_pay_amount']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $claim->load(['patient', 'insuranceProvider', 'claimItems']),
                'message' => 'Insurance claim submitted successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error submitting claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get insurance claims.
     */
    public function getClaims(Request $request)
    {
        $query = InsuranceClaim::with(['patient', 'insuranceProvider', 'claimItems'])
            ->orderBy('submitted_date', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by insurance provider
        if ($request->has('insurance_provider_id')) {
            $query->where('insurance_provider_id', $request->insurance_provider_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('submitted_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('submitted_date', '<=', $request->date_to);
        }

        $claims = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $claims,
            'message' => 'Insurance claims retrieved successfully'
        ]);
    }

    /**
     * Update claim status.
     */
    public function updateClaimStatus(Request $request, $claimId)
    {
        $claim = InsuranceClaim::findOrFail($claimId);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:submitted,under_review,approved,rejected,paid',
            'processed_date' => 'nullable|date',
            'processed_amount' => 'nullable|numeric|min:0',
            'rejection_reason' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $claim->update([
            'status' => $request->status,
            'processed_date' => $request->processed_date,
            'processed_amount' => $request->processed_amount,
            'rejection_reason' => $request->rejection_reason,
            'notes' => $request->notes,
            'processed_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $claim->load(['patient', 'insuranceProvider', 'claimItems']),
            'message' => 'Claim status updated successfully'
        ]);
    }

    /**
     * Request pre-authorization.
     */
    public function requestPreAuthorization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'policy_number' => 'nullable|string',
            'policy_id' => 'nullable|exists:insurance_policies,id',
            'service_type' => 'required|string',
            'requested_amount' => 'required|numeric|min:0',
            'service_description' => 'required|string',
            'urgency' => 'required|in:routine,urgent,emergency',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find policy by policy_number if provided
        $policy = null;
        if ($request->has('policy_number')) {
            $policy = InsurancePolicy::where('policy_number', $request->policy_number)
                ->where('patient_id', $request->patient_id)
                ->first();
            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy not found with the provided policy number'
                ], 404);
            }
        } elseif ($request->has('policy_id')) {
            $policy = InsurancePolicy::findOrFail($request->policy_id);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Either policy_number or policy_id is required'
            ], 422);
        }

        $preAuth = PreAuthorization::create([
            'patient_id' => $request->patient_id,
            'insurance_provider_id' => $request->insurance_provider_id,
            'policy_id' => $policy->id,
            'service_type' => $request->service_type,
            'requested_amount' => $request->requested_amount,
            'service_description' => $request->service_description,
            'urgency' => $request->urgency,
            'status' => 'pending',
            'request_date' => now()->toDateString(),
            'notes' => $request->notes,
            'requested_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $preAuth->load(['patient', 'insuranceProvider']),
            'message' => 'Pre-authorization request submitted successfully'
        ], 201);
    }

    /**
     * Get insurance statistics.
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_providers' => InsuranceProvider::count(),
            'active_providers' => InsuranceProvider::where('is_active', true)->count(),
            'total_policies' => InsurancePolicy::whereBetween('start_date', [$dateFrom, $dateTo])->count(),
            'active_policies' => InsurancePolicy::where('is_active', true)->count(),
            'total_claims' => InsuranceClaim::whereBetween('submitted_date', [$dateFrom, $dateTo])->count(),
            'approved_claims' => InsuranceClaim::where('status', 'approved')->whereBetween('submitted_date', [$dateFrom, $dateTo])->count(),
            'rejected_claims' => InsuranceClaim::where('status', 'rejected')->whereBetween('submitted_date', [$dateFrom, $dateTo])->count(),
            'total_claim_amount' => InsuranceClaim::whereBetween('submitted_date', [$dateFrom, $dateTo])->sum('claim_amount'),
            'total_processed_amount' => InsuranceClaim::whereBetween('submitted_date', [$dateFrom, $dateTo])->sum('processed_amount'),
            'claim_approval_rate' => $this->getClaimApprovalRate($dateFrom, $dateTo),
            'top_providers' => $this->getTopProviders($dateFrom, $dateTo)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Insurance statistics retrieved successfully'
        ]);
    }

    /**
     * Get claim approval rate.
     */
    private function getClaimApprovalRate($dateFrom, $dateTo)
    {
        $totalClaims = InsuranceClaim::whereBetween('submitted_date', [$dateFrom, $dateTo])->count();
        $approvedClaims = InsuranceClaim::where('status', 'approved')
            ->whereBetween('submitted_date', [$dateFrom, $dateTo])
            ->count();

        return $totalClaims > 0 ? round(($approvedClaims / $totalClaims) * 100, 2) : 0;
    }

    /**
     * Get top insurance providers by claims.
     */
    private function getTopProviders($dateFrom, $dateTo)
    {
        return InsuranceClaim::whereBetween('submitted_date', [$dateFrom, $dateTo])
            ->join('insurance_providers', 'insurance_claims.insurance_provider_id', '=', 'insurance_providers.id')
            ->selectRaw('insurance_providers.name, COUNT(*) as claim_count, SUM(insurance_claims.claim_amount) as total_amount')
            ->groupBy('insurance_providers.id', 'insurance_providers.name')
            ->orderBy('claim_count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get used annual limit for policy.
     */
    private function getUsedAnnualLimit($policy)
    {
        return InsuranceClaim::where('patient_id', $policy->patient_id)
            ->where('insurance_provider_id', $policy->insurance_provider_id)
            ->where('policy_number', $policy->policy_number)
            ->where('status', 'approved')
            ->whereYear('submitted_date', now()->year)
            ->sum('processed_amount');
    }

    /**
     * Calculate insurance coverage for a service.
     */
    public function calculateCoverage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'service_type' => 'required|string',
            'service_code' => 'nullable|string',
            'service_amount' => 'required|numeric|min:0',
            'service_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $coverage = $this->insuranceCoverageService->calculateCoverage(
                $request->patient_id,
                $request->service_type,
                $request->service_code,
                $request->service_amount,
                $request->service_date
            );

            return response()->json([
                'success' => true,
                'data' => $coverage,
                'message' => 'Insurance coverage calculated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating coverage: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate insurance coverage before service.
     */
    public function validateCoverage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'service_type' => 'required|string',
            'service_code' => 'nullable|string',
            'service_amount' => 'required|numeric|min:0',
            'service_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validation = $this->insuranceCoverageService->validateCoverage(
                $request->patient_id,
                $request->service_type,
                $request->service_code,
                $request->service_amount,
                $request->service_date
            );

            return response()->json([
                'success' => $validation['valid'],
                'data' => $validation,
                'message' => $validation['message']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating coverage: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service categories.
     */
    public function getServiceCategories(Request $request)
    {
        $query = InsuranceServiceCategory::orderBy('sort_order')->orderBy('name');

        if ($request->has('parent_category')) {
            $query->where('parent_category', $request->parent_category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Service categories retrieved successfully'
        ]);
    }

    /**
     * Get coverage policies for a provider.
     */
    public function getCoveragePolicies(Request $request, $providerId)
    {
        $query = InsuranceCoveragePolicy::where('insurance_provider_id', $providerId)
            ->with('serviceCategory')
            ->orderBy('service_type')
            ->orderBy('service_code');

        if ($request->has('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $policies = $query->get();

        return response()->json([
            'success' => true,
            'data' => $policies,
            'message' => 'Coverage policies retrieved successfully'
        ]);
    }

    /**
     * Create coverage policy.
     */
    public function createCoveragePolicy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'service_category_id' => 'nullable|exists:insurance_service_categories,id',
            'service_type' => 'required|string',
            'service_code' => 'nullable|string',
            'coverage_percentage' => 'required|numeric|min:0|max:100',
            'co_pay_percentage' => 'required|numeric|min:0|max:100',
            'max_coverage_amount' => 'nullable|numeric|min:0',
            'min_coverage_amount' => 'nullable|numeric|min:0',
            'deductible' => 'nullable|numeric|min:0',
            'requires_pre_authorization' => 'boolean',
            'pre_authorization_days' => 'nullable|integer|min:0',
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date|after:effective_from'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $policy = InsuranceCoveragePolicy::create(array_merge(
            $request->all(),
            ['created_by' => auth()->id()]
        ));

        return response()->json([
            'success' => true,
            'data' => $policy->load('serviceCategory'),
            'message' => 'Coverage policy created successfully'
        ], 201);
    }

    /**
     * Get pre-authorizations.
     */
    public function getPreAuthorizations(Request $request)
    {
        $query = PreAuthorization::with(['patient', 'insuranceProvider', 'policy'])
            ->orderBy('request_date', 'desc');

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('insurance_provider_id')) {
            $query->where('insurance_provider_id', $request->insurance_provider_id);
        }

        $preAuths = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $preAuths,
            'message' => 'Pre-authorizations retrieved successfully'
        ]);
    }

    /**
     * Create pre-authorization request.
     */
    public function createPreAuthorization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'service_type' => 'required|string',
            'service_code' => 'nullable|string',
            'service_description' => 'required|string',
            'requested_amount' => 'required|numeric|min:0',
            'urgency' => 'required|in:routine,urgent,emergency'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $preAuth = $this->insuranceCoverageService->createPreAuthorization(
                $request->patient_id,
                $request->service_type,
                $request->service_code,
                $request->service_description,
                $request->requested_amount,
                $request->urgency
            );

            return response()->json([
                'success' => true,
                'data' => $preAuth->load(['patient', 'insuranceProvider', 'policy']),
                'message' => 'Pre-authorization request created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating pre-authorization: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pre-authorization status.
     */
    public function updatePreAuthorizationStatus(Request $request, $preAuthId)
    {
        $preAuth = PreAuthorization::findOrFail($preAuthId);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,expired,cancelled',
            'approved_amount' => 'nullable|numeric|min:0',
            'co_pay_amount' => 'nullable|numeric|min:0',
            'approval_notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $preAuth->update(array_merge(
            $request->all(),
            ['approved_by' => auth()->id()]
        ));

        return response()->json([
            'success' => true,
            'data' => $preAuth->load(['patient', 'insuranceProvider', 'policy']),
            'message' => 'Pre-authorization status updated successfully'
        ]);
    }

    /**
     * Get patient insurance statistics.
     */
    public function getPatientInsuranceStats(Request $request, $patientId)
    {
        try {
            $stats = $this->insuranceCoverageService->getPatientInsuranceStats(
                $patientId,
                $request->year
            );

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Patient insurance statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process insurance claim.
     */
    public function processClaim(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'service_type' => 'required|string',
            'service_code' => 'nullable|string',
            'service_description' => 'required|string',
            'service_amount' => 'required|numeric|min:0',
            'service_date' => 'required|date',
            'claim_items' => 'required|array|min:1',
            'claim_items.*.service_type' => 'required|string',
            'claim_items.*.service_code' => 'nullable|string',
            'claim_items.*.description' => 'required|string',
            'claim_items.*.quantity' => 'required|integer|min:1',
            'claim_items.*.unit_price' => 'required|numeric|min:0',
            'claim_items.*.total_amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $claim = $this->insuranceCoverageService->processClaim(
                $request->patient_id,
                $request->service_type,
                $request->service_code,
                $request->service_description,
                $request->service_amount,
                $request->service_date,
                $request->claim_items
            );

            return response()->json([
                'success' => true,
                'data' => $claim->load(['patient', 'insuranceProvider', 'policy', 'claimItems']),
                'message' => 'Insurance claim processed successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive insurance analytics.
     */
    public function getAnalytics(Request $request)
    {
        try {
            $analytics = $this->insuranceReportingService->getInsuranceAnalytics(
                $request->date_from,
                $request->date_to
            );

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Insurance analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate insurance report.
     */
    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:claims_summary,provider_performance,financial_summary,pre_authorization_summary',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'filters' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $report = $this->insuranceReportingService->generateInsuranceReport(
                $request->report_type,
                $request->date_from,
                $request->date_to,
                $request->filters ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Insurance report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get insurance dashboard data.
     */
    public function getDashboardData(Request $request)
    {
        try {
            $dateFrom = $request->date_from ?? now()->subDays(30)->toDateString();
            $dateTo = $request->date_to ?? now()->toDateString();

            $analytics = $this->insuranceReportingService->getInsuranceAnalytics($dateFrom, $dateTo);

            // Get recent activities
            $recentClaims = InsuranceClaim::with(['patient', 'insuranceProvider'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $recentPreAuths = PreAuthorization::with(['patient', 'insuranceProvider'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $dashboardData = [
                'analytics' => $analytics,
                'recent_claims' => $recentClaims,
                'recent_pre_authorizations' => $recentPreAuths,
                'alerts' => $this->getInsuranceAlerts()
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
                'message' => 'Dashboard data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get insurance alerts and notifications.
     */
    private function getInsuranceAlerts()
    {
        $alerts = [];

        // Expiring policies
        $expiringPolicies = InsurancePolicy::where('end_date', '<=', now()->addDays(30))
            ->where('is_active', true)
            ->count();

        if ($expiringPolicies > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Expiring Policies',
                'message' => "{$expiringPolicies} insurance policies will expire within 30 days",
                'action' => 'Review and renew policies'
            ];
        }

        // Pending pre-authorizations
        $pendingPreAuths = PreAuthorization::where('status', 'pending')
            ->where('request_date', '<=', now()->subDays(7))
            ->count();

        if ($pendingPreAuths > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Pending Pre-authorizations',
                'message' => "{$pendingPreAuths} pre-authorization requests are pending for more than 7 days",
                'action' => 'Review pending requests'
            ];
        }

        // Rejected claims
        $rejectedClaims = InsuranceClaim::where('status', 'rejected')
            ->where('processed_date', '>=', now()->subDays(7))
            ->count();

        if ($rejectedClaims > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Rejected Claims',
                'message' => "{$rejectedClaims} insurance claims were rejected in the last 7 days",
                'action' => 'Review rejected claims'
            ];
        }

        return $alerts;
    }
}
