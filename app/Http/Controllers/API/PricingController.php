<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PricingService;
use App\Services\ModulePricingService;
use App\Models\ServicePricing;
use App\Models\PricingRule;
use App\Models\DiscountScheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PricingController extends Controller
{
    protected $pricingService;
    protected $modulePricingService;

    public function __construct(PricingService $pricingService, ModulePricingService $modulePricingService)
    {
        $this->pricingService = $pricingService;
        $this->modulePricingService = $modulePricingService;
    }

    public function calculateServicePrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|string',
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pricing = $this->pricingService->calculateServicePrice(
                $request->service_id,
                $request->patient_id,
                $request->branch_id,
                $request->options ?? []
            );

            $record = $this->pricingService->findServicePricing($request->service_id, $request->branch_id);
            if ($record && $record->pricing_type === ServicePricing::PRICING_TYPE_MODULE_FEE) {
                $pricing['charge_component'] = 'admin_fee';
            }

            return response()->json([
                'success' => true,
                'data' => $pricing,
                'message' => 'Service price calculated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating service price: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function calculateConsultationFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:users,id',
            'consultation_type' => 'required|string',
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $branchId = (int) $request->branch_id;
            $patientId = (int) $request->patient_id;
            $feeRecords = $this->modulePricingService->findModuleFeeRecords(
                'consultation',
                $branchId,
                ModulePricingService::APPLIES_ON_VISIT_CHECKIN
            );

            if ($feeRecords->isNotEmpty()) {
                $chargeLines = [];
                $moduleFee = 0.0;
                foreach ($feeRecords as $feeRecord) {
                    $pricing = $this->pricingService->calculateServicePrice(
                        $feeRecord->service_id,
                        $patientId,
                        $branchId,
                        ['doctor_id' => $request->doctor_id]
                    );
                    $amount = (float) ($pricing['final_price'] ?? $pricing['patient_co_pay'] ?? 0);
                    if ($amount <= 0) {
                        continue;
                    }
                    $moduleFee += $amount;
                    $chargeLines[] = [
                        'description' => $feeRecord->service_name ?? 'Consultation Service Fee',
                        'amount' => $amount,
                        'base_amount' => (float) ($pricing['base_price'] ?? $amount),
                        'discount_amount' => max(0, (float) ($pricing['base_price'] ?? $amount) - (float) ($pricing['calculated_price'] ?? $amount)),
                        'insurance_coverage' => (float) ($pricing['insurance_coverage']['covered_amount'] ?? 0),
                        'patient_copay' => (float) ($pricing['patient_co_pay'] ?? $amount),
                        'final_amount' => $amount,
                        'charge_component' => 'admin_fee',
                        'type' => 'consultation',
                        'service_id' => $feeRecord->service_id,
                    ];
                }

                if ($moduleFee > 0) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'final_price' => round($moduleFee, 2),
                            'module_fee' => round($moduleFee, 2),
                            'charge_lines' => $chargeLines,
                        ],
                        'message' => 'Consultation fee calculated successfully',
                    ]);
                }
            }

            $pricing = $this->pricingService->calculateConsultationFee(
                $request->doctor_id,
                $request->consultation_type,
                $request->patient_id,
                $request->branch_id
            );

            return response()->json([
                'success' => true,
                'data' => $pricing,
                'message' => 'Consultation fee calculated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating consultation fee: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function calculateLabTestPrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_id' => 'required|exists:lab_test_types,id',
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'priority' => 'nullable|in:routine,urgent,stat',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pricing = $this->pricingService->calculateLabTestPrice(
                $request->test_id,
                $request->patient_id,
                $request->branch_id,
                $request->priority ?? 'routine'
            );

            $moduleFee = (float) ($pricing['module_fee'] ?? 0);
            $itemPrice = (float) ($pricing['final_price'] ?? 0);
            $pricing['charge_lines'] = array_values(array_filter([
                $itemPrice > 0 ? [
                    'description' => ($pricing['test_name'] ?? 'Lab Test'),
                    'amount' => $itemPrice,
                    'base_amount' => (float) ($pricing['base_price'] ?? $itemPrice),
                    'discount_amount' => max(0, (float) ($pricing['base_price'] ?? 0) - (float) ($pricing['calculated_price'] ?? $itemPrice)),
                    'insurance_coverage' => (float) ($pricing['insurance_coverage']['covered_amount'] ?? 0),
                    'patient_copay' => $itemPrice,
                    'final_amount' => $itemPrice,
                    'charge_component' => 'module_price',
                    'type' => 'lab_test',
                ] : null,
                $moduleFee > 0 ? [
                    'description' => 'Laboratory Service Fee',
                    'amount' => $moduleFee,
                    'base_amount' => $moduleFee,
                    'discount_amount' => 0,
                    'insurance_coverage' => 0,
                    'patient_copay' => $moduleFee,
                    'final_amount' => $moduleFee,
                    'charge_component' => 'admin_fee',
                    'type' => 'lab_test',
                ] : null,
            ]));
            $pricing['total_with_module_fee'] = $itemPrice + $moduleFee;

            return response()->json([
                'success' => true,
                'data' => $pricing,
                'message' => 'Lab test price calculated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating lab test price: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function calculateDrugPrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'drug_id' => 'required|exists:drugs,id',
            'quantity' => 'required|numeric|min:0.01',
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pricing = $this->pricingService->calculateDrugPrice(
                $request->drug_id,
                $request->quantity,
                $request->patient_id,
                $request->branch_id
            );

            return response()->json([
                'success' => true,
                'data' => $pricing,
                'message' => 'Drug price calculated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating drug price: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getServicePricing(Request $request)
    {
        $query = ServicePricing::with(['branch', 'creator'])
            ->orderBy('service_name', 'asc');

        if ($request->has('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('pricing_type')) {
            $query->where('pricing_type', $request->pricing_type);
        }

        if ($request->has('module_code')) {
            $query->whereJsonContains('module_codes', $request->module_code);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('service_name', 'like', "%{$search}%")
                    ->orWhere('service_id', 'like', "%{$search}%");
            });
        }

        $pricing = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $pricing,
            'message' => 'Service pricing retrieved successfully',
        ]);
    }

    public function storeServicePricing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|string|max:255',
            'service_name' => 'required|string|max:255',
            'service_type' => 'required|string|max:255',
            'pricing_type' => 'nullable|in:module_fee,item_override,standalone',
            'is_additive' => 'nullable|boolean',
            'module_codes' => 'nullable|array',
            'module_codes.*' => 'string|in:' . implode(',', ModulePricingService::MODULE_CODES),
            'applies_on' => 'nullable|in:visit_checkin,order_created,appointment_booked,manual',
            'branch_id' => 'required|exists:branches,id',
            'base_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string',
            'pricing_tiers' => 'nullable|array',
            'requires_approval' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payload = $request->all();
            $pricingType = $payload['pricing_type'] ?? ServicePricing::PRICING_TYPE_STANDALONE;

            if ($pricingType === ServicePricing::PRICING_TYPE_MODULE_FEE) {
                $payload['is_additive'] = true;
                $payload['module_codes'] = array_values($payload['module_codes'] ?? []);
            }

            $pricing = ServicePricing::updateOrCreate(
                [
                    'service_id' => $request->service_id,
                    'branch_id' => $request->branch_id,
                ],
                array_merge($payload, [
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $pricing->load(['branch', 'creator']),
                'message' => 'Service pricing saved successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving service pricing: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getPricingRules(Request $request, $serviceId)
    {
        $rules = PricingRule::where('service_id', $serviceId)
            ->orderBy('priority', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rules,
            'message' => 'Pricing rules retrieved successfully',
        ]);
    }

    public function storePricingRule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|string',
            'rule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|in:percentage_increase,percentage_decrease,fixed_increase,fixed_decrease,set_price,volume_discount,time_based',
            'adjustment_value' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'min_quantity' => 'nullable|integer|min:1',
            'conditions' => 'nullable|array',
            'priority' => 'nullable|integer|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $rule = PricingRule::create(array_merge($request->all(), [
                'created_by' => auth()->id(),
            ]));

            return response()->json([
                'success' => true,
                'data' => $rule,
                'message' => 'Pricing rule created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating pricing rule: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getDiscountSchemes(Request $request)
    {
        $query = DiscountScheme::orderBy('scheme_name', 'asc');

        if ($request->has('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $schemes = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $schemes,
            'message' => 'Discount schemes retrieved successfully',
        ]);
    }

    public function storeDiscountScheme(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scheme_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_id' => 'required|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'conditions' => 'nullable|array',
            'usage_limit' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'code' => 'nullable|string|unique:discount_schemes,code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $scheme = DiscountScheme::create(array_merge($request->all(), [
                'created_by' => auth()->id(),
            ]));

            return response()->json([
                'success' => true,
                'data' => $scheme,
                'message' => 'Discount scheme created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating discount scheme: ' . $e->getMessage(),
            ], 500);
        }
    }
}
