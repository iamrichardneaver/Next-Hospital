<?php

namespace App\Services;

use App\Models\ServicePricing;
use App\Models\PricingRule;
use App\Models\DiscountScheme;
use App\Models\InsuranceCoverage;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\LabTestType;
use App\Models\Drug;
use App\Models\DrugStock;
use App\Models\Consultation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PricingService
{
    /**
     * Find active service pricing for a branch (no fallback defaults).
     */
    public function findServicePricing(string $serviceId, ?int $branchId): ?ServicePricing
    {
        if (!$branchId) {
            return null;
        }

        return ServicePricing::where('service_id', $serviceId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Return configured price or null when admin has not set pricing.
     * Applies patient-specific rules when patientId is provided.
     */
    public function getPrice(string $serviceId, ?int $branchId, ?int $patientId = null, array $options = []): ?float
    {
        $record = $this->findServicePricing($serviceId, $branchId);
        if (!$record) {
            return null;
        }

        if ($patientId) {
            try {
                $result = $this->calculateServicePrice($serviceId, $patientId, $branchId, $options);

                return (float) ($result['final_price'] ?? $result['base_price'] ?? $record->base_price);
            } catch (\Exception $e) {
                Log::debug('getPrice fell back to base_price', [
                    'service_id' => $serviceId,
                    'branch_id' => $branchId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return (float) $record->base_price;
    }

    /**
     * Whether a service has admin-configured pricing for the branch.
     */
    public function hasConfiguredPrice(string $serviceId, ?int $branchId): bool
    {
        return $this->findServicePricing($serviceId, $branchId) !== null;
    }

    /**
     * Calculate service price with all applicable rules
     */
    public function calculateServicePrice($serviceId, $patientId, $branchId, $options = [])
    {
        try {
            $patient = Patient::findOrFail($patientId);
            $branch = Branch::findOrFail($branchId);
            $service = $this->findServicePricing($serviceId, $branchId);

            if (!$service) {
                throw new \Exception("Service pricing not found for service ID: {$serviceId}");
            }

            $basePrice = $service->base_price;
            $calculatedPrice = $basePrice;

            // Apply pricing rules
            $calculatedPrice = $this->applyPricingRules($calculatedPrice, $patient, $branch, $service, $options);

            // Apply discounts
            $calculatedPrice = $this->applyDiscounts($calculatedPrice, $patient, $service, $options);

            // Apply insurance coverage
            $insuranceCoverage = $this->calculateInsuranceCoverage($serviceId, $patientId, $calculatedPrice);

            return [
                'base_price' => $basePrice,
                'calculated_price' => $calculatedPrice,
                'insurance_coverage' => $insuranceCoverage,
                'patient_co_pay' => $calculatedPrice - $insuranceCoverage['covered_amount'],
                'final_price' => $calculatedPrice - $insuranceCoverage['covered_amount'],
                'currency' => $service->currency ?? 'GHS',
                'breakdown' => $this->getPriceBreakdown($basePrice, $calculatedPrice, $insuranceCoverage)
            ];

        } catch (\Exception $e) {
            Log::error("Pricing calculation failed", [
                'service_id' => $serviceId,
                'patient_id' => $patientId,
                'branch_id' => $branchId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate consultation fee based on doctor and consultation type
     */
    public function calculateConsultationFee($doctorId, $consultationType, $patientId, $branchId)
    {
        $serviceId = "consultation_{$consultationType}_{$doctorId}";
        return $this->calculateServicePrice($serviceId, $patientId, $branchId, [
            'consultation_type' => $consultationType,
            'doctor_id' => $doctorId
        ]);
    }

    /**
     * Calculate lab test price with priority and NHIS considerations
     * Priority: Individual Test pricing > Test Type pricing > Service Pricing
     */
    public function calculateLabTestPrice($testId, $patientId, $branchId, $priority = 'routine')
    {
        $patient = Patient::findOrFail($patientId);
        
        // First, try to find individual test pricing (highest priority)
        $individualTest = \App\Models\LabTest::where('test_type_id', $testId)->where('is_active', true)->first();
        
        if ($individualTest && $individualTest->cost !== null) {
            // Use individual test pricing
            $test = $individualTest;
            $basePrice = $individualTest->cost;
            
            // Apply NHIS pricing if applicable
            if ($patient->hasActiveInsurance() && $individualTest->nhis_covered && $individualTest->nhis_cost !== null) {
                $basePrice = $individualTest->nhis_cost;
            }
            
            $testName = $individualTest->test_name;
            $nhisCovered = $individualTest->nhis_covered;
            $testCategory = $individualTest->category->name ?? 'Unknown';
            
        } else {
            // Fall back to test type pricing
            $testType = \App\Models\LabTestType::findOrFail($testId);
            $basePrice = $testType->cost;
            
            // Apply NHIS pricing if applicable
            if ($patient->hasActiveInsurance() && $testType->nhis_covered && $testType->nhis_cost !== null) {
                $basePrice = $testType->nhis_cost;
            }
            
            $testName = $testType->test_name;
            $nhisCovered = $testType->nhis_covered;
            $testCategory = $testType->category;
        }

        // Apply priority surcharge first, then item-override (replaces test price; module fees are additive elsewhere)
        $priorityMultiplier = $this->getPriorityMultiplier($priority);
        $calculatedPrice = $basePrice * $priorityMultiplier;

        $serviceId = "lab_test_{$testId}";
        $overrideRecord = $this->findServicePricing($serviceId, $branchId);
        if ($overrideRecord && $overrideRecord->pricing_type === 'item_override') {
            try {
                $pricing = $this->calculateServicePrice($serviceId, $patientId, $branchId, [
                    'priority' => $priority,
                    'test_type' => $testCategory,
                ]);
                $calculatedPrice = (float) ($pricing['final_price'] ?? $pricing['calculated_price'] ?? $calculatedPrice);
            } catch (\Exception $e) {
                $calculatedPrice = (float) $overrideRecord->base_price;
            }
        }

        $moduleFee = app(ModulePricingService::class)->resolveModuleFee(
            'lab',
            (int) $branchId,
            (int) $patientId,
            ModulePricingService::APPLIES_ON_ORDER_CREATED
        );

        $insuranceCoverage = $this->calculateInsuranceCoverage($serviceId, $patientId, $calculatedPrice);
        $patientCoPay = $calculatedPrice - ($insuranceCoverage['covered_amount'] ?? 0);

        $pricing = [
            'base_price' => $basePrice,
            'calculated_price' => $calculatedPrice,
            'module_fee' => $moduleFee,
            'insurance_coverage' => $insuranceCoverage,
            'patient_co_pay' => $patientCoPay,
            'final_price' => $patientCoPay,
            'total_with_module_fee' => $patientCoPay + $moduleFee,
            'currency' => 'GHS',
            'breakdown' => [],
        ];

        $pricing['charge_lines'] = array_values(array_filter([
            $pricing['final_price'] > 0 ? [
                'description' => $testName,
                'amount' => $pricing['final_price'],
                'charge_component' => 'module_price',
                'type' => 'lab_test',
            ] : null,
            $moduleFee > 0 ? [
                'description' => 'Laboratory Service Fee',
                'amount' => $moduleFee,
                'charge_component' => 'admin_fee',
                'type' => 'lab_test',
            ] : null,
        ]));

        return array_merge($pricing, [
            'test_name' => $testName,
            'priority' => $priority,
            'nhis_covered' => $nhisCovered,
            'pricing_source' => $individualTest ? 'individual_test' : 'test_type',
        ]);
    }

    /**
     * Calculate drug price with quantity and branch-specific pricing
     */
    public function calculateDrugPrice($drugId, $quantity, $patientId, $branchId)
    {
        $drug = Drug::findOrFail($drugId);
        $drugStock = DrugStock::where('drug_id', $drugId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$drugStock) {
            throw new \Exception("Drug not available at this branch");
        }

        $basePrice = (float) $drugStock->selling_price;
        $totalPrice = $basePrice * $quantity;

        $volumeDiscount = $this->calculateVolumeDiscount($drugId, $quantity);
        $totalPrice = $totalPrice - $volumeDiscount;

        $serviceId = "drug_{$drugId}";
        $overrideRecord = $this->findServicePricing($serviceId, $branchId);

        if ($overrideRecord && $overrideRecord->pricing_type === 'item_override') {
            try {
                $pricing = $this->calculateServicePrice($serviceId, $patientId, $branchId, [
                    'quantity' => $quantity,
                    'drug_category' => $drug->category,
                ]);
                $totalPrice = (float) ($pricing['final_price'] ?? $pricing['calculated_price'] ?? $totalPrice);
            } catch (\Exception $e) {
                $totalPrice = (float) $overrideRecord->base_price * $quantity;
            }
        }

        $insuranceCoverage = $this->calculateInsuranceCoverage($serviceId, $patientId, $totalPrice);
        $patientCoPay = $totalPrice - ($insuranceCoverage['covered_amount'] ?? 0);
        $moduleFee = app(ModulePricingService::class)->resolveModuleFee(
            'pharmacy',
            (int) $branchId,
            (int) $patientId,
            ModulePricingService::APPLIES_ON_ORDER_CREATED
        );

        return [
            'base_price' => $basePrice,
            'calculated_price' => $totalPrice,
            'module_fee' => $moduleFee,
            'insurance_coverage' => $insuranceCoverage,
            'patient_co_pay' => $patientCoPay,
            'final_price' => $patientCoPay,
            'total_with_module_fee' => $patientCoPay + $moduleFee,
            'currency' => 'GHS',
            'drug_name' => $drug->name,
            'unit_price' => $basePrice,
            'quantity' => $quantity,
            'volume_discount' => $volumeDiscount,
            'charge_lines' => array_values(array_filter([
                $patientCoPay > 0 ? [
                    'description' => $drug->name,
                    'amount' => $patientCoPay,
                    'charge_component' => 'module_price',
                    'type' => 'prescription',
                ] : null,
                $moduleFee > 0 ? [
                    'description' => 'Pharmacy Service Fee',
                    'amount' => $moduleFee,
                    'charge_component' => 'admin_fee',
                    'type' => 'prescription',
                ] : null,
            ])),
        ];
    }

    /**
     * Apply dynamic pricing rules
     */
    private function applyPricingRules($basePrice, $patient, $branch, $service, $options)
    {
        $rules = PricingRule::where('service_id', $service->service_id)
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        $calculatedPrice = $basePrice;

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule, $patient, $branch, $options)) {
                $calculatedPrice = $this->applyRule($rule, $calculatedPrice);
            }
        }

        return $calculatedPrice;
    }

    /**
     * Apply discounts based on patient and service
     */
    private function applyDiscounts($basePrice, $patient, $service, $options)
    {
        $discounts = DiscountScheme::where('is_active', true)
            ->where(function($query) use ($service) {
                $query->where('service_id', $service->service_id)
                      ->orWhere('service_id', 'all');
            })
            ->get();

        $totalDiscount = 0;

        foreach ($discounts as $discount) {
            if ($this->isDiscountApplicable($discount, $patient, $options)) {
                $discountAmount = $this->calculateDiscountAmount($discount, $basePrice);
                $totalDiscount += $discountAmount;
            }
        }

        return max(0, $basePrice - $totalDiscount);
    }

    /**
     * Calculate insurance coverage
     */
    private function calculateInsuranceCoverage($serviceId, $patientId, $amount)
    {
        $patient = Patient::findOrFail($patientId);
        
        if (!$patient->hasActiveInsurance()) {
            return [
                'covered_amount' => 0,
                'co_pay_amount' => $amount,
                'insurance_provider' => null,
                'coverage_percentage' => 0
            ];
        }

        $coverage = InsuranceCoverage::where('service_id', $serviceId)
            ->where('patient_id', $patientId)
            ->where('is_active', true)
            ->first();

        if (!$coverage) {
            return [
                'covered_amount' => 0,
                'co_pay_amount' => $amount,
                'insurance_provider' => null,
                'coverage_percentage' => 0
            ];
        }

        $coveredAmount = ($amount * $coverage->coverage_percentage) / 100;
        $coPayAmount = $amount - $coveredAmount;

        return [
            'covered_amount' => $coveredAmount,
            'co_pay_amount' => $coPayAmount,
            'insurance_provider' => $coverage->insurance_provider,
            'coverage_percentage' => $coverage->coverage_percentage
        ];
    }

    /**
     * Get priority multiplier for lab tests
     */
    private function getPriorityMultiplier($priority)
    {
        return match($priority) {
            'stat' => 2.0,
            'urgent' => 1.5,
            'routine' => 1.0,
            default => 1.0
        };
    }

    /**
     * Calculate volume discount for drugs
     */
    private function calculateVolumeDiscount($drugId, $quantity)
    {
        $volumeRules = PricingRule::where('service_id', "drug_{$drugId}")
            ->where('rule_type', 'volume_discount')
            ->where('is_active', true)
            ->orderBy('min_quantity', 'desc')
            ->get();

        foreach ($volumeRules as $rule) {
            if ($quantity >= $rule->min_quantity) {
                return $rule->discount_amount ?? ($quantity * $rule->discount_percentage / 100);
            }
        }

        return 0;
    }

    /**
     * Evaluate if a pricing rule applies
     */
    private function evaluateRule($rule, $patient, $branch, $options)
    {
        $conditions = json_decode($rule->conditions, true);
        
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $patient, $branch, $options)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition($condition, $patient, $branch, $options)
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        $actualValue = $this->getFieldValue($field, $patient, $branch, $options);

        return match($operator) {
            'equals' => $actualValue == $value,
            'not_equals' => $actualValue != $value,
            'greater_than' => $actualValue > $value,
            'less_than' => $actualValue < $value,
            'contains' => str_contains($actualValue, $value),
            'in' => in_array($actualValue, $value),
            default => false
        };
    }

    /**
     * Get field value for condition evaluation
     */
    private function getFieldValue($field, $patient, $branch, $options)
    {
        return match($field) {
            'patient.age' => $patient->age,
            'patient.gender' => $patient->gender,
            'patient.insurance_type' => $patient->insurance_type ?? 'none',
            'branch.id' => $branch->id,
            'time.hour' => now()->hour,
            'time.day_of_week' => now()->dayOfWeek,
            'options.priority' => $options['priority'] ?? 'routine',
            'options.consultation_type' => $options['consultation_type'] ?? null,
            default => null
        };
    }

    /**
     * Apply a pricing rule
     */
    private function applyRule($rule, $currentPrice)
    {
        return match($rule->rule_type) {
            'percentage_increase' => $currentPrice * (1 + $rule->adjustment_value / 100),
            'percentage_decrease' => $currentPrice * (1 - $rule->adjustment_value / 100),
            'fixed_increase' => $currentPrice + $rule->adjustment_value,
            'fixed_decrease' => $currentPrice - $rule->adjustment_value,
            'set_price' => $rule->adjustment_value,
            default => $currentPrice
        };
    }

    /**
     * Check if discount is applicable
     */
    private function isDiscountApplicable($discount, $patient, $options)
    {
        $conditions = json_decode($discount->conditions, true);
        
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $patient, null, $options)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Calculate discount amount
     */
    private function calculateDiscountAmount($discount, $basePrice)
    {
        return match($discount->discount_type) {
            'percentage' => $basePrice * $discount->discount_value / 100,
            'fixed' => $discount->discount_value,
            default => 0
        };
    }

    /**
     * Get price breakdown for transparency
     */
    private function getPriceBreakdown($basePrice, $calculatedPrice, $insuranceCoverage)
    {
        return [
            'base_price' => $basePrice,
            'adjustments' => $calculatedPrice - $basePrice,
            'insurance_covered' => $insuranceCoverage['covered_amount'],
            'patient_pays' => $insuranceCoverage['co_pay_amount'],
            'net_amount' => $insuranceCoverage['co_pay_amount']
        ];
    }
}
