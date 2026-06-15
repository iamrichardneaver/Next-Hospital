<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ConsultationIntervention extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'intervention_type',
        'description',
        'medication_id',
        'dosage_instructions',
        'frequency',
        'duration',
        'procedure_code',
        'lab_test_id',
        'imaging_id',
        'priority',
        'status',
        'ordered_by',
        'ordered_at',
        'completed_at',
        'notes'
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the consultation that owns the intervention.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the medication for this intervention.
     */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Drug::class, 'medication_id');
    }

    /**
     * Get the user who ordered the intervention.
     */
    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    /**
     * Scope to get interventions by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('intervention_type', $type);
    }

    /**
     * Scope to get interventions by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get ordered interventions.
     */
    public function scopeOrdered($query)
    {
        return $query->where('status', 'ordered');
    }

    /**
     * Scope to get completed interventions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get urgent interventions.
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    /**
     * Check if intervention is ordered.
     */
    public function isOrdered()
    {
        return $this->status === 'ordered';
    }

    /**
     * Check if intervention is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if intervention is urgent.
     */
    public function isUrgent()
    {
        return $this->priority === 'urgent';
    }

    /**
     * Get the intervention type display name.
     */
    public function getInterventionTypeDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->intervention_type));
    }

    /**
     * Get the priority badge class.
     */
    public function getPriorityBadgeClass()
    {
        switch ($this->priority) {
            case 'urgent':
                return 'badge-danger';
            case 'routine':
                return 'badge-info';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeClass()
    {
        switch ($this->status) {
            case 'ordered':
                return 'badge-warning';
            case 'completed':
                return 'badge-success';
            case 'cancelled':
                return 'badge-danger';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    /**
     * Mark as cancelled.
     */
    public function markAsCancelled()
    {
        $this->update(['status' => 'cancelled']);
    }
}
