<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    // You can add custom methods and properties here
    
    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'category',
    ];

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}

