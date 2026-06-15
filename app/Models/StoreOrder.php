<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\HasIdPrefix;

class StoreOrder extends Model
{
    use HasFactory, HasIdPrefix;

    /**
     * The field where the generated ID should be stored
     */
    protected $idField = 'store_order_number';

    protected $fillable = [
        'id',
        'store_order_number',
        'order_number',
        'patient_id',
        'branch_id',
        'visit_id',
        'order_date',
        'delivery_method',
        'delivery_address',
        'delivery_phone',
        'delivery_notes',
        'subtotal',
        'tax_amount',
        'delivery_fee',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_reference',
        'transaction_id',
        'paid_at',
        'payment_metadata',
        'status',
        'status_notes',
        'status_updated_at',
        'notes',
        'order_source',
        'created_by',
        'updated_by'
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        // Don't generate ID prefix for primary key, use integer IDs
        return null;

    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($storeOrder) {
            // Generate store_order_number using ID prefix service
            if (empty($storeOrder->store_order_number)) {
                $generatedNumber = app(\App\Services\IdPrefixService::class)->generateId('store_order');
                $storeOrder->store_order_number = $generatedNumber;
                // Also populate order_number to satisfy database NOT NULL constraint
                $storeOrder->order_number = $generatedNumber;
            }
        });
    }

    protected $casts = [
        'order_date' => 'date',
        'status_updated_at' => 'datetime',
        'paid_at' => 'datetime',
        'payment_metadata' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    /**
     * Get the patient that owns the order.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the branch that owns the order.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the order items for this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Get the order items for this order (alias for items).
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Get the delivery record for this order.
     */
    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class, 'order_id');
    }

    /**
     * Get the visit associated with this order.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Get the user who created the order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the order.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get orders by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get orders by patient.
     */
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope to get orders by delivery method.
     */
    public function scopeByDeliveryMethod($query, $method)
    {
        return $query->where('delivery_method', $method);
    }

    /**
     * Scope to get orders by date range.
     */
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('order_date', [$from, $to]);
    }
}
