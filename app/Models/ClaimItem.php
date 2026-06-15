<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id',
        'service_type',
        'service_code',
        'description',
        'quantity',
        'unit_price',
        'total_amount',
        'covered_amount',
        'co_pay_amount',
        'deductible_amount',
        'service_details'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'covered_amount' => 'decimal:2',
        'co_pay_amount' => 'decimal:2',
        'deductible_amount' => 'decimal:2',
        'service_details' => 'array'
    ];

    /**
     * Get the claim that owns the claim item.
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(InsuranceClaim::class);
    }

    /**
     * Calculate total amount based on quantity and unit price.
     */
    public function calculateTotalAmount()
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Calculate coverage amounts.
     */
    public function calculateCoverage($coveragePercentage, $coPayPercentage)
    {
        $this->covered_amount = ($this->total_amount * $coveragePercentage) / 100;
        $this->co_pay_amount = ($this->total_amount * $coPayPercentage) / 100;
        
        return $this;
    }
}