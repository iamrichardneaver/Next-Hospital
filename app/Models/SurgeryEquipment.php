<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryEquipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'theatre_id',
        'name',
        'description',
        'category',
        'serial_number',
        'model',
        'manufacturer',
        'purchase_date',
        'warranty_expiry',
        'last_maintenance',
        'next_maintenance',
        'status',
        'is_active'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'last_maintenance' => 'datetime',
        'next_maintenance' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function theatre()
    {
        return $this->belongsTo(Theatre::class);
    }
}
