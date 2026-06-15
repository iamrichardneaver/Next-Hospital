<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InsurancePolicy;
use App\Models\InsuranceClaim;
use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Models\PreAuthorization;
use App\Models\InsuranceCoveragePolicy;
use App\Services\InsuranceCoverageService;
use App\Services\InsuranceReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsuranceController extends Controller
{
    protected $insuranceCoverageService;
    protected $insuranceReportingService;

    public function __construct(InsuranceCoverageService $insuranceCoverageService, InsuranceReportingService $insuranceReportingService)
    {
        $this->insuranceCoverageService = $insuranceCoverageService;
        $this->insuranceReportingService = $insuranceReportingService;
    }
    public function index()
    {
        $policies = InsurancePolicy::with(['patient', 'insuranceProvider'])
            ->latest('id')
            ->paginate(20);
        
        $statistics = [
            'total_policies' => InsurancePolicy::count(),
            'active_policies' => InsurancePolicy::where('is_active', true)
                ->where('end_date', '>=', now())
                ->count(),
            'total_claims' => InsuranceClaim::count(),
            'pending_claims' => InsuranceClaim::where('status', 'pending')->count(),
        ];
        
        return view('insurance.index', compact('policies', 'statistics'));
    }
    
    public function policies()
    {
        $policies = InsurancePolicy::with(['patient', 'insuranceProvider'])
            ->latest('id')
            ->paginate(20);
        
        $patients = Patient::latest()->get();
        $providers = InsuranceProvider::where('is_active', true)->get();
        
        return view('insurance.policies', compact('policies', 'patients', 'providers'));
    }
    
    public function claims()
    {
        $claims = InsuranceClaim::with(['patient', 'policy.insuranceProvider', 'visit'])
            ->latest('id')
            ->paginate(20);
        
        $statistics = [
            'total' => InsuranceClaim::count(),
            'pending' => InsuranceClaim::where('status', 'pending')->count(),
            'approved' => InsuranceClaim::where('status', 'approved')->count(),
            'rejected' => InsuranceClaim::where('status', 'rejected')->count(),
            'total_amount' => InsuranceClaim::sum('total_amount'),
            'covered_amount' => InsuranceClaim::sum('covered_amount'),
        ];
        
        return view('insurance.claims', compact('claims', 'statistics'));
    }
    
    public function storePolicy(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'provider_id' => 'required|exists:insurance_providers,id',
                'policy_number' => 'required|string|unique:insurance_policies',
                'coverage_type' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
            ]);
            
            $validated['insurance_provider_id'] = $validated['provider_id'];
            unset($validated['provider_id']);
            $validated['is_active'] = true;
            $validated['created_by'] = auth()->id();
            
            InsurancePolicy::create($validated);
            
            return redirect()->route('insurance.policies')
                ->with('success', 'Insurance policy added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating insurance policy: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create insurance policy. Please try again.');
        }
    }
    
    public function storeClaim(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'policy_id' => 'required|exists:insurance_policies,id',
                'visit_id' => 'nullable|exists:visits,id',
                'total_amount' => 'required|numeric|min:0',
                'claim_items' => 'required|array|min:1',
                'claim_items.*.service_type' => 'required|string',
                'claim_items.*.description' => 'required|string',
                'claim_items.*.amount' => 'required|numeric|min:0',
                'claim_items.*.quantity' => 'nullable|integer|min:1',
            ]);
            
            $validated['status'] = 'pending';
            $validated['submitted_date'] = now();
            $validated['created_by'] = auth()->id();
            
            // Calculate covered amount based on policy coverage
            $policy = InsurancePolicy::find($validated['policy_id']);
            $coveragePercentage = $policy->coverage_percentage ?? 80;
            $validated['covered_amount'] = ($validated['total_amount'] * $coveragePercentage) / 100;
            $validated['co_pay_amount'] = $validated['total_amount'] - $validated['covered_amount'];
            $validated['insurance_provider_id'] = $policy->insurance_provider_id;
            
            $claim = InsuranceClaim::create($validated);
            
            // Create claim items
            foreach ($request->claim_items as $item) {
                $itemAmount = $item['amount'];
                $itemQuantity = $item['quantity'] ?? 1;
                $unitPrice = $itemAmount / $itemQuantity;
                
                $claim->claimItems()->create([
                    'service_type' => $item['service_type'],
                    'description' => $item['description'],
                    'quantity' => $itemQuantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $itemAmount,
                    'covered_amount' => ($itemAmount * $coveragePercentage) / 100,
                    'co_pay_amount' => $itemAmount - (($itemAmount * $coveragePercentage) / 100),
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('insurance.claims')
                ->with('success', 'Insurance claim submitted successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating insurance claim: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create insurance claim. Please try again.');
        }
    }

    /**
     * Show insurance providers management
     */
    public function providers()
    {
        $providers = InsuranceProvider::with('creator')
            ->latest('id')
            ->paginate(20);
        
        $statistics = [
            'total_providers' => InsuranceProvider::count(),
            'active_providers' => InsuranceProvider::where('is_active', true)->count(),
            'total_policies' => InsurancePolicy::count(),
            'total_claims' => InsuranceClaim::count(),
        ];
        
        return view('insurance.providers', compact('providers', 'statistics'));
    }

    /**
     * Store a new insurance provider
     */
    public function storeProvider(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|unique:insurance_providers,code',
                'type' => 'required|in:private,public,corporate,government',
                'contact_person' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'website' => 'nullable|url',
                'default_coverage_percentage' => 'nullable|numeric|min:0|max:100',
                'default_co_pay_percentage' => 'nullable|numeric|min:0|max:100',
                'requires_pre_authorization' => 'nullable|boolean',
                'supports_electronic_claims' => 'nullable|boolean',
                'supports_real_time_verification' => 'nullable|boolean',
            ]);
            
            $validated['is_active'] = true;
            $validated['created_by'] = auth()->id();
            
            InsuranceProvider::create($validated);
            
            return redirect()->route('insurance.providers')
                ->with('success', 'Insurance provider added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating insurance provider: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create insurance provider. Please try again.');
        }
    }

    public function editProvider(InsuranceProvider $provider)
    {
        return view('insurance.providers-edit', compact('provider'));
    }

    public function updateProvider(Request $request, InsuranceProvider $provider)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|unique:insurance_providers,code,' . $provider->id,
                'type' => 'required|in:private,public,corporate,government',
                'contact_person' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'website' => 'nullable|url',
                'default_coverage_percentage' => 'nullable|numeric|min:0|max:100',
                'default_co_pay_percentage' => 'nullable|numeric|min:0|max:100',
                'requires_pre_authorization' => 'nullable|boolean',
                'supports_electronic_claims' => 'nullable|boolean',
                'supports_real_time_verification' => 'nullable|boolean',
                'is_active' => 'nullable|boolean',
            ]);

            $validated['requires_pre_authorization'] = (bool) ($validated['requires_pre_authorization'] ?? false);
            $validated['supports_electronic_claims'] = (bool) ($validated['supports_electronic_claims'] ?? false);
            $validated['supports_real_time_verification'] = (bool) ($validated['supports_real_time_verification'] ?? false);
            $validated['is_active'] = (bool) ($validated['is_active'] ?? $provider->is_active);

            $provider->update($validated);

            return redirect()->route('insurance.providers')
                ->with('success', 'Insurance provider updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating insurance provider: ' . $e->getMessage(), [
                'provider_id' => $provider->id,
            ]);

            return back()->withInput()->with('error', 'Failed to update insurance provider.');
        }
    }

    public function editPolicy(InsurancePolicy $policy)
    {
        $providers = InsuranceProvider::where('is_active', true)->get();
        $patients = Patient::orderBy('first_name')->limit(200)->get();

        return view('insurance.policies-edit', compact('policy', 'providers', 'patients'));
    }

    public function updatePolicy(Request $request, InsurancePolicy $policy)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'insurance_provider_id' => 'required|exists:insurance_providers,id',
                'policy_number' => 'required|string|unique:insurance_policies,policy_number,' . $policy->id,
                'coverage_type' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'coverage_percentage' => 'nullable|numeric|min:0|max:100',
                'is_active' => 'nullable|boolean',
            ]);

            $validated['is_active'] = (bool) ($validated['is_active'] ?? $policy->is_active);
            $validated['updated_by'] = auth()->id();

            $policy->update($validated);

            return redirect()->route('insurance.policies')
                ->with('success', 'Insurance policy updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating insurance policy: ' . $e->getMessage(), [
                'policy_id' => $policy->id,
            ]);

            return back()->withInput()->with('error', 'Failed to update insurance policy.');
        }
    }

    /**
     * Show pre-authorization requests
     */
    public function preAuthorizations()
    {
        $preAuthorizations = PreAuthorization::with([
                'patient', 
                'policy.insuranceProvider', 
                'requestedBy', 
                'approvedBy'
            ])
            ->latest('id')
            ->paginate(20);
        
        $statistics = [
            'total' => PreAuthorization::count(),
            'pending' => PreAuthorization::where('status', 'pending')->count(),
            'approved' => PreAuthorization::where('status', 'approved')->count(),
            'rejected' => PreAuthorization::where('status', 'rejected')->count(),
            'expired' => PreAuthorization::where('expiry_date', '<', now())->count(),
        ];
        
        return view('insurance.pre-authorizations', compact('preAuthorizations', 'statistics'));
    }

    /**
     * Store a new pre-authorization request
     */
    public function storePreAuthorization(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'policy_id' => 'required|exists:insurance_policies,id',
                'service_type' => 'required|string',
                'service_code' => 'nullable|string',
                'requested_amount' => 'required|numeric|min:0',
                'description' => 'required|string',
                'urgency' => 'required|in:routine,urgent,emergency',
                'clinical_justification' => 'nullable|string',
            ]);
            
            // Map description to service_description
            $validated['service_description'] = $validated['description'];
            unset($validated['description']);
            
            // Map clinical_justification to notes if provided
            if (isset($validated['clinical_justification'])) {
                $validated['notes'] = $validated['clinical_justification'];
                unset($validated['clinical_justification']);
            }
            
            $validated['status'] = 'pending';
            $validated['request_date'] = now();
            $validated['expiry_date'] = now()->addDays(30); // Default 30 days expiry
            $validated['requested_by'] = auth()->id();
            
            PreAuthorization::create($validated);
            
            return redirect()->route('insurance.pre-authorizations')
                ->with('success', 'Pre-authorization request submitted successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating pre-authorization: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create pre-authorization request. Please try again.');
        }
    }

    /**
     * Approve or reject a pre-authorization
     */
    public function updatePreAuthorization(Request $request, PreAuthorization $preAuthorization)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:approved,rejected',
                'approved_amount' => 'nullable|numeric|min:0',
                'approval_notes' => 'nullable|string',
                'expiry_date' => 'nullable|date|after:now',
            ]);
            
            $validated['approval_date'] = now();
            $validated['approved_by'] = auth()->id();
            
            if ($validated['status'] === 'approved') {
                $validated['approved_amount'] = $validated['approved_amount'] ?: $preAuthorization->requested_amount;
                $validated['expiry_date'] = $validated['expiry_date'] ?: now()->addDays(30);
            }
            
            $preAuthorization->update($validated);
            
            $statusMessage = $validated['status'] === 'approved' ? 'approved' : 'rejected';
            
            return redirect()->route('insurance.pre-authorizations')
                ->with('success', "Pre-authorization {$statusMessage} successfully!");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating pre-authorization: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'pre_authorization_id' => $preAuthorization->id,
                'request_data' => $request->except(['_token', '_method']),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update pre-authorization. Please try again.');
        }
    }

    /**
     * Show insurance analytics and reports
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        
        $analytics = $this->insuranceReportingService->getInsuranceAnalytics($dateFrom, $dateTo);
        
        return view('insurance.analytics', compact('analytics', 'dateFrom', 'dateTo'));
    }

    /**
     * Check insurance coverage for a service
     */
    public function checkCoverage(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'service_type' => 'required|string',
            'service_code' => 'nullable|string',
            'service_amount' => 'required|numeric|min:0',
            'service_date' => 'nullable|date',
        ]);
        
        $coverage = $this->insuranceCoverageService->calculateCoverage(
            $validated['patient_id'],
            $validated['service_type'],
            $validated['service_code'],
            $validated['service_amount'],
            $validated['service_date']
        );
        
        return response()->json([
            'success' => true,
            'coverage' => $coverage
        ]);
    }

    /**
     * Get patient insurance policies for API
     */
    public function getPatientPolicies(Patient $patient)
    {
        $policies = $patient->insurancePolicies()
            ->with('insuranceProvider')
            ->where('is_active', true)
            ->where('end_date', '>=', now())
            ->get();
        
        return response()->json([
            'success' => true,
            'policies' => $policies
        ]);
    }

    /**
     * Approve or reject an insurance claim (web session + CSRF).
     */
    public function updateClaimStatus(Request $request, InsuranceClaim $claim)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:approved,rejected,pending,processing',
                'notes' => 'nullable|string|max:1000',
            ]);

            $claim->update([
                'status' => $validated['status'],
                'processed_date' => in_array($validated['status'], ['approved', 'rejected'], true) ? now() : $claim->processed_date,
                'processed_by' => auth()->id(),
                'notes' => $validated['notes'] ?? $claim->notes,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Claim status updated successfully.',
                    'claim' => $claim->fresh(),
                ]);
            }

            return redirect()->route('insurance.claims')
                ->with('success', 'Claim status updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $e->errors()], 422);
            }

            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Error updating insurance claim status: ' . $e->getMessage(), [
                'claim_id' => $claim->id,
                'user_id' => auth()->id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update claim status.'], 500);
            }

            return back()->with('error', 'Failed to update claim status.');
        }
    }

    /**
     * Export insurance reports to PDF
     */
    public function exportReport(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $reportType = $request->get('report_type', 'analytics');
        
        $analytics = $this->insuranceReportingService->getInsuranceAnalytics($dateFrom, $dateTo);
        
        $pdf = app('App\Services\InsurancePdfService')->generateInsuranceReport($analytics, $reportType);
        
        return $pdf->download("insurance-report-{$reportType}-{$dateFrom}-to-{$dateTo}.pdf");
    }
}
