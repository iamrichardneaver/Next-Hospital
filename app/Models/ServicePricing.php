<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePricing extends Model
{
    use HasFactory;


    protected $table = 'service_pricing';

    public const PRICING_TYPE_MODULE_FEE = 'module_fee';
    public const PRICING_TYPE_ITEM_OVERRIDE = 'item_override';
    public const PRICING_TYPE_STANDALONE = 'standalone';

    protected $fillable = [
        'service_id',
        'service_name',
        'service_type',
        'pricing_type',
        'is_additive',
        'module_codes',
        'applies_on',
        'branch_id',
        'base_price',
        'currency',
        'description',
        'pricing_tiers',
        'is_active',
        'requires_approval',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'pricing_tiers' => 'array',
        'metadata' => 'array',
        'module_codes' => 'array',
        'is_active' => 'boolean',
        'is_additive' => 'boolean',
        'requires_approval' => 'boolean',
        'base_price' => 'decimal:2'
    ];

    /**
     * Get the branch that owns the service pricing.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the service pricing.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the service pricing.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all pricing rules for this service.
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class, 'service_id', 'service_id');
    }

    /**
     * Get all discount schemes for this service.
     */
    public function discountSchemes(): HasMany
    {
        return $this->hasMany(DiscountScheme::class, 'service_id', 'service_id');
    }

    /**
     * Scope a query to only include active service pricing.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by service type.
     */
    public function scopeByServiceType($query, $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Scope a query to filter by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get pricing for specific tier.
     */
    public function getTierPrice($tier = 'standard')
    {
        $tiers = $this->pricing_tiers ?? [];
        return $tiers[$tier] ?? $this->base_price;
    }

    /**
     * Check if service requires approval.
     */
    public function requiresApproval()
    {
        return $this->requires_approval;
    }

    /**
     * Get formatted price with currency.
     */
    public function getFormattedPrice($tier = 'standard')
    {
        $price = $this->getTierPrice($tier);
        return $this->currency . ' ' . number_format($price, 2);
    }
}
