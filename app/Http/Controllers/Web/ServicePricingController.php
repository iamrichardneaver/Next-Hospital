<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ServicePricing;
use App\Services\ModulePricingService;
use App\Models\Branch;
use App\Models\Drug;
use App\Models\AppointmentFee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ServicePricingController extends Controller
{
    protected array $pricingTypes = [
        ServicePricing::PRICING_TYPE_MODULE_FEE => 'Module Fee (additive administrative charge)',
        ServicePricing::PRICING_TYPE_ITEM_OVERRIDE => 'Item Override (replaces module item price)',
        ServicePricing::PRICING_TYPE_STANDALONE => 'Standalone (fixed service charge)',
    ];

    protected array $appliesOnOptions = [
        'visit_checkin' => 'Visit Check-in (OPD/IPD/Emergency consultation fee)',
        'order_created' => 'Order Created (lab, pharmacy, radiology, surgery)',
        'appointment_booked' => 'Appointment Booked (not on visit check-in)',
        'manual' => 'Manual Only (cashier invoice — never auto-applied)',
    ];

    public function __construct()
    {
        $this->middleware('permission:view_service_pricing|manage_service_pricing')->only([
            'index', 'show', 'priceList', 'export',
        ]);
        $this->middleware('permission:create_service_pricing|manage_service_pricing')->only([
            'create', 'store', 'bulkImport',
        ]);
        $this->middleware('permission:edit_service_pricing|manage_service_pricing')->only([
            'edit', 'update', 'toggleActive',
        ]);
        $this->middleware('permission:delete_service_pricing|manage_service_pricing')->only([
            'destroy',
        ]);
    }
    /**
     * Display a listing of all service pricing.
     */
    public function index(Request $request)
    {
        $query = ServicePricing::with(['branch', 'creator']);
        
        // Filter by service type
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }
        
        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('service_name', 'like', "%{$search}%")
                  ->orWhere('service_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        $servicePricing = $query->orderBy('id', 'desc')->paginate(20);
        $branches = Branch::where('is_active', true)->get();
        
        // Get service types for filter
        $serviceTypes = ServicePricing::distinct()->pluck('service_type');
        
        // Get appointment fees
        $appointmentFeesQuery = AppointmentFee::with(['doctor', 'branch', 'creator']);
        
        // Apply same branch filter to appointment fees
        if ($request->filled('branch_id')) {
            $appointmentFeesQuery->where('branch_id', $request->branch_id);
        }
        
        // Apply active status filter
        if ($request->filled('is_active')) {
            $appointmentFeesQuery->where('is_active', $request->is_active);
        }
        
        // Search appointment fees
        if ($request->filled('search')) {
            $search = $request->search;
            $appointmentFeesQuery->where(function($q) use ($search) {
                $q->where('fee_category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('doctor', function($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $appointmentFees = $appointmentFeesQuery->orderBy('id', 'desc')->paginate(20);
        
        // Get doctors for appointment fees
        $doctors = User::role('doctor')->orderBy('name')->get();
        
        // Statistics
        $stats = [
            'total_services' => ServicePricing::count(),
            'active_services' => ServicePricing::where('is_active', true)->count(),
            'total_revenue_potential' => ServicePricing::where('is_active', true)->sum('base_price'),
            'service_categories' => ServicePricing::select('service_type', DB::raw('count(*) as count'))
                ->groupBy('service_type')
                ->get(),
            'total_appointment_fees' => AppointmentFee::count(),
            'active_appointment_fees' => AppointmentFee::where('is_active', true)->count(),
            'in_person_fees' => AppointmentFee::where('appointment_type', 'in-person')->active()->count(),
            'teleconsultation_fees' => AppointmentFee::where('appointment_type', 'teleconsultation')->active()->count(),
        ];
        
        $pricingTypes = $this->pricingTypes;
        $moduleCodes = ModulePricingService::MODULE_CODES;

        return view('pricing.index', compact(
            'servicePricing',
            'appointmentFees',
            'branches',
            'doctors',
            'serviceTypes',
            'stats',
            'pricingTypes',
            'moduleCodes'
        ));
    }

    /**
     * Show the form for creating a new service pricing.
     */
    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        $serviceTypes = [
            'consultation' => 'Consultation',
            'lab_test' => 'Laboratory Test',
            'imaging' => 'Imaging/Radiology',
            'procedure' => 'Medical Procedure',
            'surgery' => 'Surgery',
            'drug' => 'Medication',
            'bed' => 'Bed Charges',
            'emergency' => 'Emergency Services',
            'vaccination' => 'Vaccination',
            'therapy' => 'Therapy',
            'other' => 'Other Services'
        ];
        
        return view('pricing.create', [
            'branches' => $branches,
            'serviceTypes' => $serviceTypes,
            'pricingTypes' => $this->pricingTypes,
            'moduleCodes' => ModulePricingService::MODULE_CODES,
            'appliesOnOptions' => $this->appliesOnOptions,
        ]);
    }

    /**
     * Store a newly created service pricing in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $this->validatePricingRequest($request);
            $validated['created_by'] = Auth::id();
            $validated['currency'] = $validated['currency'] ?? 'GHS';
            $validated = $this->normalizePricingPayload($validated);

            ServicePricing::create($validated);
            
            return redirect()->route('pricing.index')
                ->with('success', 'Service pricing created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error creating service pricing: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create service pricing. Please try again.');
        }
    }

    /**
     * Display the specified service pricing.
     */
    public function show(ServicePricing $pricing)
    {
        $pricing->load(['branch', 'creator', 'updater']);
        
        // Get usage statistics for this service
        $usageStats = $this->getServiceUsageStats($pricing);
        
        return view('pricing.show', compact('pricing', 'usageStats'));
    }

    /**
     * Show the form for editing the specified service pricing.
     */
    public function edit(ServicePricing $pricing)
    {
        $branches = Branch::where('is_active', true)->get();
        $serviceTypes = [
            'consultation' => 'Consultation',
            'lab_test' => 'Laboratory Test',
            'imaging' => 'Imaging/Radiology',
            'procedure' => 'Medical Procedure',
            'surgery' => 'Surgery',
            'drug' => 'Medication',
            'bed' => 'Bed Charges',
            'emergency' => 'Emergency Services',
            'vaccination' => 'Vaccination',
            'therapy' => 'Therapy',
            'other' => 'Other Services'
        ];
        
        return view('pricing.edit', [
            'pricing' => $pricing,
            'branches' => $branches,
            'serviceTypes' => $serviceTypes,
            'pricingTypes' => $this->pricingTypes,
            'moduleCodes' => ModulePricingService::MODULE_CODES,
            'appliesOnOptions' => $this->appliesOnOptions,
        ]);
    }

    /**
     * Update the specified service pricing in storage.
     */
    public function update(Request $request, ServicePricing $pricing)
    {
        try {
            $validated = $this->validatePricingRequest($request, $pricing->id);
            $validated['updated_by'] = Auth::id();
            $validated = $this->normalizePricingPayload($validated);

            $pricing->update($validated);
            
            return redirect()->route('pricing.index')
                ->with('success', 'Service pricing updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error updating service pricing: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'pricing_id' => $pricing->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update service pricing. Please try again.');
        }
    }

    /**
     * Remove the specified service pricing from storage.
     */
    public function destroy(ServicePricing $pricing)
    {
        try {
            $pricing->delete();
            
            return redirect()->route('pricing.index')
                ->with('success', 'Service pricing deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting service pricing: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'pricing_id' => $pricing->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete service pricing. Please try again.');
        }
    }

    /**
     * Toggle active status of service pricing.
     */
    public function toggleActive(ServicePricing $pricing)
    {
        $pricing->update([
            'is_active' => !$pricing->is_active,
            'updated_by' => Auth::id()
        ]);
        
        return back()->with('success', 'Service status updated successfully.');
    }

    /**
     * Bulk import service pricing.
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
            'branch_id' => 'required|exists:branches,id'
        ]);
        
        try {
            $file = $request->file('file');
            $branchId = $request->branch_id;
            
            // Read CSV file
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $headers = array_shift($csvData); // Remove header row
            
            $imported = 0;
            $errors = [];
            
            foreach ($csvData as $row) {
                try {
                    $data = array_combine($headers, $row);
                    
                    // Validate required fields
                    if (empty($data['service_id']) || empty($data['service_name']) || empty($data['base_price'])) {
                        $errors[] = "Row missing required fields: " . implode(', ', $row);
                        continue;
                    }
                    
                    // Check if service already exists
                    if (ServicePricing::where('service_id', $data['service_id'])->exists()) {
                        $errors[] = "Service ID '{$data['service_id']}' already exists";
                        continue;
                    }
                    
                    // Create service pricing
                    ServicePricing::create([
                        'service_id' => $data['service_id'],
                        'service_name' => $data['service_name'],
                        'service_type' => $data['service_type'] ?? 'other',
                        'branch_id' => $branchId,
                        'base_price' => $data['base_price'],
                        'currency' => $data['currency'] ?? 'GHS',
                        'description' => $data['description'] ?? null,
                        'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
                        'requires_approval' => isset($data['requires_approval']) ? (bool)$data['requires_approval'] : false,
                        'created_by' => Auth::id()
                    ]);
                    
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Error processing row: " . $e->getMessage();
                }
            }
            
            $message = "Successfully imported {$imported} services.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " and " . (count($errors) - 5) . " more errors.";
                }
            }
            
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Export service pricing to CSV.
     */
    public function export(Request $request)
    {
        $query = ServicePricing::with(['branch']);
        
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        $servicePricing = $query->get();
        
        $filename = 'service-pricing-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($servicePricing) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Service ID', 'Service Name', 'Service Type', 'Pricing Type',
                'Modules', 'Additive', 'Branch', 'Base Price', 'Currency', 'Status',
            ]);

            foreach ($servicePricing as $service) {
                fputcsv($file, [
                    $service->service_id,
                    $service->service_name,
                    $service->service_type,
                    $service->pricing_type ?? 'standalone',
                    implode(', ', $service->module_codes ?? []),
                    ($service->is_additive ?? false) ? 'Yes' : 'No',
                    $service->branch->name ?? 'N/A',
                    $service->base_price,
                    $service->currency,
                    $service->is_active ? 'Active' : 'Inactive',
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get comprehensive price list (all services) - AGGREGATED FROM ALL MODULES
     */
    public function priceList(Request $request)
    {
        $branchId = $request->get('branch_id');
        $serviceType = $request->get('service_type');
        
        // STEP 1: Get centralized service pricing
        $servicePricing = ServicePricing::where('is_active', true)
            ->when($branchId, function($query, $branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->when($serviceType, function($query, $serviceType) {
                return $query->where('service_type', $serviceType);
            })
            ->orderBy('service_type')
            ->orderBy('service_name')
            ->get();
        
        // STEP 2: INTEGRATE LAB TEST PRICING from lab_test_templates
        if (!$serviceType || $serviceType === 'lab_test') {
            $labTests = \App\Models\LabTestTemplate::where('is_active', true)
                ->whereNotNull('cost')
                ->where('cost', '>', 0)
                ->get()
                ->map(function($test) {
                    return (object)[
                        'service_id' => 'LAB-' . $test->id,
                        'service_name' => $test->template_name,
                        'service_type' => 'lab_test',
                        'base_price' => $test->cost,
                        'description' => $test->description ?? 'Laboratory Test',
                        'currency' => 'GHS',
                        'nhis_price' => $test->nhis_cost,
                        'nhis_covered' => $test->nhis_covered,
                        'source' => 'lab_test_templates'
                    ];
                });
            
            // Merge with existing lab_test services
            $servicePricing = $servicePricing->merge($labTests);
        }
        
        // STEP 3: INTEGRATE EYE SERVICES PRICING
        if (!$serviceType || $serviceType === 'eye_service' || $serviceType === 'procedure') {
            $eyeServices = \App\Models\EyeService::where('is_active', true)
                ->whereNotNull('base_price')
                ->where('base_price', '>', 0)
                ->get()
                ->map(function($service) {
                    return (object)[
                        'service_id' => 'EYE-' . $service->id,
                        'service_name' => $service->service_name,
                        'service_type' => 'eye_service',
                        'base_price' => $service->base_price,
                        'description' => $service->description ?? 'Eye Care Service',
                        'currency' => $service->currency ?? 'GHS',
                        'source' => 'eye_services'
                    ];
                });
            
            $servicePricing = $servicePricing->merge($eyeServices);
        }
        
        // STEP 4: INTEGRATE RADIOLOGY/IMAGING PRICING (if imaging modalities have pricing)
        if (!$serviceType || $serviceType === 'imaging' || $serviceType === 'radiology') {
            $imagingServices = \App\Models\ImagingModality::where('is_active', true)
                ->whereNotNull('base_cost')
                ->where('base_cost', '>', 0)
            ->get()
                ->map(function($modality) {
                    return (object)[
                        'service_id' => 'IMG-' . $modality->id,
                        'service_name' => $modality->name,
                        'service_type' => 'imaging',
                        'base_price' => $modality->base_cost,
                        'description' => $modality->description ?? 'Imaging Service',
                        'currency' => 'GHS',
                        'source' => 'imaging_modalities'
                    ];
                });
            
            $servicePricing = $servicePricing->merge($imagingServices);
        }
        
        // Re-group by service_type after merging all sources
        $servicePricing = $servicePricing->groupBy('service_type');
        
        // Get drug pricing (only if no service_type filter or if explicitly requesting pharmacy)
        $drugs = collect([]);
        if (!$serviceType || $serviceType === 'pharmacy' || $serviceType === 'medication') {
        $drugs = Drug::where('is_active', true)
                ->select('name', 'generic_name', 'dosage_form', 'strength', 'selling_price', 'nhis_price', 'nhis_covered')
            ->orderBy('name')
            ->get();
        }
        
        // Get appointment fees (only if no service_type filter or if requesting consultation)
        $appointmentFees = collect([]);
        if (!$serviceType || $serviceType === 'consultation') {
        $appointmentFees = AppointmentFee::with(['doctor', 'branch'])
            ->where('is_active', true)
            ->when($branchId, function($query, $branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->get()
            ->groupBy('fee_category');
        }
        
        $branches = Branch::where('is_active', true)->get();
        
        return view('pricing.price-list', compact('servicePricing', 'drugs', 'appointmentFees', 'branches'));
    }

    private function validatePricingRequest(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = Rule::unique('service_pricing')
            ->where(fn ($query) => $query->where('branch_id', $request->branch_id));

        if ($ignoreId) {
            $uniqueRule->ignore($ignoreId);
        }

        return $request->validate([
            'service_id' => ['required', $uniqueRule],
            'service_name' => 'required|string|max:255',
            'service_type' => 'required|string',
            'pricing_type' => 'required|in:module_fee,item_override,standalone',
            'is_additive' => 'nullable|boolean',
            'module_codes' => 'nullable|array',
            'module_codes.*' => 'string|in:' . implode(',', ModulePricingService::MODULE_CODES),
            'applies_on' => 'nullable|in:visit_checkin,order_created,appointment_booked,manual',
            'branch_id' => 'required|exists:branches,id',
            'base_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'requires_approval' => 'boolean',
        ]);
    }

    private function normalizePricingPayload(array $validated): array
    {
        $pricingType = $validated['pricing_type'] ?? ServicePricing::PRICING_TYPE_STANDALONE;

        if ($pricingType === ServicePricing::PRICING_TYPE_MODULE_FEE) {
            $validated['is_additive'] = true;
            $validated['module_codes'] = array_values($validated['module_codes'] ?? []);
        } else {
            $validated['is_additive'] = (bool) ($validated['is_additive'] ?? false);
            if ($pricingType === ServicePricing::PRICING_TYPE_ITEM_OVERRIDE) {
                $validated['module_codes'] = null;
            }
        }

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['requires_approval'] = (bool) ($validated['requires_approval'] ?? false);

        return $validated;
    }

    /**
     * Get service usage statistics.
     */
    private function getServiceUsageStats($service)
    {
        $query = \App\Models\RevenueTransaction::query()
            ->where('status', 'completed')
            ->where(function ($q) use ($service) {
                $q->whereJsonContains('metadata->service_id', $service->service_id)
                    ->orWhereJsonContains('metadata->service_pricing_id', $service->id);
            });

        $timesUsed = (clone $query)->count();
        $totalRevenue = (clone $query)->sum('amount');
        $lastUsedAt = (clone $query)->latest('transaction_date')->value('transaction_date');

        return [
            'times_used' => $timesUsed,
            'total_revenue' => round((float) $totalRevenue, 2),
            'last_used_at' => $lastUsedAt,
        ];
    }
}

