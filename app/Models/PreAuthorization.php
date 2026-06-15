<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'insurance_provider_id',
        'policy_id',
        'pre_auth_number',
        'external_pre_auth_id',
        'service_type',
        'service_code',
        'service_description',
        'requested_amount',
        'approved_amount',
        'co_pay_amount',
        'status',
        'urgency',
        'request_date',
        'service_date',
        'expiry_date',
        'approval_notes',
        'rejection_reason',
        'attachments',
        'notes',
        'requested_by',
        'approved_by'
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'co_pay_amount' => 'decimal:2',
        'attachments' => 'array',
        'request_date' => 'date',
        'service_date' => 'date',
        'expiry_date' => 'date'
    ];

    /**
     * Get the patient.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the insurance provider.
     */
    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    /**
     * Get the insurance policy.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    /**
     * Get the user who requested the pre-authorization.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved the pre-authorization.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who requested the pre-authorization (alias for requester).
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved the pre-authorization (alias for approver).
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all claims for this pre-authorization.
     */
    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    /**
     * Scope a query to only include pending pre-authorizations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved pre-authorizations.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include expired pre-authorizations.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere(function($q) {
                        $q->where('status', 'approved')
                          ->where('expiry_date', '<', now()->toDateString());
                    });
    }

    /**
     * Check if pre-authorization is expired.
     */
    public function isExpired()
    {
        return $this->status === 'expired' || 
               ($this->status === 'approved' && $this->expiry_date && $this->expiry_date < now()->toDateString());
    }

    /**
     * Check if pre-authorization is valid for use.
     */
    public function isValid()
    {
        return $this->status === 'approved' && !$this->isExpired();
    }

    /**
     * Generate pre-authorization number.
     */
    public static function generatePreAuthNumber()
    {
        $prefix = 'PA';
        $date = now()->format('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $random;
    }
}