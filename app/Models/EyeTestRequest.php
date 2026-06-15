<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasIdPrefix;

class EyeTestRequest extends Model
{
    use HasFactory, HasIdPrefix;


    protected $fillable = [
        'request_number',
        'patient_id',
        'appointment_id',
        'consultation_id',
        'service_id',
        'template_id',
        'requested_by',
        'assigned_to',
        'branch_id',
        'clinical_notes',
        'reason_for_test',
        'priority',
        'status',
        'requires_dilation',
        'dilation_completed',
        'dilation_time',
        'dilation_notes',
        'scheduled_at',
        'started_at',
        'completed_at',
        'actual_duration_minutes',
        'has_results',
        'results_entered_at',
        'results_verified_at',
        'results_entered_by',
        'results_verified_by',
        'quality_control_passed',
        'quality_control_notes',
        'quality_control_by',
        'quality_control_at',
        'service_cost',
        'billing_processed',
        'invoice_id',
        'created_by',
    ];

    protected $casts = [
        'requires_dilation' => 'boolean',
        'dilation_completed' => 'boolean',
        'dilation_time' => 'datetime',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'results_entered_at' => 'datetime',
        'results_verified_at' => 'datetime',
        'quality_control_passed' => 'boolean',
        'quality_control_at' => 'datetime',
        'billing_processed' => 'boolean',
        'service_cost' => 'decimal:2',
    ];

    // Relationships
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(EyeService::class, 'service_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EyeTestTemplate::class, 'template_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function resultsEnteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'results_entered_by');
    }

    public function resultsVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'results_verified_by');
    }

    public function qualityControlBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quality_control_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(EyeTestResult::class, 'test_request_id');
    }

    public function testImages(): HasMany
    {
        return $this->hasMany(EyeTestImage::class, 'test_request_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(EyeTestComment::class, 'test_request_id');
    }

    public function billingItems(): HasMany
    {
        return $this->hasMany(EyeServiceBillingItem::class, 'test_request_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeRequiresDilation($query)
    {
        return $query->where('requires_dilation', true);
    }

    public function scopeHasResults($query)
    {
        return $query->where('has_results', true);
    }

    public function scopeQualityControlPassed($query)
    {
        return $query->where('quality_control_passed', true);
    }

    // Accessors & Mutators
    public function getFormattedStatusAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getFormattedPriorityAttribute()
    {
        return ucfirst($this->priority);
    }

    public function getFormattedServiceCostAttribute()
    {
        return 'GHS ' . number_format($this->service_cost, 2);
    }

    public function getDurationFormattedAttribute()
    {
        if ($this->actual_duration_minutes) {
            $hours = floor($this->actual_duration_minutes / 60);
            $minutes = $this->actual_duration_minutes % 60;
            
            if ($hours > 0) {
                return $hours . 'h ' . $minutes . 'm';
            }
            return $minutes . 'm';
        }
        return null;
    }

    // Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }

    public function isEmergency(): bool
    {
        return $this->priority === 'emergency';
    }

    public function canBeStarted(): bool
    {
        return $this->isPending() && $this->assigned_to !== null;
    }

    public function canBeCompleted(): bool
    {
        return $this->isInProgress() && $this->has_results;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    public function startTest(): bool
    {
        if ($this->canBeStarted()) {
            $this->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    public function completeTest(): bool
    {
        if ($this->canBeCompleted()) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
                'actual_duration_minutes' => $this->started_at ? 
                    $this->started_at->diffInMinutes(now()) : null,
            ]);
            return true;
        }
        return false;
    }

    public function cancelTest(string $reason = null): bool
    {
        if ($this->canBeCancelled()) {
            $this->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    public function markResultsEntered(int $userId): bool
    {
        $this->update([
            'has_results' => true,
            'results_entered_at' => now(),
            'results_entered_by' => $userId,
        ]);
        return true;
    }

    public function markResultsVerified(int $userId): bool
    {
        $this->update([
            'results_verified_at' => now(),
            'results_verified_by' => $userId,
        ]);
        return true;
    }

    public function markQualityControlPassed(int $userId, string $notes = null): bool
    {
        $this->update([
            'quality_control_passed' => true,
            'quality_control_at' => now(),
            'quality_control_by' => $userId,
            'quality_control_notes' => $notes,
        ]);
        return true;
    }

    public function getPrimaryImage(): ?EyeTestImage
    {
        return $this->testImages()->where('is_primary', true)->first();
    }

    public function getImagesByType(string $imageType): \Illuminate\Database\Eloquent\Collection
    {
        return $this->testImages()->where('image_type', $imageType)->get();
    }

    public function getAbnormalResults(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->testResults()->whereIn('result_status', ['abnormal', 'critical'])->get();
    }

    public function getCriticalResults(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->testResults()->where('result_status', 'critical')->get();
    }

    public function hasAbnormalResults(): bool
    {
        return $this->getAbnormalResults()->count() > 0;
    }

    public function hasCriticalResults(): bool
    {
        return $this->getCriticalResults()->count() > 0;
    }

    public function generateRequestNumber(): string
    {
        $prefix = 'ETR';
        $year = date('Y');
        $month = date('m');
        $sequence = str_pad($this->id, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $year . $month . $sequence;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->request_number)) {
                $model->request_number = $model->generateRequestNumber();
            }
        });
    }
}
