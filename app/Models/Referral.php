<?php

namespace App\Models;

use App\Traits\HasIdPrefix;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Referral extends Model
{
    use HasFactory, HasIdPrefix;

    protected $entityType = 'referral';

    protected $fillable = [
        'consultation_id',
        'referred_to_specialty',
        'referred_to_doctor_id',
        'reason',
        'urgency',
        'status',
        'referred_by',
        'referral_date',
        'accepted_at',
        'completed_at',
        'notes',
        'external_facility',
        'external_contact',
        'external_address'
    ];

    protected $casts = [
        'referral_date' => 'date',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the consultation that owns the referral.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the doctor referred to.
     */
    public function referredToDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_to_doctor_id');
    }

    /**
     * Get the user who made the referral.
     */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Scope to get referrals by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending referrals.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get accepted referrals.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get completed referrals.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get urgent referrals.
     */
    public function scopeUrgent($query)
    {
        return $query->where('urgency', 'urgent');
    }

    /**
     * Scope to get referrals by specialty.
     */
    public function scopeBySpecialty($query, $specialty)
    {
        return $query->where('referred_to_specialty', $specialty);
    }

    /**
     * Check if referral is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if referral is accepted.
     */
    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if referral is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if referral is urgent.
     */
    public function isUrgent()
    {
        return $this->urgency === 'urgent';
    }

    /**
     * Get the urgency badge class.
     */
    public function getUrgencyBadgeClass()
    {
        switch ($this->urgency) {
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
            case 'pending':
                return 'badge-warning';
            case 'accepted':
                return 'badge-success';
            case 'completed':
                return 'badge-primary';
            case 'rejected':
                return 'badge-danger';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get the urgency display name.
     */
    public function getUrgencyDisplayAttribute()
    {
        return ucfirst($this->urgency);
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayAttribute()
    {
        return ucfirst($this->status);
    }

    /**
     * Get the specialty display name.
     */
    public function getSpecialtyDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->referred_to_specialty));
    }

    /**
     * Get days since referral.
     */
    public function getDaysSinceReferral()
    {
        return now()->diffInDays($this->referral_date);
    }

    /**
     * Accept referral.
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now()
        ]);
    }

    /**
     * Complete referral.
     */
    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    /**
     * Reject referral.
     */
    public function reject()
    {
        $this->update(['status' => 'rejected']);
    }
}
