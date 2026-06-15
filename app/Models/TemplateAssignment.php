<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_type',
        'category',
        'subcategory',
        'specimen_type',
        'template_id',
        'is_default',
        'priority',
        'auto_select',
        'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'auto_select' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(LabTestTemplate::class, 'template_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeAutoSelect($query)
    {
        return $query->where('auto_select', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByTestType($query, $testType)
    {
        return $query->where('test_type', $testType);
    }
}

