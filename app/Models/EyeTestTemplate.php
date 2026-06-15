<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EyeTestTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'template_code',
        'template_name',
        'description',
        'test_type',
        'test_parameters',
        'reference_ranges',
        'abnormal_criteria',
        'equipment_config',
        'test_sequence',
        'estimated_duration_minutes',
        'requires_dilation',
        'dilation_requirements',
        'requires_dark_room',
        'requires_bright_light',
        'environmental_requirements',
        'is_active',
    ];

    protected $casts = [
        'test_parameters' => 'array',
        'reference_ranges' => 'array',
        'abnormal_criteria' => 'array',
        'equipment_config' => 'array',
        'test_sequence' => 'array',
        'dilation_requirements' => 'array',
        'environmental_requirements' => 'array',
        'requires_dilation' => 'boolean',
        'requires_dark_room' => 'boolean',
        'requires_bright_light' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function service(): BelongsTo
    {
        return $this->belongsTo(EyeService::class, 'service_id');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(EyeTestParameter::class, 'template_id');
    }

    public function testRequests(): HasMany
    {
        return $this->hasMany(EyeTestRequest::class, 'template_id');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(EyeTestResult::class, 'template_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTestType($query, $testType)
    {
        return $query->where('test_type', $testType);
    }

    public function scopeRequiresDilation($query)
    {
        return $query->where('requires_dilation', true);
    }

    public function scopeRequiresDarkRoom($query)
    {
        return $query->where('requires_dark_room', true);
    }

    public function scopeRequiresBrightLight($query)
    {
        return $query->where('requires_bright_light', true);
    }

    // Accessors & Mutators
    public function getFormattedDurationAttribute()
    {
        $hours = floor($this->estimated_duration_minutes / 60);
        $minutes = $this->estimated_duration_minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        return $minutes . 'm';
    }

    // Methods
    public function getTestSequence(): array
    {
        return $this->test_sequence ?? [];
    }

    public function getEquipmentConfig(): array
    {
        return $this->equipment_config ?? [];
    }

    public function getDilationRequirements(): array
    {
        return $this->dilation_requirements ?? [];
    }

    public function getEnvironmentalRequirements(): array
    {
        return $this->environmental_requirements ?? [];
    }

    public function getReferenceRanges(): array
    {
        return $this->reference_ranges ?? [];
    }

    public function getAbnormalCriteria(): array
    {
        return $this->abnormal_criteria ?? [];
    }

    public function requiresSpecialEnvironment(): bool
    {
        return $this->requires_dark_room || $this->requires_bright_light;
    }

    public function getEnvironmentType(): string
    {
        if ($this->requires_dark_room) {
            return 'dark_room';
        }
        if ($this->requires_bright_light) {
            return 'bright_light';
        }
        return 'normal';
    }
}
