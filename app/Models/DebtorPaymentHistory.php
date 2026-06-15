<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtorPaymentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'debtor_id',
        'invoice_id',
        'payment_id',
        'payment_amount',
        'remaining_balance',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'processed_by'
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'payment_date' => 'date'
    ];

    /**
     * Get the debtor that owns the payment history.
     */
    public function debtor(): BelongsTo
    {
        return $this->belongsTo(Debtor::class);
    }

    /**
     * Get the invoice that this payment is for.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the payment record.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who processed the payment.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope to get payments by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get payments by method.
     */
    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Get formatted payment amount.
     */
    public function getFormattedPaymentAmount()
    {
        return '₵' . number_format($this->payment_amount, 2);
    }

    /**
     * Get formatted remaining balance.
     */
    public function getFormattedRemainingBalance()
    {
        return '₵' . number_format($this->remaining_balance, 2);
    }
}
