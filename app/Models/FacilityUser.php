<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityUser extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'role_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
