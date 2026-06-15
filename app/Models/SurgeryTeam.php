<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'user_id',
        'role',
        'assigned_at',
        'assigned_by'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
    ];

    public function surgery()
    {
        return $this->belongsTo(SurgerySchedule::class, 'surgery_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
