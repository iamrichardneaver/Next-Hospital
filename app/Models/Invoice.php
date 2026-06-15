<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasIdPrefix;

class Invoice extends Model
{
    use HasFactory, HasIdPrefix;


    /**
     * The field where the generated ID should be stored
     */
    protected $idField = 'invoice_number';

    protected $appends = ['balance'];

    protected $fillable = [
        'id',
        'patient_id',
        'branch_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'items',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'payment_status',
        'payment_method',
        'source_platform',
        'notes',
        'created_by',
        'updated_by'
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return 'invoice';
    }

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2'
    ];

    /**
     * Get the items attribute, ensuring it's always an array
     */
    public function getItemsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }

    /**
     * Get the patient that owns the invoice.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the branch that owns the invoice.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the invoice.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the invoice.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who created the invoice.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get revenue transactions for this invoice (polymorphic).
     */
    public function revenueTransactions()
    {
        return $this->morphMany(\App\Models\RevenueTransaction::class, 'source');
    }

    /**
     * Scope to get invoices by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending invoices.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to get overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', '!=', 'paid')
                    ->where('due_date', '<', now());
            });
    }

    /**
     * Scope to get invoices by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get invoices by payment method.
     */
    public function scopeByPaymentMethod($query, $paymentMethod)
    {
        return $query->where('payment_method', $paymentMethod);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue()
    {
        return $this->status === 'overdue' ||
            ($this->status !== 'paid' && $this->due_date < now());
    }

    /**
     * Check if invoice is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Get total amount paid.
     */
    public function getTotalPaid()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    /**
     * Get remaining balance.
     */
    public function getRemainingBalance()
    {
        return $this->total_amount - $this->getTotalPaid();
    }

    /**
     * Get payment percentage.
     */
    public function getPaymentPercentage()
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        return round(($this->getTotalPaid() / $this->total_amount) * 100, 2);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClass()
    {
        switch ($this->status) {
            case 'paid':
                return 'bg-success';
            case 'pending':
                return 'bg-warning';
            case 'overdue':
                return 'bg-danger';
            case 'cancelled':
                return 'bg-secondary';
            default:
                return 'bg-info';
        }
    }

    /**
     * Get formatted invoice number.
     */
    public function getFormattedInvoiceNumber()
    {
        return 'INV-' . $this->invoice_number;
    }

    /**
     * Get days until due.
     */
    public function getDaysUntilDue()
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isFullyPaid()
    {
        return $this->getTotalPaid() >= $this->total_amount;
    }

    /**
     * Check if invoice is partially paid.
     */
    public function isPartiallyPaid()
    {
        $totalPaid = $this->getTotalPaid();
        return $totalPaid > 0 && $totalPaid < $this->total_amount;
    }

    /**
     * Update invoice status based on payments.
     */
    public function updateStatus()
    {
        $totalPaid = $this->getTotalPaid();

        if ($totalPaid >= $this->total_amount) {
            $this->update(['status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $this->update(['status' => 'partial']);
        } elseif ($this->due_date < now()) {
            $this->update(['status' => 'overdue']);
        } else {
            $this->update(['status' => 'pending']);
        }
    }

    /**
     * Get balance attribute.
     */
    public function getBalanceAttribute()
    {
        return $this->getRemainingBalance();
    }

    /**
     * Whether this invoice is for outpatient/OPD services (no partial payments).
     */
    public function isOutpatientInvoice(): bool
    {
        $opdTypes = [
            'registration_fee', 'consultation', 'appointment', 'opd',
        ];

        $items = is_array($this->items) ? $this->items : [];
        foreach ($items as $item) {
            $type = strtolower($item['service_type'] ?? '');
            if (in_array($type, $opdTypes, true)) {
                return true;
            }
        }

        return true;
    }

    /**
     * Reject partial payment when outpatient policy applies.
     */
    public function allowsPartialPayment(): bool
    {
        return !$this->isOutpatientInvoice();
    }
}
