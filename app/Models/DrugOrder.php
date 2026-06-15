<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'drug_id',
        'quantity',
        'quantity_dispensed',
        'dosage_instructions',
        'instructions',
        'frequency',
        'duration',
        'status',
        'dispensed_by',
        'dispensed_at',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'dispensed_at' => 'datetime',
    ];

    /**
     * Get the prescription that owns the drug order.
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Get the drug that owns the drug order.
     */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    /**
     * Get the user who dispensed the drug.
     */
    public function dispenser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    /**
     * Get the user who created the drug order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get orders by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get dispensed orders.
     */
    public function scopeDispensed($query)
    {
        return $query->where('status', 'dispensed');
    }

    /**
     * Check if order is fully dispensed.
     */
    public function isFullyDispensed(): bool
    {
        return $this->quantity_dispensed >= $this->quantity;
    }

    /**
     * Get remaining quantity to dispense.
     */
    public function getRemainingQuantity(): int
    {
        return max(0, $this->quantity - $this->quantity_dispensed);
    }

    /**
     * Get the total value of this drug order.
     */
    public function getTotalValue(): float
    {
        return $this->quantity * $this->drug->selling_price;
    }

    /**
     * Check if this order has stock available.
     */
    public function hasStockAvailable(): bool
    {
        $stock = \App\Models\DrugStock::where('drug_id', $this->drug_id)
            ->where('branch_id', $this->prescription->branch_id)
            ->where('is_active', true)
            ->first();

        if (!$stock) {
            return false;
        }

        return $stock->current_stock >= $this->getRemainingQuantity();
    }

    /**
     * Get stock information for this drug order.
     */
    public function getStockInfo(): ?array
    {
        $stock = \App\Models\DrugStock::where('drug_id', $this->drug_id)
            ->where('branch_id', $this->prescription->branch_id)
            ->where('is_active', true)
            ->first();

        if (!$stock) {
            return null;
        }

        return [
            'current_stock' => $stock->current_stock,
            'reorder_level' => $stock->reorder_level,
            'available' => $stock->current_stock >= $this->getRemainingQuantity(),
            'low_stock' => $stock->current_stock <= $stock->reorder_level,
            'expiry_date' => $stock->expiry_date
        ];
    }

    /**
     * Scope to get orders with stock issues.
     */
    public function scopeWithStockIssues($query)
    {
        return $query->where('status', 'out_of_stock')
                    ->orWhere('status', 'pending');
    }

    /**
     * Scope to get orders by drug category.
     */
    public function scopeByDrugCategory($query, $category)
    {
        return $query->whereHas('drug', function($drugQuery) use ($category) {
            $drugQuery->where('category', $category);

        });
    }

    /**
     * Scope to get urgent orders.
     */
    public function scopeUrgent($query)
    {
        return $query->whereHas('drug', function($drugQuery) {
            $drugQuery->whereIn('category', ['Antibiotics', 'Cardiovascular', 'Emergency']);
        });
    }
}
