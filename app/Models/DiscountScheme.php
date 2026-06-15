<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountScheme extends Model
{
    use HasFactory;

    protected $table = 'discount_schemes';

    protected $fillable = [
        'scheme_name',
        'description',
        'service_id',
        'discount_type',
        'discount_value',
        'min_amount',
        'max_discount',
        'conditions',
        'usage_limit',
        'used_count',
        'is_active',
        'valid_from',
        'valid_until',
        'code',
        'created_by'
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'discount_value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date'
    ];

    /**
     * Get the user who created the discount scheme.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active schemes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by service ID.
     */
    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * Scope a query to filter by discount code.
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope a query to only include currently valid schemes.
     */
    public function scopeCurrentlyValid($query)
    {
        $now = now()->toDateString();
        return $query->where(function($q) use ($now) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', $now);
        })->where(function($q) use ($now) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', $now);
        });
    }

    /**
     * Check if scheme is currently valid.
     */
    public function isCurrentlyValid()
    {
        $now = now()->toDateString();
        
        if ($this->valid_from && $this->valid_from > $now) {
            return false;
        }
        
        if ($this->valid_until && $this->valid_until < $now) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if scheme has usage limit remaining.
     */
    public function hasUsageLimitRemaining()
    {
        if (!$this->usage_limit) {
            return true;
        }
        
        return $this->used_count < $this->usage_limit;
    }

    /**
     * Check if scheme is applicable to given context.
     */
    public function isApplicable($context)
    {
        if (!$this->is_active || !$this->isCurrentlyValid() || !$this->hasUsageLimitRemaining()) {
            return false;
        }

        $conditions = $this->conditions ?? [];
        
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $context)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Evaluate a single condition.
     */
    private function evaluateCondition($condition, $context)
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (!$field || !$operator) {
            return false;
        }

        $actualValue = data_get($context, $field);

        return match($operator) {
            'equals' => $actualValue == $value,
            'not_equals' => $actualValue != $value,
            'greater_than' => $actualValue > $value,
            'less_than' => $actualValue < $value,
            'contains' => str_contains($actualValue, $value),
            'in' => in_array($actualValue, (array)$value),
            'not_in' => !in_array($actualValue, (array)$value),
            default => false
        };
    }

    /**
     * Calculate discount amount for given base amount.
     */
    public function calculateDiscount($baseAmount)
    {
        if ($this->min_amount && $baseAmount < $this->min_amount) {
            return 0;
        }

        $discount = match($this->discount_type) {
            'percentage' => $baseAmount * $this->discount_value / 100,
            'fixed' => $this->discount_value,
            default => 0
        };

        // Apply maximum discount limit
        if ($this->max_discount && $discount > $this->max_discount) {
            $discount = $this->max_discount;
        }

        return min($discount, $baseAmount);
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage()
    {
        $this->increment('used_count');
    }

    /**
     * Get remaining usage count.
     */
    public function getRemainingUsage()
    {
        if (!$this->usage_limit) {
            return null;
        }
        
        return max(0, $this->usage_limit - $this->used_count);
    }
}
