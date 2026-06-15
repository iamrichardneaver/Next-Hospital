<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabTestCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Get the test types for this category.
     */
    public function testTypes()
    {
        return $this->hasMany(LabTestType::class, 'category_id');
    }

    /**
     * Get the tests for this category.
     */
    public function tests()
    {
        return $this->hasMany(LabTest::class, 'category_id');
    }

    /**
     * Get the templates for this category.
     */
    public function templates()
    {
        return $this->hasMany(LabTestTemplate::class, 'category_id');
    }

    /**
     * Get the user who created this category.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this category.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}

