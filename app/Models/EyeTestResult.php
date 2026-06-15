<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeTestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_request_id',
        'template_id',
        'parameter_id',
        'parameter_code',
        'parameter_name',
        'result_value',
        'formatted_value',
        'unit',
        'reference_range',
        'age_group',
        'gender',
        'result_status',
        'abnormal_flag',
        'clinical_interpretation',
        'technical_notes',
        'equipment_used',
        'test_conditions',
        'methodology_used',
        'test_performed_at',
        'result_entered_at',
        'result_verified_at',
        'performed_by',
        'verified_by',
        'requires_repeat',
        'repeat_reason',
        'is_critical_alert_sent',
        'critical_alert_sent_at',
    ];

    protected $casts = [
        'equipment_used' => 'array',
        'test_conditions' => 'array',
        'test_performed_at' => 'datetime',
        'result_entered_at' => 'datetime',
        'result_verified_at' => 'datetime',
        'critical_alert_sent_at' => 'datetime',
        'requires_repeat' => 'boolean',
        'is_critical_alert_sent' => 'boolean',
    ];

    // Relationships
    public function testRequest(): BelongsTo
    {
        return $this->belongsTo(EyeTestRequest::class, 'test_request_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EyeTestTemplate::class, 'template_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(EyeTestParameter::class, 'parameter_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeNormal($query)
    {
        return $query->where('result_status', 'normal');
    }

    public function scopeAbnormal($query)
    {
        return $query->where('result_status', 'abnormal');
    }

    public function scopeCritical($query)
    {
        return $query->where('result_status', 'critical');
    }

    public function scopePending($query)
    {
        return $query->where('result_status', 'pending');
    }

    public function scopeCancelled($query)
    {
        return $query->where('result_status', 'cancelled');
    }

    public function scopeByParameter($query, $parameterId)
    {
        return $query->where('parameter_id', $parameterId);
    }

    public function scopeByTestRequest($query, $testRequestId)
    {
        return $query->where('test_request_id', $testRequestId);
    }

    public function scopeRequiresRepeat($query)
    {
        return $query->where('requires_repeat', true);
    }

    public function scopeCriticalAlertSent($query)
    {
        return $query->where('is_critical_alert_sent', true);
    }

    public function scopeCriticalAlertNotSent($query)
    {
        return $query->where('is_critical_alert_sent', false);
    }

    // Accessors & Mutators
    public function getFormattedStatusAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->result_status));
    }

    public function getFormattedValueAttribute()
    {
        if ($this->formatted_value) {
            return $this->formatted_value;
        }

        if ($this->result_value) {
            $value = $this->result_value;
            if ($this->unit) {
                $value .= ' ' . $this->unit;
            }
            return $value;
        }

        return null;
    }

    public function getDisplayValueAttribute()
    {
        $value = $this->formatted_value ?: $this->result_value;
        
        if ($value && $this->unit) {
            return $value . ' ' . $this->unit;
        }
        
        return $value;
    }

    public function getAbnormalFlagDisplayAttribute()
    {
        if (!$this->abnormal_flag) {
            return null;
        }

        $flags = [
            'H' => 'High',
            'L' => 'Low',
            'HH' => 'Very High',
            'LL' => 'Very Low',
            'CRITICAL' => 'Critical',
            'ABNORMAL' => 'Abnormal',
        ];

        return $flags[$this->abnormal_flag] ?? $this->abnormal_flag;
    }

    // Methods
    public function isNormal(): bool
    {
        return $this->result_status === 'normal';
    }

    public function isAbnormal(): bool
    {
        return $this->result_status === 'abnormal';
    }

    public function isCritical(): bool
    {
        return $this->result_status === 'critical';
    }

    public function isPending(): bool
    {
        return $this->result_status === 'pending';
    }

    public function isCancelled(): bool
    {
        return $this->result_status === 'cancelled';
    }

    public function hasAbnormalFlag(): bool
    {
        return !empty($this->abnormal_flag);
    }

    public function isHigh(): bool
    {
        return in_array($this->abnormal_flag, ['H', 'HH']);
    }

    public function isLow(): bool
    {
        return in_array($this->abnormal_flag, ['L', 'LL']);
    }

    public function isCriticalFlag(): bool
    {
        return $this->abnormal_flag === 'CRITICAL';
    }

    public function isVerified(): bool
    {
        return $this->result_verified_at !== null;
    }

    public function isEntered(): bool
    {
        return $this->result_entered_at !== null;
    }

    public function needsRepeat(): bool
    {
        return $this->requires_repeat;
    }

    public function hasCriticalAlertBeenSent(): bool
    {
        return $this->is_critical_alert_sent;
    }

    public function markAsEntered(int $userId): bool
    {
        $this->update([
            'result_entered_at' => now(),
            'performed_by' => $userId,
        ]);
        return true;
    }

    public function markAsVerified(int $userId): bool
    {
        $this->update([
            'result_verified_at' => now(),
            'verified_by' => $userId,
        ]);
        return true;
    }

    public function markAsRepeat(string $reason, int $userId): bool
    {
        $this->update([
            'requires_repeat' => true,
            'repeat_reason' => $reason,
        ]);
        return true;
    }

    public function markCriticalAlertSent(): bool
    {
        $this->update([
            'is_critical_alert_sent' => true,
            'critical_alert_sent_at' => now(),
        ]);
        return true;
    }

    public function getEquipmentUsed(): array
    {
        return $this->equipment_used ?? [];
    }

    public function getTestConditions(): array
    {
        return $this->test_conditions ?? [];
    }

    public function setResultValue($value, string $formattedValue = null): bool
    {
        $this->update([
            'result_value' => $value,
            'formatted_value' => $formattedValue ?: $value,
        ]);
        return true;
    }

    public function setAbnormalFlag(string $flag): bool
    {
        $this->update(['abnormal_flag' => $flag]);
        return true;
    }

    public function setResultStatus(string $status): bool
    {
        $this->update(['result_status' => $status]);
        return true;
    }

    public function addClinicalInterpretation(string $interpretation): bool
    {
        $this->update(['clinical_interpretation' => $interpretation]);
        return true;
    }

    public function addTechnicalNotes(string $notes): bool
    {
        $this->update(['technical_notes' => $notes]);
        return true;
    }

    public function setEquipmentUsed(array $equipment): bool
    {
        $this->update(['equipment_used' => $equipment]);
        return true;
    }

    public function setTestConditions(array $conditions): bool
    {
        $this->update(['test_conditions' => $conditions]);
        return true;
    }

    public function setMethodology(string $methodology): bool
    {
        $this->update(['methodology_used' => $methodology]);
        return true;
    }

    public function getResultWithUnit(): string
    {
        $value = $this->result_value;
        if ($this->unit) {
            $value .= ' ' . $this->unit;
        }
        return $value;
    }

    public function getReferenceRangeWithUnit(): string
    {
        if (!$this->reference_range) {
            return 'N/A';
        }

        $range = $this->reference_range;
        if ($this->unit) {
            $range .= ' ' . $this->unit;
        }
        return $range;
    }
}
