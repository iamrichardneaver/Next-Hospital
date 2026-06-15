<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_visit_id',
        'patient_id',
        'alert_type',
        'message',
        'priority',
        'location',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'acknowledgment_notes',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    public function emergencyVisit()
    {
        return $this->belongsTo(EmergencyVisit::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
