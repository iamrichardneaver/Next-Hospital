<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffProfile extends Model
{
    /**
     * staff_profiles.id is bigint auto_increment; do not use HasIdPrefix (which sets string id).
     */
    protected $fillable = [
        'user_id',
        'branch_id',
        'employee_id',
        'first_name',
        'last_name',
        'contact',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'department',
        'specialization',
        'license_number',
        'license_expiry',
        'emergency_contact',
        'online_status',
        'photo',
        'is_active'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'license_expiry' => 'date',
        'is_active' => 'boolean'
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
