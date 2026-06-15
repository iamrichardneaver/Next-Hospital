<?php

namespace App\Models;

use App\Traits\HasIdPrefix;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FollowUp extends Model
{
    use HasFactory, HasIdPrefix;

    protected $entityType = 'follow_up';

    protected $fillable = [
        'consultation_id',
        'follow_up_date',
        'follow_up_type',
        'reason',
        'status',
        'assigned_to',
        'notes',
        'created_by',
        'completed_at'
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the consultation that owns the follow-up.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the user assigned to the follow-up.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created the follow-up.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get follow-ups by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get scheduled follow-ups.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope to get completed follow-ups.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get cancelled follow-ups.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to get follow-ups by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('follow_up_type', $type);
    }

    /**
     * Scope to get overdue follow-ups.
     */
    public function scopeOverdue($query)
    {
        return $query->where('follow_up_date', '<', now())
                    ->where('status', 'scheduled');
    }

    /**
     * Check if follow-up is scheduled.
     */
    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if follow-up is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if follow-up is cancelled.
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if follow-up is overdue.
     */
    public function isOverdue()
    {
        return $this->follow_up_date < now() && $this->status === 'scheduled';
    }

    /**
     * Get the follow-up type display name.
     */
    public function getFollowUpTypeDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->follow_up_type));
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeClass()
    {
        switch ($this->status) {
            case 'scheduled':
                return $this->isOverdue() ? 'badge-danger' : 'badge-warning';
            case 'completed':
                return 'badge-success';
            case 'cancelled':
                return 'badge-secondary';
            default:
                return 'badge-info';
        }
    }

    /**
     * Get the type badge class.
     */
    public function getTypeBadgeClass()
    {
        switch ($this->follow_up_type) {
            case 'in-person':
                return 'badge-primary';
            case 'teleconsultation':
                return 'badge-info';
            case 'phone_call':
                return 'badge-warning';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get days until follow-up.
     */
    public function getDaysUntilFollowUp()
    {
        return now()->diffInDays($this->follow_up_date, false);
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

    /**
     * Reschedule follow-up.
     */
    public function reschedule($newDate)
    {
        $this->update(['follow_up_date' => $newDate]);
    }
}
