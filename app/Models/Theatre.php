<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theatre extends Model
{
    use HasFactory;


    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'capacity',
        'equipment',
        'is_active',
        'working_hours'
    ];

    protected $casts = [
        'equipment' => 'array',
        'working_hours' => 'array',
        'is_active' => 'boolean'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function surgerySchedules()
    {
        return $this->hasMany(SurgerySchedule::class);
    }
}
