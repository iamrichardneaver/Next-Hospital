<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LabReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'report_number',
        'patient_id',
        'lab_request_id',
        'report_type',
        'status',
        'clinical_history',
        'specimen_info',
        'methodology',
        'results',
        'interpretation',
        'recommendations',
        'comments',
        'technician_name',
        'reviewed_by',
        'reviewed_at',
        'approved_at',
        'approved_by',
        'attachments',
        'is_critical',
        'is_abnormal',
        'priority',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_critical' => 'boolean',
        'is_abnormal' => 'boolean',
        'attachments' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->report_number)) {
                $model->report_number = 'LR-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    // Relationships
    public function labRequest()
    {
        return $this->belongsTo(LabRequest::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeAbnormal($query)
    {
        return $query->where('is_abnormal', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }
}

