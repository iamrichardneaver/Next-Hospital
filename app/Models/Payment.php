<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\PaymentMethod as PaymentMethodEnum;
use App\Traits\HasIdPrefix;

class Payment extends Model
{
    use HasFactory, HasIdPrefix;

    protected $fillable = [
        'id',
        'invoice_id',
        'patient_id',
        'branch_id',
        'amount',
        'payment_method',
        'source_platform',
        'device_info',
        'ip_address',
        'payment_date',
        'payment_reference',
        'reference_number',
        'transaction_id',
        'status',
        'notes',
        'metadata',
        'processed_by',
        'processed_at',
        'created_by',
        'updated_by'
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return 'payment';
    }

    /**
     * The field where the generated ID should be stored
     */
    protected function getIdField()
    {
        return 'payment_reference';
    }

    protected $casts = [
        'payment_date' => 'date',
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
        'metadata' => 'array'
    ];

    /**
     * Get the invoice that owns the payment.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the patient that owns the payment.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the branch that owns the payment.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who processed the payment.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the user who created the payment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the payment.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get payments by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get payments by method.
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope to get payments by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is failed.
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClass()
    {
        switch ($this->status) {
            case 'completed':
                return 'badge-success';
            case 'pending':
                return 'badge-warning';
            case 'failed':
                return 'badge-danger';
            case 'cancelled':
                return 'badge-secondary';
            default:
                return 'badge-info';
        }
    }

    /**
     * Get formatted payment method.
     */
    public function getFormattedPaymentMethod()
    {
        return PaymentMethodEnum::labelFor($this->payment_method);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount()
    {
        return 'GHS ' . number_format($this->amount, 2);
    }

    /**
     * Get payment method icon.
     */
    public function getPaymentMethodIcon()
    {
        $method = PaymentMethodEnum::normalize($this->payment_method);

        return match ($method) {
            PaymentMethodEnum::Cash->value => 'fas fa-money-bill-wave',
            PaymentMethodEnum::Paystack->value => 'fas fa-credit-card',
            PaymentMethodEnum::MobileMoneyOffline->value => 'fas fa-mobile-alt',
            PaymentMethodEnum::Insurance->value => 'fas fa-shield-alt',
            PaymentMethodEnum::BankTransfer->value => 'fas fa-university',
            default => 'fas fa-payment',
        };
    }

    /**
     * Get source platform icon.
     */
    public function getSourcePlatformIcon()
    {
        switch ($this->source_platform) {
            case 'mobile':
                return 'fas fa-mobile-alt';
            case 'web':
                return 'fas fa-desktop';
            case 'api':
                return 'fas fa-code';
            case 'webhook':
                return 'fas fa-exchange-alt';
            case 'system':
                return 'fas fa-cog';
            default:
                return 'fas fa-question-circle';
        }
    }

    /**
     * Get source platform badge class.
     */
    public function getSourcePlatformBadgeClass()
    {
        switch ($this->source_platform) {
            case 'mobile':
                return 'badge-info'; // Blue for mobile
            case 'web':
                return 'badge-success'; // Green for web
            case 'api':
                return 'badge-primary'; // Primary for API
            case 'webhook':
                return 'badge-warning'; // Yellow for webhook
            case 'system':
                return 'badge-secondary'; // Gray for system
            default:
                return 'badge-light';
        }
    }

    /**
     * Get formatted source platform.
     */
    public function getFormattedSourcePlatform()
    {
        return ucfirst($this->source_platform ?? 'Unknown');
    }

    /**
     * Mark payment as completed.
     */
    public function markAsCompleted($processedBy = null)
    {
        $this->update([
            'status' => 'completed',
            'processed_by' => $processedBy ?? auth()->id(),
            'processed_at' => now()
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed($notes = null)
    {
        $this->update([
            'status' => 'failed',
            'notes' => $notes ?? $this->notes
        ]);
    }

    /**
     * Mark payment as pending.
     */
    public function markAsPending()
    {
        $this->update([
            'status' => 'pending',
            'processed_by' => null,
            'processed_at' => null
        ]);
    }

    /**
     * Get revenue transactions for this payment (polymorphic).
     */
    public function revenueTransactions()
    {
        return $this->morphMany(\App\Models\RevenueTransaction::class, 'source');
    }
}
