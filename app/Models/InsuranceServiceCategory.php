<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_category',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Get all coverage policies for this category.
     */
    public function coveragePolicies(): HasMany
    {
        return $this->hasMany(InsuranceCoveragePolicy::class, 'service_category_id');
    }

    /**
     * Get child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(InsuranceServiceCategory::class, 'parent_category', 'code');
    }

    /**
     * Get parent category.
     */
    public function parent()
    {
        return $this->belongsTo(InsuranceServiceCategory::class, 'parent_category', 'code');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include root categories.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_category');
    }
}
