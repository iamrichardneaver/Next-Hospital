<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasIdPrefix;

class Prescription extends Model
{
    use HasFactory, HasIdPrefix;


    /**
     * The field where the generated ID should be stored
     */
    protected $idField = 'prescription_number';

    protected $fillable = [
        'id',
        'patient_id',
        'consultation_id',
        'doctor_id',
        'branch_id',
        'prescription_number',
        'prescription_date',
        'status',
        'notes',
        'created_by',
        'billing_status',
        'invoice_id',
        'billing_amount',
        'billed_at',
        'workflow_instance_id'
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return 'prescription';
    }

    protected $casts = [
        'prescription_date' => 'date',
        'billing_amount' => 'decimal:2',
        'billed_at' => 'datetime',
    ];

    /**
     * Get the patient that owns the prescription.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the consultation that owns the prescription.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the doctor that owns the prescription.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the branch that owns the prescription.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the prescription.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all drug orders for this prescription.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(DrugOrder::class);
    }

    /**
     * Get the workflow instance for this prescription.
     */
    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    /**
     * Scope to get prescriptions by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get active prescriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get prescriptions by patient.
     */
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope to get prescriptions by doctor.
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope to get prescriptions by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('prescription_date', [$startDate, $endDate]);
    }

    /**
     * Get the total value of this prescription.
     */
    public function getTotalValue(): float
    {
        return $this->orders->sum(function($order) {
            return $order->getTotalValue();
        });
    }

    /**
     * Check if prescription has stock issues.
     */
    public function hasStockIssues(): bool
    {
        return $this->orders->contains(function($order) {
            return !$order->hasStockAvailable();
        });
    }

    /**
     * Get prescription urgency level.
     */
    public function getUrgencyLevel(): string
    {
        $hasUrgentDrugs = $this->orders->contains(function($order) {
            return in_array($order->drug->category, ['Antibiotics', 'Cardiovascular', 'Emergency']);
        });

        if ($hasUrgentDrugs) {
            return 'urgent';
        }

        if ($this->hasStockIssues()) {
            return 'delayed';
        }

        return 'routine';
    }

    /**
     * Get prescription status with details.
     */
    public function getDetailedStatus(): array
    {
        $totalOrders = $this->orders->count();
        $dispensedOrders = $this->orders->where('status', 'dispensed')->count();
        $pendingOrders = $this->orders->where('status', 'pending')->count();
        $outOfStockOrders = $this->orders->where('status', 'out_of_stock')->count();

        return [
            'total_orders' => $totalOrders,
            'dispensed_orders' => $dispensedOrders,
            'pending_orders' => $pendingOrders,
            'out_of_stock_orders' => $outOfStockOrders,
            'completion_percentage' => $totalOrders > 0 ? round(($dispensedOrders / $totalOrders) * 100, 2) : 0,
            'urgency_level' => $this->getUrgencyLevel(),
            'has_stock_issues' => $this->hasStockIssues()
        ];
    }

    /**
     * Scope to get prescriptions with stock issues.
     */
    public function scopeWithStockIssues($query)
    {
        return $query->whereHas('orders', function($orderQuery) {
            $orderQuery->where('status', 'out_of_stock');
        });
    }

    /**
     * Scope to get urgent prescriptions.
     */
    public function scopeUrgent($query)
    {
        return $query->whereHas('orders', function($orderQuery) {
            $orderQuery->whereHas('drug', function($drugQuery) {
                $drugQuery->whereIn('category', ['Antibiotics', 'Cardiovascular', 'Emergency']);
            });
        });
    }

    /**
     * Scope to get prescriptions by urgency level.
     */
    public function scopeByUrgency($query, $urgency)
    {
        switch ($urgency) {
            case 'urgent':
                return $query->urgent();
            case 'delayed':
                return $query->withStockIssues();
            case 'routine':
                return $query->whereDoesntHave('orders', function($orderQuery) {
                    $orderQuery->where('status', 'out_of_stock')
                              ->orWhereHas('drug', function($drugQuery) {
                                  $drugQuery->whereIn('category', ['Antibiotics', 'Cardiovascular', 'Emergency']);
                              });
                });
        }
        
        return $query;
    }
}
