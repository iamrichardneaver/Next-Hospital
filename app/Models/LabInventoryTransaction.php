<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabInventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'item_type',
        'transaction_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_number',
        'supplier_id',
        'location',
        'notes',
        'transaction_date',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'transaction_date' => 'date'
    ];

    /**
     * Get the supplier for this transaction.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created this transaction.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this transaction.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get incoming transactions.
     */
    public function scopeIncoming($query)
    {
        return $query->whereIn('transaction_type', ['purchase', 'return', 'adjustment_in']);
    }

    /**
     * Scope to get outgoing transactions.
     */
    public function scopeOutgoing($query)
    {
        return $query->whereIn('transaction_type', ['usage', 'waste', 'adjustment_out', 'transfer']);
    }

    /**
     * Scope to get transactions by item type.
     */
    public function scopeByItemType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    /**
     * Scope to get transactions by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
