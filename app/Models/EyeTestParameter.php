<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EyeTestParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'parameter_code',
        'parameter_name',
        'description',
        'data_type',
        'input_type',
        'input_options',
        'unit',
        'decimal_places',
        'is_required',
        'is_critical',
        'validation_rules',
        'reference_ranges',
        'abnormal_criteria',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'input_options' => 'array',
        'validation_rules' => 'array',
        'reference_ranges' => 'array',
        'abnormal_criteria' => 'array',
        'is_required' => 'boolean',
        'is_critical' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function template(): BelongsTo
    {
        return $this->belongsTo(EyeTestTemplate::class, 'template_id');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(EyeTestResult::class, 'parameter_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeByDataType($query, $dataType)
    {
        return $query->where('data_type', $dataType);
    }

    public function scopeByInputType($query, $inputType)
    {
        return $query->where('input_type', $inputType);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('parameter_name');
    }

    // Accessors & Mutators
    public function getFormattedUnitAttribute()
    {
        return $this->unit ? ' (' . $this->unit . ')' : '';
    }

    public function getDisplayNameAttribute()
    {
        return $this->parameter_name . $this->formatted_unit;
    }

    // Methods
    public function getInputOptions(): array
    {
        return $this->input_options ?? [];
    }

    public function getValidationRules(): array
    {
        return $this->validation_rules ?? [];
    }

    public function getReferenceRanges(): array
    {
        return $this->reference_ranges ?? [];
    }

    public function getAbnormalCriteria(): array
    {
        return $this->abnormal_criteria ?? [];
    }

    public function isNumeric(): bool
    {
        return $this->data_type === 'numeric';
    }

    public function isText(): bool
    {
        return $this->data_type === 'text';
    }

    public function isBoolean(): bool
    {
        return $this->data_type === 'boolean';
    }

    public function isSelect(): bool
    {
        return in_array($this->input_type, ['select', 'radio', 'checkbox']);
    }

    public function isFile(): bool
    {
        return in_array($this->input_type, ['file', 'image']);
    }

    public function hasOptions(): bool
    {
        return !empty($this->input_options);
    }

    public function getDefaultValue()
    {
        switch ($this->data_type) {
            case 'numeric':
                return 0;
            case 'boolean':
                return false;
            case 'text':
            default:
                return '';
        }
    }

    public function validateValue($value): array
    {
        $errors = [];
        $rules = $this->getValidationRules();

        // Required validation
        if ($this->is_required && empty($value)) {
            $errors[] = $this->parameter_name . ' is required';
        }

        // Data type validation
        if (!empty($value)) {
            switch ($this->data_type) {
                case 'numeric':
                    if (!is_numeric($value)) {
                        $errors[] = $this->parameter_name . ' must be a number';
                    }
                    break;
                case 'boolean':
                    if (!is_bool($value) && !in_array($value, ['0', '1', 'true', 'false'])) {
                        $errors[] = $this->parameter_name . ' must be true or false';
                    }
                    break;
            }
        }

        // Custom validation rules
        foreach ($rules as $rule => $value) {
            switch ($rule) {
                case 'min':
                    if (is_numeric($value) && $value < $rule) {
                        $errors[] = $this->parameter_name . ' must be at least ' . $rule;
                    }
                    break;
                case 'max':
                    if (is_numeric($value) && $value > $rule) {
                        $errors[] = $this->parameter_name . ' must be at most ' . $rule;
                    }
                    break;
            }
        }

        return $errors;
    }
}
