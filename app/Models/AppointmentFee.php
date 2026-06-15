<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AppointmentFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'branch_id',
        'appointment_type',
        'fee_category',
        'base_fee',
        'currency',
        'platform_fee',
        'tax_rate',
        'discount_rules',
        'is_active',
        'effective_from',
        'effective_until',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'base_fee' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_rules' => 'array',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    /**
     * Get the doctor that owns the fee.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the branch that owns the fee.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the fee.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the fee.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if fee is currently effective.
     */
    public function isEffective(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->toDateString();
        
        if ($this->effective_from && $this->effective_from > $now) {
            return false;
        }
        
        if ($this->effective_until && $this->effective_until < $now) {
            return false;
        }
        
        return true;
    }

    /**
     * Calculate total fee including platform fee and tax.
     */
    public function calculateTotalFee(): float
    {
        $subtotal = $this->base_fee + $this->platform_fee;
        $taxAmount = $subtotal * ($this->tax_rate / 100);
        return $subtotal + $taxAmount;
    }

    /**
     * Calculate tax amount.
     */
    public function calculateTaxAmount(): float
    {
        $subtotal = $this->base_fee + $this->platform_fee;
        return $subtotal * ($this->tax_rate / 100);
    }

    /**
     * Get fee breakdown.
     */
    public function getFeeBreakdown(): array
    {
        $taxAmount = $this->calculateTaxAmount();
        $total = $this->calculateTotalFee();

        return [
            'base_fee' => $this->base_fee,
            'platform_fee' => $this->platform_fee,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $taxAmount,
            'subtotal' => $this->base_fee + $this->platform_fee,
            'total' => $total,
            'currency' => $this->currency,
        ];
    }

    /**
     * Apply discount rules if applicable.
     */
    public function applyDiscounts(array $context = []): array
    {
        $breakdown = $this->getFeeBreakdown();
        
        if (!$this->discount_rules || empty($this->discount_rules)) {
            return $breakdown;
        }

        $discountAmount = 0;
        $discountReason = '';

        foreach ($this->discount_rules as $rule) {
            if ($this->evaluateDiscountRule($rule, $context)) {
                $discountAmount = $this->calculateDiscountAmount($rule, $breakdown['total']);
                $discountReason = $rule['description'] ?? 'Discount applied';
                break; // Apply first matching rule
            }
        }

        if ($discountAmount > 0) {
            $breakdown['discount_amount'] = $discountAmount;
            $breakdown['discount_reason'] = $discountReason;
            $breakdown['total'] = max(0, $breakdown['total'] - $discountAmount);
        }

        return $breakdown;
    }

    /**
     * Evaluate if a discount rule applies.
     */
    private function evaluateDiscountRule(array $rule, array $context): bool
    {
        // Example rule evaluation logic
        // This can be extended based on your business rules
        
        if (isset($rule['conditions'])) {
            foreach ($rule['conditions'] as $condition) {
                $field = $condition['field'] ?? '';
                $operator = $condition['operator'] ?? '=';
                $value = $condition['value'] ?? '';
                
                $contextValue = $context[$field] ?? null;
                
                if (!$this->evaluateCondition($contextValue, $operator, $value)) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Evaluate a single condition.
     */
    private function evaluateCondition($contextValue, string $operator, $value): bool
    {
        switch ($operator) {
            case '=':
                return $contextValue == $value;
            case '!=':
                return $contextValue != $value;
            case '>':
                return $contextValue > $value;
            case '>=':
                return $contextValue >= $value;
            case '<':
                return $contextValue < $value;
            case '<=':
                return $contextValue <= $value;
            case 'in':
                return in_array($contextValue, (array)$value);
            case 'not_in':
                return !in_array($contextValue, (array)$value);
            default:
                return false;
        }
    }

    /**
     * Calculate discount amount based on rule.
     */
    private function calculateDiscountAmount(array $rule, float $total): float
    {
        $discountType = $rule['type'] ?? 'percentage';
        $discountValue = $rule['value'] ?? 0;
        
        if ($discountType === 'percentage') {
            return $total * ($discountValue / 100);
        } else {
            return min($discountValue, $total);
        }
    }

    /**
     * Scope to get fees for a specific doctor.
     */
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope to get fees for a specific branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get fees by appointment type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('appointment_type', $type);
    }

    /**
     * Scope to get fees by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('fee_category', $category);
    }

    /**
     * Scope to get active fees.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get effective fees.
     */
    public function scopeEffective($query)
    {
        $now = now()->toDateString();
        return $query->where('is_active', true)
                    ->where(function($q) use ($now) {
                        $q->whereNull('effective_from')
                          ->orWhere('effective_from', '<=', $now);
                    })
                    ->where(function($q) use ($now) {
                        $q->whereNull('effective_until')
                          ->orWhere('effective_until', '>=', $now);

                    });
    }
}
