<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabTestTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_code',
        'template_name',
        'category_id',
        'category',
        'subcategory',
        'description',
        'template_content',
        'quantitative_parameters',
        'qualitative_parameters',
        'template_type',
        'specimen_type',
        'collection_instructions',
        'preparation_instructions',
        'storage_requirements',
        'transport_requirements',
        'parameters_config',
        'reference_ranges',
        'critical_values',
        'units_config',
        'flagging_rules',
        'methodology',
        'equipment_required',
        'reagents_required',
        'quality_control_requirements',
        'calibration_requirements',
        'routine_tat_hours',
        'urgent_tat_hours',
        'stat_tat_hours',
        'cost',
        'nhis_cost',
        'nhis_covered',
        'ghs_code',
        'ghs_mandatory',
        'ghs_reporting_requirements',
        'international_standard',
        'compliance_requirements',
        'requires_doctor_approval',
        'requires_consultant_review',
        'requires_pathologist_review',
        'is_active',
        'is_template_bank',
        'template_source',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'collection_instructions' => 'array',
        'preparation_instructions' => 'array',
        'storage_requirements' => 'array',
        'transport_requirements' => 'array',
        'parameters_config' => 'array',
        'reference_ranges' => 'array',
        'critical_values' => 'array',
        'units_config' => 'array',
        'flagging_rules' => 'array',
        'reagents_required' => 'array',
        'quality_control_requirements' => 'array',
        'calibration_requirements' => 'array',
        'ghs_reporting_requirements' => 'array',
        'compliance_requirements' => 'array',
        'quantitative_parameters' => 'array',
        'qualitative_parameters' => 'array',
        'is_active' => 'boolean',
        'nhis_covered' => 'boolean',
        'ghs_mandatory' => 'boolean',
        'requires_doctor_approval' => 'boolean',
        'requires_consultant_review' => 'boolean',
        'requires_pathologist_review' => 'boolean',
        'is_template_bank' => 'boolean',
        'cost' => 'decimal:2',
        'nhis_cost' => 'decimal:2'
    ];

    /**
     * Get the category that owns this template.
     */
    public function category()
    {
        return $this->belongsTo(LabTestCategory::class, 'category_id');
    }

    /**
     * Get the tests using this template.
     */
    public function tests()
    {
        return $this->hasMany(LabTest::class, 'template_id');
    }

    /**
     * Get the parameters for this template.
     */
    public function parameters()
    {
        return $this->hasMany(LabTestParameter::class, 'template_id');
    }

    /**
     * Get the test results for this template.
     */
    public function testResults()
    {
        return $this->hasMany(LabTestResult::class, 'template_id');
    }

    /**
     * Get the user who created this template.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this template.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include template bank templates.
     */
    public function scopeTemplateBank($query)
    {
        return $query->where('is_template_bank', true);
    }

    /**
     * Scope a query to filter by template type.
     */
    public function scopeByTemplateType($query, $templateType)
    {
        return $query->where('template_type', $templateType);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the turnaround time based on priority.
     */
    public function getTurnaroundTime($priority = 'routine')
    {
        switch ($priority) {
            case 'urgent':
                return $this->urgent_tat_hours;
            case 'stat':
                return $this->stat_tat_hours;
            default:
                return $this->routine_tat_hours;
        }
    }

    /**
     * Check if template requires doctor approval.
     */
    public function requiresApproval()
    {
        return $this->requires_doctor_approval;
    }

    /**
     * Check if template requires consultant review.
     */
    public function requiresReview()
    {
        return $this->requires_consultant_review;
    }

    /**
     * Check if template requires pathologist review.
     */
    public function requiresPathologistReview()
    {
        return $this->requires_pathologist_review;
    }

    /**
     * Get the cost for NHIS patients.
     */
    public function getNhisCost()
    {
        return $this->nhis_covered ? $this->nhis_cost : $this->cost;
    }

    /**
     * Get parameters by input type.
     */
    public function getParametersByInputType($inputType)
    {
        return $this->parameters()->where('input_type', $inputType)->get();
    }

    /**
     * Get required parameters.
     */
    public function getRequiredParameters()
    {
        return $this->parameters()->where('is_required', true)->orderBy('sort_order')->get();
    }

    /**
     * Get critical parameters.
     */
    public function getCriticalParameters()
    {
        return $this->parameters()->where('is_critical', true)->get();
    }

    /**
     * Check if template supports qualitative results.
     */
    public function supportsQualitative()
    {
        return in_array($this->template_type, ['qualitative', 'combined']);
    }

    /**
     * Check if template supports quantitative results.
     */
    public function supportsQuantitative()
    {
        return in_array($this->template_type, ['quantitative', 'combined']);
    }

    /**
     * Check if template supports narrative results.
     */
    public function supportsNarrative()
    {
        return in_array($this->template_type, ['narrative', 'combined']);
    }

    /**
     * Get the template type display name.
     */
    public function getTemplateTypeDisplayName()
    {
        return ucfirst($this->template_type);
    }

    /**
     * Get the category display name.
     */
    public function getCategoryDisplayName()
    {
        return ucfirst(str_replace('_', ' ', $this->category));
    }

    /**
     * Get the subcategory display name.
     */
    public function getSubcategoryDisplayName()
    {
        return $this->subcategory ? ucfirst(str_replace('_', ' ', $this->subcategory)) : null;
    }
}
