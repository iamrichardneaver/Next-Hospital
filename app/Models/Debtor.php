<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasIdPrefix;

class Debtor extends Model
{
    use HasFactory;


    protected $fillable = [
        'id',
        'patient_id',
        'branch_id',
        'total_outstanding',
        'total_paid',
        'total_invoiced',
        'outstanding_invoices_count',
        'overdue_invoices_count',
        'last_payment_date',
        'last_invoice_date',
        'first_outstanding_date',
        'debt_status',
        'days_overdue',
        'largest_outstanding_amount',
        'notes',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'total_outstanding' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_invoiced' => 'decimal:2',
        'largest_outstanding_amount' => 'decimal:2',
        'last_payment_date' => 'date',
        'last_invoice_date' => 'date',
        'first_outstanding_date' => 'date',
        'is_active' => 'boolean'
    ];

    /**
     * Get the patient that owns the debtor record.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the branch that owns the debtor record.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the debtor record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the debtor record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all payment history for this debtor.
     */
    public function paymentHistory(): HasMany
    {
        return $this->hasMany(DebtorPaymentHistory::class);
    }

    /**
     * Get all outstanding invoices for this debtor.
     */
    public function outstandingInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'patient_id', 'patient_id')
            ->where('branch_id', $this->branch_id)
            ->where(function($query) {
                $query->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
                      ->orWhereIn('status', ['pending', 'draft', 'partial', 'overdue']);
            })
            ->where('status', '!=', 'cancelled')
            ->where('balance_amount', '>', 0) // Only invoices with actual balance
            ->orderBy('invoice_date', 'asc');
    }

    /**
     * Get all overdue invoices for this debtor.
     */
    public function overdueInvoices()
    {
        return Invoice::where('patient_id', $this->patient_id)
            ->where('branch_id', $this->branch_id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->where(function($query) {
                $query->where('status', 'overdue')
                      ->orWhere(function($q) {
                          $q->where('status', '!=', 'paid')
                            ->whereNotNull('due_date')
                            ->where('due_date', '<', now());
                      });
            })
            ->orderBy('due_date', 'asc');
    }

    /**
     * Scope to get debtors by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('debt_status', $status);
    }

    /**
     * Scope to get active debtors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get overdue debtors.
     */
    public function scopeOverdue($query)
    {
        return $query->where('debt_status', 'overdue')
                    ->orWhere('days_overdue', '>', 0);
    }

    /**
     * Scope to get critical debtors.
     */
    public function scopeCritical($query)
    {
        return $query->where('debt_status', 'critical');
    }

    /**
     * Scope to get debtors by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get debtors by amount range.
     */
    public function scopeByAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('total_outstanding', [$minAmount, $maxAmount]);
    }

    /**
     * Scope to get debtors by days overdue range.
     */
    public function scopeByDaysOverdue($query, $minDays, $maxDays)
    {
        return $query->whereBetween('days_overdue', [$minDays, $maxDays]);
    }

    /**
     * Check if debtor is overdue.
     */
    public function isOverdue()
    {
        return $this->debt_status === 'overdue' || $this->days_overdue > 0;
    }

    /**
     * Check if debtor is critical.
     */
    public function isCritical()
    {
        return $this->debt_status === 'critical';
    }

    /**
     * Check if debtor is current.
     */
    public function isCurrent()
    {
        return $this->debt_status === 'current';
    }

    /**
     * Get debt status badge class.
     */
    public function getStatusBadgeClass()
    {
        switch ($this->debt_status) {
            case 'current':
                return 'bg-success';
            case 'overdue':
                return 'bg-warning';
            case 'critical':
                return 'bg-danger';
            case 'resolved':
                return 'bg-secondary';
            default:
                return 'bg-info';
        }
    }

    /**
     * Display name for the linked patient, with fallback when the record is missing.
     */
    public function getPatientDisplayNameAttribute(): string
    {
        if ($this->patient) {
            return trim($this->patient->first_name . ' ' . $this->patient->last_name);
        }

        return 'Unknown Patient';
    }

    /**
     * Display patient number, with fallback when the linked patient record is missing.
     */
    public function getPatientNumberDisplayAttribute(): string
    {
        if ($this->patient) {
            return $this->patient->patient_number;
        }

        return $this->patient_id
            ? "Patient #{$this->patient_id} (record missing)"
            : 'N/A';
    }

    /**
     * Get formatted outstanding amount.
     */
    public function getFormattedOutstandingAmount()
    {
        return '₵' . number_format($this->total_outstanding, 2);
    }

    /**
     * Get formatted total paid amount.
     */
    public function getFormattedTotalPaid()
    {
        return '₵' . number_format($this->total_paid, 2);
    }

    /**
     * Get formatted total invoiced amount.
     */
    public function getFormattedTotalInvoiced()
    {
        return '₵' . number_format($this->total_invoiced, 2);
    }

    /**
     * Get payment percentage.
     */
    public function getPaymentPercentage()
    {
        if ($this->total_invoiced == 0) {
            return 0;
        }
        
        return round(($this->total_paid / $this->total_invoiced) * 100, 2);
    }

    /**
     * Get days since last payment.
     */
    public function getDaysSinceLastPayment()
    {
        if (!$this->last_payment_date) {
            return null;
        }
        
        return now()->diffInDays($this->last_payment_date);
    }

    /**
     * Get days since first outstanding.
     */
    public function getDaysSinceFirstOutstanding()
    {
        if (!$this->first_outstanding_date) {
            return null;
        }
        
        return now()->diffInDays($this->first_outstanding_date);
    }

    /**
     * Update debtor status based on outstanding amounts and days.
     */
    public function updateStatus()
    {
        $outstandingInvoices = $this->outstandingInvoices()->get();
        $overdueInvoices = $this->overdueInvoices()->get();
        
        $this->outstanding_invoices_count = $outstandingInvoices->count();
        $this->overdue_invoices_count = $overdueInvoices->count();
        
        // Calculate days overdue based on oldest overdue invoice (positive number of days past due)
        $oldestOverdue = $overdueInvoices->min('due_date');
        $this->days_overdue = $oldestOverdue ? abs(now()->diffInDays($oldestOverdue, false)) : 0;
        
        // Update debt status
        if ($this->total_outstanding == 0) {
            $this->debt_status = 'resolved';
        } elseif ($this->days_overdue > 90) {
            $this->debt_status = 'critical';
        } elseif ($this->days_overdue > 30) {
            $this->debt_status = 'overdue';
        } else {
            $this->debt_status = 'current';
        }
        
        // Update largest outstanding amount
        $this->largest_outstanding_amount = $outstandingInvoices->max('total_amount') ?? 0;
        
        $this->save();
    }

    /**
     * Calculate total outstanding from invoices.
     * Uses balance_amount field which is the actual outstanding amount per invoice.
     */
    public function calculateOutstanding()
    {
        $outstandingInvoices = $this->outstandingInvoices()->get();
        
        // Use balance_amount field which is the actual outstanding amount per invoice
        $this->total_outstanding = $outstandingInvoices->sum('balance_amount');
        
        $this->total_invoiced = Invoice::where('patient_id', $this->patient_id)
            ->where('branch_id', $this->branch_id)
            ->sum('total_amount');
            
        $this->total_paid = Invoice::where('patient_id', $this->patient_id)
            ->where('branch_id', $this->branch_id)
            ->sum('paid_amount');
        
        $this->save();
    }
}
