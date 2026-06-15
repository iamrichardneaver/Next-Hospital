<?php

namespace App\Models;

use App\Traits\HasIdPrefix;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryRider extends Model
{
    use HasFactory, HasIdPrefix, SoftDeletes;

    protected $entityType = 'rider';

    protected $fillable = [
        'rider_number',
        'user_id',
        'branch_id',
        'phone',
        'emergency_contact',
        'vehicle_type',
        'vehicle_number',
        'license_number',
        'status',
        'total_deliveries',
        'successful_deliveries',
        'failed_deliveries',
        'rating',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'total_deliveries' => 'integer',
        'successful_deliveries' => 'integer',
        'failed_deliveries' => 'integer'
    ];

    /**
     * Get the user associated with this rider.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch this rider belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all deliveries assigned to this rider.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'rider_id');
    }

    /**
     * Get the user who created this rider.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this rider.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active riders.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get available riders (active or off_duty, not on_delivery).
     */
    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['active', 'off_duty']);
    }

    /**
     * Scope to get riders on delivery.
     */
    public function scopeOnDelivery($query)
    {
        return $query->where('status', 'on_delivery');
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get pending deliveries for this rider.
     */
    public function pendingDeliveries()
    {
        return $this->deliveries()->whereIn('status', ['assigned', 'in_transit'])->get();
    }

    /**
     * Update delivery statistics.
     */
    public function updateStatistics()
    {
        $this->total_deliveries = $this->deliveries()->count();
        $this->successful_deliveries = $this->deliveries()->where('status', 'delivered')->count();
        $this->failed_deliveries = $this->deliveries()->where('status', 'failed')->count();
        
        // Calculate average rating
        $avgRating = $this->deliveries()->whereNotNull('delivery_rating')->avg('delivery_rating');
        if ($avgRating) {
            $this->rating = round($avgRating, 2);
        }
        
        $this->save();
    }
}
