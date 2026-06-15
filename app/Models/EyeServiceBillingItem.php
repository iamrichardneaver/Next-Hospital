<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeServiceBillingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_request_id',
        'service_id',
        'item_code',
        'item_name',
        'description',
        'quantity',
        'unit_price',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'final_amount',
        'currency',
        'is_insurance_covered',
        'insurance_coverage',
        'patient_co_pay',
        'billing_status',
        'invoice_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'insurance_coverage' => 'decimal:2',
        'patient_co_pay' => 'decimal:2',
        'is_insurance_covered' => 'boolean',
    ];

    // Relationships
    public function testRequest(): BelongsTo
    {
        return $this->belongsTo(EyeTestRequest::class, 'test_request_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(EyeService::class, 'service_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('billing_status', 'pending');
    }

    public function scopeBilled($query)
    {
        return $query->where('billing_status', 'billed');
    }

    public function scopePaid($query)
    {
        return $query->where('billing_status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('billing_status', 'cancelled');
    }

    public function scopeByTestRequest($query, $testRequestId)
    {
        return $query->where('test_request_id', $testRequestId);
    }

    public function scopeByService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public function scopeInsuranceCovered($query)
    {
        return $query->where('is_insurance_covered', true);
    }

    public function scopeNotInsuranceCovered($query)
    {
        return $query->where('is_insurance_covered', false);
    }

    // Accessors & Mutators
    public function getFormattedStatusAttribute()
    {
        return ucfirst($this->billing_status);
    }

    public function getFormattedUnitPriceAttribute()
    {
        return $this->currency . ' ' . number_format($this->unit_price, 2);
    }

    public function getFormattedTotalAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->total_amount, 2);
    }

    public function getFormattedDiscountAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->discount_amount, 2);
    }

    public function getFormattedTaxAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->tax_amount, 2);
    }

    public function getFormattedFinalAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->final_amount, 2);
    }

    public function getFormattedInsuranceCoverageAttribute()
    {
        return $this->currency . ' ' . number_format($this->insurance_coverage, 2);
    }

    public function getFormattedPatientCoPayAttribute()
    {
        return $this->currency . ' ' . number_format($this->patient_co_pay, 2);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'billed' => 'info',
            'paid' => 'success',
            'cancelled' => 'danger',
        ];

        return $colors[$this->billing_status] ?? 'light';
    }

    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'pending' => 'badge-light-warning',
            'billed' => 'badge-light-info',
            'paid' => 'badge-light-success',
            'cancelled' => 'badge-light-danger',
        ];

        return $classes[$this->billing_status] ?? 'badge-light-light';
    }

    // Methods
    public function isPending(): bool
    {
        return $this->billing_status === 'pending';
    }

    public function isBilled(): bool
    {
        return $this->billing_status === 'billed';
    }

    public function isPaid(): bool
    {
        return $this->billing_status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->billing_status === 'cancelled';
    }

    public function isInsuranceCovered(): bool
    {
        return $this->is_insurance_covered;
    }

    public function calculateTotalAmount(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function calculateFinalAmount(): float
    {
        return $this->total_amount - $this->discount_amount + $this->tax_amount;
    }

    public function calculateInsuranceCoverage(): float
    {
        if (!$this->is_insurance_covered) {
            return 0;
        }

        return $this->final_amount * ($this->insurance_coverage / 100);
    }

    public function calculatePatientCoPay(): float
    {
        if (!$this->is_insurance_covered) {
            return $this->final_amount;
        }

        return $this->final_amount - $this->insurance_coverage;
    }

    public function updateAmounts(): bool
    {
        $this->update([
            'total_amount' => $this->calculateTotalAmount(),
            'final_amount' => $this->calculateFinalAmount(),
            'insurance_coverage' => $this->calculateInsuranceCoverage(),
            'patient_co_pay' => $this->calculatePatientCoPay(),
        ]);

        return true;
    }

    public function applyDiscount(float $discountAmount, string $discountType = 'fixed'): bool
    {
        if ($discountType === 'percentage') {
            $discountAmount = $this->total_amount * ($discountAmount / 100);
        }

        $this->update([
            'discount_amount' => $discountAmount,
        ]);

        $this->updateAmounts();
        return true;
    }

    public function applyTax(float $taxRate): bool
    {
        $taxAmount = $this->total_amount * ($taxRate / 100);
        
        $this->update([
            'tax_amount' => $taxAmount,
        ]);

        $this->updateAmounts();
        return true;
    }

    public function setInsuranceCoverage(float $coveragePercentage): bool
    {
        $this->update([
            'is_insurance_covered' => true,
            'insurance_coverage' => $coveragePercentage,
        ]);

        $this->updateAmounts();
        return true;
    }

    public function removeInsuranceCoverage(): bool
    {
        $this->update([
            'is_insurance_covered' => false,
            'insurance_coverage' => 0,
        ]);

        $this->updateAmounts();
        return true;
    }

    public function markAsBilled(): bool
    {
        $this->update(['billing_status' => 'billed']);
        return true;
    }

    public function markAsPaid(): bool
    {
        $this->update(['billing_status' => 'paid']);
        return true;
    }

    public function markAsCancelled(): bool
    {
        $this->update(['billing_status' => 'cancelled']);
        return true;
    }

    public function getDiscountPercentage(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        return ($this->discount_amount / $this->total_amount) * 100;
    }

    public function getTaxRate(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        return ($this->tax_amount / $this->total_amount) * 100;
    }

    public function getInsuranceCoveragePercentage(): float
    {
        if ($this->final_amount == 0) {
            return 0;
        }

        return ($this->insurance_coverage / $this->final_amount) * 100;
    }

    public function getPatientCoPayPercentage(): float
    {
        if ($this->final_amount == 0) {
            return 0;
        }

        return ($this->patient_co_pay / $this->final_amount) * 100;
    }

    public function getNetAmount(): float
    {
        return $this->final_amount - $this->discount_amount;
    }

    public function getGrossAmount(): float
    {
        return $this->total_amount + $this->tax_amount;
    }

    public function getSavingsAmount(): float
    {
        return $this->discount_amount + $this->insurance_coverage;
    }

    public function getItemSummary(): array
    {
        return [
            'item_code' => $this->item_code,
            'item_name' => $this->item_name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->formatted_unit_price,
            'total_amount' => $this->formatted_total_amount,
            'discount_amount' => $this->formatted_discount_amount,
            'tax_amount' => $this->formatted_tax_amount,
            'final_amount' => $this->formatted_final_amount,
            'insurance_coverage' => $this->formatted_insurance_coverage,
            'patient_co_pay' => $this->formatted_patient_co_pay,
            'billing_status' => $this->formatted_status,
            'is_insurance_covered' => $this->is_insurance_covered,
        ];
    }
}
