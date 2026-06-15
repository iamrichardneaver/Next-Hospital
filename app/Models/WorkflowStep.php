<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_key',
        'step_name',
        'step_description',
        'route_name',
        'required_permission',
        'order',
        'is_required',
        'can_skip',
        'auto_redirect',
        'route_parameters',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'can_skip' => 'boolean',
        'auto_redirect' => 'boolean',
        'route_parameters' => 'array',
    ];

    /**
     * Get the workflow that owns this step.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get transitions from this step.
     */
    public function transitionsFrom(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'from_step_id');
    }

    /**
     * Get transitions to this step.
     */
    public function transitionsTo(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'to_step_id');
    }

    /**
     * Get all workflow instances at this step.
     */
    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'current_step_id');
    }

    /**
     * Get all action logs for this step.
     */
    public function actionLogs(): HasMany
    {
        return $this->hasMany(WorkflowActionLog::class);
    }

    /**
     * Check if user can access this step.
     */
    public function canAccess($user): bool
    {
        if (!$this->required_permission) {
            return true;
        }

        return $user->can($this->required_permission);
    }
}

