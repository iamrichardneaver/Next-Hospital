<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodInventory extends Model
{
    use HasFactory;

    protected $table = 'blood_inventory';

    protected $fillable = [
        'blood_group',
        'blood_component',
        'total_units',
        'available_units',
        'reserved_units',
        'used_units',
        'expired_units',
        'minimum_stock_level',
        'optimal_stock_level',
        'branch_id',
        'last_updated_at',
        'last_updated_by',
        'notes',
    ];

    protected $casts = [
        'total_units' => 'decimal:2',
        'available_units' => 'decimal:2',
        'reserved_units' => 'decimal:2',
        'used_units' => 'decimal:2',
        'expired_units' => 'decimal:2',
        'minimum_stock_level' => 'decimal:2',
        'optimal_stock_level' => 'decimal:2',
        'last_updated_at' => 'datetime',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereRaw('available_units < minimum_stock_level');
    }

    public function scopeByBloodGroup($query, $bloodGroup)
    {
        return $query->where('blood_group', $bloodGroup);
    }

    public function scopeByComponent($query, $component)
    {
        return $query->where('blood_component', $component);
    }

    // Accessors
    public function getIsLowStockAttribute()
    {
        return $this->available_units < $this->minimum_stock_level;
    }

    public function getIsCriticalAttribute()
    {
        return $this->available_units < ($this->minimum_stock_level * 0.5);
    }

    public function getStockPercentageAttribute()
    {
        if ($this->optimal_stock_level == 0) {
            return 0;
        }
        return round(($this->available_units / $this->optimal_stock_level) * 100, 2);
    }

    // Methods
    public function addUnits($units)
    {
        $this->total_units += $units;
        $this->available_units += $units;
        $this->save();
    }

    public function reserveUnits($units)
    {
        if ($this->available_units >= $units) {
            $this->available_units -= $units;
            $this->reserved_units += $units;
            $this->save();
            return true;
        }
        return false;
    }

    public function useUnits($units)
    {
        if ($this->reserved_units >= $units) {
            $this->reserved_units -= $units;
            $this->used_units += $units;
            $this->save();
            return true;
        }
        return false;
    }

    public function expireUnits($units)
    {
        $this->available_units -= $units;
        $this->expired_units += $units;
        $this->save();
    }
}

