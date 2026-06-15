<?php

namespace App\Models;

use App\Traits\HasIdPrefix;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasFactory, HasIdPrefix;

    protected $entityType = 'delivery';

    protected $fillable = [
        'order_id',
        'delivery_address',
        'delivery_phone',
        'delivery_notes',
        'status',
        'estimated_delivery',
        'actual_delivery',
        'delivery_person',
        'rider_id',
        'assigned_at',
        'picked_up_at',
        'delivered_at',
        'rider_notes',
        'delivery_rating',
        'confirmation_code',
        'delivery_confirmation',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'estimated_delivery' => 'datetime',
        'actual_delivery' => 'datetime',
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_rating' => 'decimal:2'
    ];

    /**
     * Get the order that owns the delivery.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id');
    }

    /**
     * Get the delivery rider assigned to this delivery.
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(DeliveryRider::class, 'rider_id');
    }

    /**
     * Get the user who created the delivery.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the delivery.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get deliveries by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending deliveries.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get completed deliveries.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}