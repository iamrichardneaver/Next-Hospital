<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\HasIdPrefix;

class RevenueTransaction extends Model
{
    use HasFactory, HasIdPrefix;

    /**
     * The field where the generated ID should be stored
     */
    protected $idField = 'transaction_reference';

    protected $fillable = [
        'transaction_reference',
        'patient_id',
        'branch_id',
        'source_type',
        'source_id',
        'service_type',
        'amount',
        'payment_method',
        'source_platform',
        'transaction_date',
        'status',
        'metadata',
        'recorded_by'
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return 'revenue';
    }

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'metadata' => 'array'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            // Generate transaction_reference using ID prefix service
            if (empty($transaction->transaction_reference)) {
                $transaction->transaction_reference = app(\App\Services\IdPrefixService::class)->generateId('revenue');
            }
            
            // Set transaction_date to today if not set
            if (empty($transaction->transaction_date)) {
                $transaction->transaction_date = now()->toDateString();
            }
        });
    }

    /**
     * Get the source model (polymorphic relation)
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the patient that owns the transaction.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the branch that owns the transaction.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who recorded the transaction.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope to get transactions by patient.
     */
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope to get transactions by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get transactions by service type.
     */
    public function scopeByServiceType($query, $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Scope to get transactions by payment method.
     */
    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope to get transactions by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get revenue summary for a branch.
     */
    public static function getBranchRevenueSummary($branchId, $startDate = null, $endDate = null)
    {
        $query = static::where('branch_id', $branchId)
            ->where('status', 'completed');

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        return [
            'total_revenue' => $query->sum('amount'),
            'total_transactions' => $query->count(),
            'by_service_type' => $query->selectRaw('service_type, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('service_type')
                ->get(),
            'by_payment_method' => $query->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('payment_method')
                ->get()
        ];
    }

    /**
     * Get revenue summary for a patient.
     */
    public static function getPatientRevenueSummary($patientId)
    {
        $query = static::where('patient_id', $patientId)
            ->where('status', 'completed');

        return [
            'lifetime_revenue' => $query->sum('amount'),
            'total_transactions' => $query->count(),
            'by_service_type' => $query->selectRaw('service_type, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('service_type')
                ->get(),
            'last_transaction_date' => $query->max('transaction_date')
        ];
    }
}

