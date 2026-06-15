<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabTestType extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_code',
        'test_name',
        'category',
        'category_id',
        'template_id',
        'subcategory',
        'description',
        'specimen_type',
        'collection_method',
        'preparation_instructions',
        'collection_instructions',
        'storage_requirements',
        'transport_requirements',
        'parameters',
        'normal_ranges',
        'critical_values',
        'units',
        'routine_tat_hours',
        'urgent_tat_hours',
        'stat_tat_hours',
        'cost',
        'nhis_cost',
        'nhis_covered',
        'requires_qc',
        'qc_requirements',
        'requires_verification',
        'verification_requirements',
        'equipment_required',
        'reagents_required',
        'methodology',
        'ghs_code',
        'ghs_mandatory',
        'ghs_reporting_requirements',
        'is_active',
        'requires_doctor_approval',
        'requires_consultant_review',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'preparation_instructions' => 'array',
        'collection_instructions' => 'array',
        'storage_requirements' => 'array',
        'transport_requirements' => 'array',
        'parameters' => 'array',
        'normal_ranges' => 'array',
        'critical_values' => 'array',
        'units' => 'array',
        'qc_requirements' => 'array',
        'verification_requirements' => 'array',
        'reagents_required' => 'array',
        'ghs_reporting_requirements' => 'array',
        'is_active' => 'boolean',
        'nhis_covered' => 'boolean',
        'requires_qc' => 'boolean',
        'requires_verification' => 'boolean',
        'ghs_mandatory' => 'boolean',
        'requires_doctor_approval' => 'boolean',
        'requires_consultant_review' => 'boolean',
        'cost' => 'decimal:2',
        'nhis_cost' => 'decimal:2'
    ];

    /**
     * Get the template assigned to this test type.
     */
    public function template()
    {
        return $this->belongsTo(LabTestTemplate::class, 'template_id');
    }

    /**
     * Resolve the lab template for this test type (stored link or name/code match).
     */
    public function resolveTemplate(): ?LabTestTemplate
    {
        if ($this->template_id) {
            return $this->template ?? LabTestTemplate::find($this->template_id);
        }

        $name = trim((string) $this->test_name);
        if ($name === '') {
            return null;
        }

        $template = LabTestTemplate::query()
            ->where(function ($query) use ($name) {
                $query->whereRaw('LOWER(template_name) = ?', [strtolower($name)])
                    ->orWhere('template_code', 'LIKE', '%' . substr($name, 0, 4) . '%');
            })
            ->first();

        if (!$template) {
            $aliases = [
                'ERYTHROCYTE SEDIMENTATION RATE (ESR)' => 'ESR',
                'HB ELECTROPHORESIS' => 'HB ELECT',
                'PROSTATE SPECIFIC ANTIGEN' => 'PROSTATE SPECIFIC ANTIGEN (PSA)',
            ];
            if (isset($aliases[$name])) {
                $template = LabTestTemplate::whereRaw('LOWER(template_name) = ?', [strtolower($aliases[$name])])->first();
            }
        }

        return $template;
    }

    /**
     * Get template ID, persisting a name-based match when found.
     */
    public function getResolvedTemplateId(): ?int
    {
        if ($this->template_id) {
            return (int) $this->template_id;
        }

        $template = $this->resolveTemplate();
        if (!$template) {
            return null;
        }

        $this->update(['template_id' => $template->id]);

        return (int) $template->id;
    }

    /**
     * Get the category that owns this test type.
     */
    public function category()
    {
        return $this->belongsTo(LabTestCategory::class, 'category_id');
    }

    /**
     * Get the tests for this test type.
     */
    public function tests()
    {
        return $this->hasMany(LabTest::class, 'test_type_id');
    }

    /**
     * Reagents/consumables consumed per test run.
     */
    public function consumableItems()
    {
        return $this->hasMany(LabTestTypeItem::class, 'lab_test_type_id');
    }

    /**
     * Get the lab results for this test type through lab requests.
     */
    public function labResults()
    {
        return $this->hasManyThrough(LabResult::class, LabRequest::class, 'test_type_id', 'lab_request_id');
    }

    /**
     * Get the user who created this test type.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this test type.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active test types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include NHIS covered test types.
     */
    public function scopeNhisCovered($query)
    {
        return $query->where('nhis_covered', true);
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
     * Check if test requires doctor approval.
     */
    public function requiresApproval()
    {
        return $this->requires_doctor_approval;
    }

    /**
     * Check if test requires consultant review.
     */
    public function requiresReview()
    {
        return $this->requires_consultant_review;
    }

    /**
     * Get the cost for NHIS patients.
     */
    public function getNhisCost()
    {
        return $this->nhis_covered ? $this->nhis_cost : $this->cost;
    }

    /**
     * Get the normal range for a specific parameter.
     */
    public function getNormalRange($parameter, $ageGroup = null, $gender = null)
    {
        $normalRanges = $this->normal_ranges;
        
        if (!$normalRanges || !isset($normalRanges[$parameter])) {
            return null;
        }

        $parameterRanges = $normalRanges[$parameter];
        
        // Try to get age/gender specific range first
        if ($ageGroup && isset($parameterRanges[$ageGroup])) {
            return $parameterRanges[$ageGroup];
        }
        
        if ($gender && isset($parameterRanges[$gender])) {
            return $parameterRanges[$gender];
        }
        
        // Fall back to general range
        if (isset($parameterRanges['Adult'])) {
            return $parameterRanges['Adult'];
        }
        
        return $parameterRanges[array_key_first($parameterRanges)] ?? null;
    }

    /**
     * Get critical values for a specific parameter.
     */
    public function getCriticalValues($parameter)
    {
        $criticalValues = $this->critical_values;
        
        if (!$criticalValues || !isset($criticalValues[$parameter])) {
            return [];
        }

        return $criticalValues[$parameter];
    }

    /**
     * Check if a result value is critical for a parameter.
     */
    public function isCriticalValue($parameter, $value)
    {
        $criticalValues = $this->getCriticalValues($parameter);
        $numericValue = floatval($value);
        
        foreach ($criticalValues as $type => $threshold) {
            $thresholdValue = floatval(str_replace(['<', '>', '='], '', $threshold));
            
            if (strpos($threshold, '<') !== false && $numericValue < $thresholdValue) {
                return true;
            }
            if (strpos($threshold, '>') !== false && $numericValue > $thresholdValue) {
                return true;
            }
        }
        
        return false;
    }
}
