<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory;

    protected $table = 'pricing_rules';

    protected $fillable = [
        'service_id',
        'rule_name',
        'description',
        'rule_type',
        'adjustment_value',
        'discount_amount',
        'discount_percentage',
        'min_quantity',
        'conditions',
        'priority',
        'is_active',
        'valid_from',
        'valid_until',
        'created_by'
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'adjustment_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date'
    ];

    /**
     * Get the user who created the pricing rule.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active rules.
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
     * Scope a query to filter by rule type.
     */
    public function scopeByType($query, $ruleType)
    {
        return $query->where('rule_type', $ruleType);
    }

    /**
     * Scope a query to only include currently valid rules.
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
     * Check if rule is currently valid.
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
     * Get rule conditions as array.
     */
    public function getConditions()
    {
        return $this->conditions ?? [];
    }

    /**
     * Set rule conditions.
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * Check if rule applies to given context.
     */
    public function appliesTo($context)
    {
        if (!$this->is_active || !$this->isCurrentlyValid()) {
            return false;
        }

        $conditions = $this->getConditions();
        
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
}
