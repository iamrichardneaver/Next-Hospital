<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeService extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_code',
        'service_name',
        'description',
        'category',
        'subcategory',
        'service_type',
        'instructions',
        'duration_minutes',
        'requires_doctor',
        'requires_equipment',
        'equipment_required',
        'preparation_instructions',
        'post_service_instructions',
        'base_price',
        'nhis_price',
        'nhis_covered',
        'currency',
        'ghs_code',
        'ghs_mandatory',
        'ghs_reporting_requirements',
        'is_active',
        'requires_approval',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'equipment_required' => 'array',
        'preparation_instructions' => 'array',
        'post_service_instructions' => 'array',
        'ghs_reporting_requirements' => 'array',
        'requires_doctor' => 'boolean',
        'requires_equipment' => 'boolean',
        'nhis_covered' => 'boolean',
        'ghs_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'base_price' => 'decimal:2',
        'nhis_price' => 'decimal:2',
    ];

    // Relationships
    public function templates(): HasMany
    {
        return $this->hasMany(EyeTestTemplate::class, 'service_id');
    }

    public function testRequests(): HasMany
    {
        return $this->hasMany(EyeTestRequest::class, 'service_id');
    }

    public function billingItems(): HasMany
    {
        return $this->hasMany(EyeServiceBillingItem::class, 'service_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    public function scopeNhisCovered($query)
    {
        return $query->where('nhis_covered', true);
    }

    // Accessors & Mutators
    public function getFormattedPriceAttribute()
    {
        return $this->currency . ' ' . number_format($this->base_price, 2);
    }

    public function getFormattedNhisPriceAttribute()
    {
        if ($this->nhis_price) {
            return $this->currency . ' ' . number_format($this->nhis_price, 2);
        }
        return null;
    }

    // Methods
    public function isNhisEligible(): bool
    {
        return $this->nhis_covered && $this->nhis_price !== null;
    }

    public function getEquipmentList(): array
    {
        return $this->equipment_required ?? [];
    }

    public function getPreparationSteps(): array
    {
        return $this->preparation_instructions ?? [];
    }

    public function getPostServiceSteps(): array
    {
        return $this->post_service_instructions ?? [];
    }
}
