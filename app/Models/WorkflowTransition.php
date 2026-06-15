<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTransition extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'from_step_id',
        'to_step_id',
        'condition_type',
        'condition_logic',
        'required_permission',
        'priority',
    ];

    protected $casts = [
        'condition_logic' => 'array',
    ];

    /**
     * Get the workflow that owns this transition.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get the source step.
     */
    public function fromStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'from_step_id');
    }

    /**
     * Get the destination step.
     */
    public function toStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'to_step_id');
    }

    /**
     * Check if this transition is allowed based on condition logic.
     */
    public function isAllowed($metadata = []): bool
    {
        if ($this->condition_type === 'always') {
            return true;
        }

        if ($this->condition_type === 'permission_based') {
            // Permission check is done separately
            return true;
        }

        if ($this->condition_type === 'conditional' && $this->condition_logic) {
            return $this->evaluateCondition($this->condition_logic, $metadata);
        }

        return false;
    }

    /**
     * Evaluate conditional logic.
     */
    protected function evaluateCondition($logic, $metadata): bool
    {
        // Simple condition evaluation
        // Supports: field checks, comparisons, etc.
        if (!is_array($logic)) {
            return false;
        }

        foreach ($logic as $condition) {
            if (isset($condition['field']) && isset($condition['operator']) && isset($condition['value'])) {
                $fieldValue = $metadata[$condition['field']] ?? null;
                $expectedValue = $condition['value'];

                switch ($condition['operator']) {
                    case 'equals':
                        return $fieldValue == $expectedValue;
                    case 'not_equals':
                        return $fieldValue != $expectedValue;
                    case 'exists':
                        return isset($metadata[$condition['field']]);
                    case 'not_exists':
                        return !isset($metadata[$condition['field']]);
                    case 'greater_than':
                        return $fieldValue > $expectedValue;
                    case 'less_than':
                        return $fieldValue < $expectedValue;
                    default:
                        return false;
                }
            }
        }

        return true;
    }
}

