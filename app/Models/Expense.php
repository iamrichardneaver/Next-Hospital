<?php

namespace App\Models;

use App\Traits\HasIdPrefix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasIdPrefix;

    public const DEPARTMENTS = [
        'pharmacy' => 'Pharmacy',
        'lab' => 'Laboratory',
        'radiology' => 'Radiology',
        'reception' => 'Reception / Front Desk',
        'nursing' => 'Nursing / Wards',
        'cashier' => 'Cashier',
        'general' => 'General / Admin',
    ];

    /** Category codes auto-generated from inventory PO receive — excluded from staff manual entry. */
    public const INVENTORY_CATEGORY_CODES = ['PHARM_STOCK', 'LAB_SUPPLIES', 'RADIOLOGY_SUPPLIES'];

    protected $fillable = [
        'expense_reference',
        'category_id',
        'branch_id',
        'department',
        'amount',
        'expense_date',
        'description',
        'reference',
        'payment_method',
        'vendor',
        'notes',
        'rejection_reason',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected function getEntityType(): string
    {
        return 'expense';
    }

    protected function getIdField(): string
    {
        return 'expense_reference';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'paid']);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public function scopeForDepartment($query, ?string $department)
    {
        if ($department) {
            $query->where('department', $department);
        }

        return $query;
    }

    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeManualOperational($query)
    {
        return $query->whereDoesntHave('category', fn ($q) => $q->whereIn('code', self::INVENTORY_CATEGORY_CODES));
    }

    public function getDepartmentLabel(): string
    {
        return self::DEPARTMENTS[$this->department] ?? ($this->department ? ucfirst($this->department) : '—');
    }

    public function isInventoryAuto(): bool
    {
        return in_array($this->category?->code, self::INVENTORY_CATEGORY_CODES, true);
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'approved' => 'bg-success',
            'paid' => 'bg-primary',
            'pending' => 'bg-warning text-dark',
            'rejected' => 'bg-danger',
            'draft' => 'bg-secondary',
            default => 'bg-info',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'pending', 'rejected'], true);
    }
}
