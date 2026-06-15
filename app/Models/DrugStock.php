<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugStock extends Model
{
    use HasFactory;


    protected $fillable = [
        'drug_id',
        'branch_id',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'expiry_date',
        'batch_number',
        'supplier',
        'cost_price',
        'selling_price',
        'reorder_level',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'current_stock' => 'integer',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'reorder_level' => 'integer'
    ];

    /**
     * Get the drug that owns the stock.
     */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    /**
     * Get the branch that owns the stock.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the stock.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the stock.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active stocks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->where(function ($q) {
            $q->whereColumn('current_stock', '<=', 'reorder_level')
                ->orWhere(function ($q2) {
                    $q2->whereNull('reorder_level')
                        ->whereColumn('current_stock', '<=', 'minimum_stock');
                });
        });
    }

    /**
     * Scope to get expiring soon items.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days));
    }

    /**
     * Scope to get expired items.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope to get stocks by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Check if stock is low.
     */
    public function isLowStock()
    {
        $level = $this->reorder_level ?? $this->minimum_stock ?? (int) env('PHARMACY_LOW_STOCK_THRESHOLD', 10);

        return $this->current_stock <= $level;
    }

    /**
     * Check if stock is expired.
     */
    public function isExpired()
    {
        return $this->expiry_date !== null && $this->expiry_date->lt(now()->startOfDay());
    }

    /**
     * Check if stock is expiring soon.
     */
    public function isExpiringSoon($days = 30)
    {
        if ($this->expiry_date === null || $this->isExpired()) {
            return false;
        }

        return $this->expiry_date->lte(now()->addDays($days));
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiry()
    {
        if ($this->expiry_date === null) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get stock status.
     */
    public function getStockStatus()
    {
        if ($this->isExpired()) {
            return 'expired';
        } elseif ($this->isExpiringSoon()) {
            return 'expiring_soon';
        } elseif ($this->isLowStock()) {
            return 'low_stock';
        } else {
            return 'normal';
        }
    }

    /**
     * Get stock status badge class.
     */
    public function getStockStatusBadgeClass()
    {
        switch ($this->getStockStatus()) {
            case 'expired':
                return 'bg-danger';
            case 'expiring_soon':
                return 'bg-warning text-dark';
            case 'low_stock':
                return 'bg-info';
            default:
                return 'bg-success';
        }
    }

    /**
     * Increment stock.
     */
    public function incrementStock($quantity)
    {
        $this->increment('current_stock', $quantity);
    }

    /**
     * Decrement stock.
     */
    public function decrementStock($quantity)
    {
        if ($this->current_stock >= $quantity) {
            $this->decrement('current_stock', $quantity);
            return true;
        }
        return false;
    }

    /**
     * Get total value of current stock.
     */
    public function getTotalValue()
    {
        return $this->current_stock * $this->cost_price;
    }
}
