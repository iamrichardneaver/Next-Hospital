<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'icd_code',
        'diagnosis_description',
        'diagnosis_type',
        'confidence_level',
        'diagnosed_by',
        'diagnosis_date',
        'notes',
        'is_primary',
        'is_active'
    ];

    protected $casts = [
        'diagnosis_date' => 'date',
        'is_primary' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Get the consultation that owns the diagnosis.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the user who made the diagnosis.
     */
    public function diagnosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diagnosed_by');
    }

    /**
     * Scope to get primary diagnoses.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to get secondary diagnoses.
     */
    public function scopeSecondary($query)
    {
        return $query->where('is_primary', false);
    }

    /**
     * Scope to get active diagnoses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get diagnoses by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('diagnosis_type', $type);
    }

    /**
     * Scope to get diagnoses by confidence level.
     */
    public function scopeByConfidence($query, $confidence)
    {
        return $query->where('confidence_level', $confidence);
    }

    /**
     * Check if this is a primary diagnosis.
     */
    public function isPrimary()
    {
        return $this->is_primary;
    }

    /**
     * Check if this is a secondary diagnosis.
     */
    public function isSecondary()
    {
        return !$this->is_primary;
    }

    /**
     * Check if diagnosis is active.
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Get the formatted diagnosis with ICD code.
     */
    public function getFormattedDiagnosisAttribute()
    {
        return $this->icd_code . ' - ' . $this->diagnosis_description;
    }

    /**
     * Get the diagnosis type badge class.
     */
    public function getDiagnosisTypeBadgeClass()
    {
        switch ($this->diagnosis_type) {
            case 'primary':
                return 'badge-primary';
            case 'secondary':
                return 'badge-secondary';
            case 'differential':
                return 'badge-info';
            default:
                return 'badge-light';
        }
    }

    /**
     * Get the confidence level badge class.
     */
    public function getConfidenceLevelBadgeClass()
    {
        switch ($this->confidence_level) {
            case 'confirmed':
                return 'badge-success';
            case 'probable':
                return 'badge-warning';
            case 'possible':
                return 'badge-info';
            default:
                return 'badge-light';
        }
    }

    /**
     * Get the diagnosis type display name.
     */
    public function getDiagnosisTypeDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->diagnosis_type));
    }

    /**
     * Get the confidence level display name.
     */
    public function getConfidenceLevelDisplayAttribute()
    {
        return ucfirst($this->confidence_level);
    }

    /**
     * Mark as primary diagnosis.
     */
    public function markAsPrimary()
    {
        $this->update(['is_primary' => true]);
    }

    /**
     * Mark as secondary diagnosis.
     */
    public function markAsSecondary()
    {
        $this->update(['is_primary' => false]);
    }

    /**
     * Activate diagnosis.
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate diagnosis.
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }
}
