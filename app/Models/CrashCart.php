<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrashCart extends Model
{
    use HasFactory;


    protected $fillable = [
        'branch_id',
        'item_name',
        'description',
        'category',
        'current_quantity',
        'minimum_quantity',
        'maximum_quantity',
        'unit',
        'expiry_date',
        'last_used',
        'last_used_by',
        'usage_notes',
        'is_active'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'last_used' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function lastUsedBy()
    {
        return $this->belongsTo(User::class, 'last_used_by');
    }
}
