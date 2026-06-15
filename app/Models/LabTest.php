<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_code',
        'test_name',
        'category_id',
        'test_type_id',
        'template_id',
        'description',
        'specimen_type',
        'cost',
        'nhis_cost',
        'nhis_covered',
        'turnaround_hours',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'nhis_cost' => 'decimal:2',
        'nhis_covered' => 'boolean',
        'is_active' => 'boolean',
        'turnaround_hours' => 'integer',
        'sort_order' => 'integer'
    ];

    /**
     * Get the category that owns this test.
     */
    public function category()
    {
        return $this->belongsTo(LabTestCategory::class, 'category_id');
    }

    /**
     * Get the test type that owns this test.
     */
    public function testType()
    {
        return $this->belongsTo(LabTestType::class, 'test_type_id');
    }

    /**
     * Get the template assigned to this test.
     */
    public function template()
    {
        return $this->belongsTo(LabTestTemplate::class, 'template_id');
    }

    /**
     * Get the user who created this test.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this test.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the lab requests for this test.
     */
    public function labRequests()
    {
        return $this->hasMany(LabRequest::class, 'test_id');
    }

    /**
     * Scope a query to only include active tests.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by test type.
     */
    public function scopeByTestType($query, $testTypeId)
    {
        return $query->where('test_type_id', $testTypeId);
    }

    /**
     * Scope a query to only include NHIS covered tests.
     */
    public function scopeNhisCovered($query)
    {
        return $query->where('nhis_covered', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}

