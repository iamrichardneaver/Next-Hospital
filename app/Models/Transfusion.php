<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transfusion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transfusion_id',
        'patient_id',
        'visit_id',
        'consultation_id',
        'donation_id',
        'blood_group_patient',
        'blood_group_donor',
        'blood_component',
        'volume_ml',
        'blood_bag_number',
        'indication',
        'cross_match_result',
        'cross_match_at',
        'cross_matched_by',
        'transfusion_started_at',
        'transfusion_completed_at',
        'administered_by',
        'doctor_id',
        'status',
        'pre_transfusion_vitals',
        'post_transfusion_vitals',
        'adverse_reactions',
        'reaction_severity',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'volume_ml' => 'decimal:2',
        'cross_match_at' => 'datetime',
        'transfusion_started_at' => 'datetime',
        'transfusion_completed_at' => 'datetime',
        'pre_transfusion_vitals' => 'array',
        'post_transfusion_vitals' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->transfusion_id)) {
                $model->transfusion_id = 'TF-' . strtoupper(Str::random(10));
            }
        });
    }

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function donation()
    {
        return $this->belongsTo(BloodDonation::class, 'donation_id');
    }

    public function administeredBy()
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function crossMatchedBy()
    {
        return $this->belongsTo(User::class, 'cross_matched_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeWithAdverseReactions($query)
    {
        return $query->where('status', 'adverse_reaction');
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    // Accessors
    public function getDurationMinutesAttribute()
    {
        if ($this->transfusion_started_at && $this->transfusion_completed_at) {
            return $this->transfusion_started_at->diffInMinutes($this->transfusion_completed_at);
        }
        return null;
    }

    public function getHasAdverseReactionAttribute()
    {
        return !empty($this->adverse_reactions) && $this->reaction_severity !== 'none';
    }

    public function getIsCompatibleAttribute()
    {
        return $this->cross_match_result === 'compatible';
    }
}

