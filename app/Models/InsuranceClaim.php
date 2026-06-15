<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasIdPrefix;

class InsuranceClaim extends Model
{
    use HasIdPrefix;
    
    protected $fillable = [
        'claim_number',
        'patient_id',
        'insurance_provider_id',
        'policy_id',
        'invoice_id',
        'visit_id',
        'total_amount',
        'covered_amount',
        'co_pay_amount',
        'processed_amount',
        'rejection_reason',
        'status',
        'submitted_date',
        'processed_date',
        'notes',
        'created_by',
        'processed_by'
    ];

    protected $casts = [
        'submitted_date' => 'date',
        'processed_date' => 'date',
        'total_amount' => 'decimal:2',
        'covered_amount' => 'decimal:2',
        'co_pay_amount' => 'decimal:2',
        'processed_amount' => 'decimal:2',
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return 'insurance_claim';
    }

    /**
     * Get the field name where the generated ID should be stored
     */
    protected function getIdField()
    {
        return 'claim_number';
    }

    /**
     * Get the patient that owns the claim.
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
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id');
    }

    /**
     * Get the insurance policy.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'policy_id');
    }

    /**
     * Get the invoice associated with this claim.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the visit associated with this claim.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Get all claim items for this claim.
     */
    public function claimItems(): HasMany
    {
        return $this->hasMany(ClaimItem::class, 'claim_id');
    }

    /**
     * Get the user who created the claim.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who processed the claim.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
