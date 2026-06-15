<?php

namespace App\Models;

use App\Traits\HasIdPrefix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabPurchaseOrder extends Model
{
    use HasIdPrefix;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'branch_id',
        'status',
        'total_amount',
        'notes',
        'ordered_by',
        'ordered_at',
        'received_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    protected function getEntityType(): string
    {
        return 'lab_purchase_order';
    }

    protected function getIdField(): string
    {
        return 'po_number';
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabPurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function canReceive(): bool
    {
        return in_array($this->status, ['ordered', 'partially_received'], true);
    }

    public function canMarkOrdered(): bool
    {
        return $this->status === 'draft';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'ordered'], true);
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'draft' => 'bg-secondary',
            'ordered' => 'bg-primary',
            'partially_received' => 'bg-warning text-dark',
            'received' => 'bg-success',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'partially_received' => 'Partially Received',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
