<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BloodDonation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'donation_id',
        'donor_id',
        'donor_name',
        'donor_phone',
        'blood_group',
        'volume_ml',
        'donation_date',
        'donation_time',
        'expiry_date',
        'status',
        'screening_notes',
        'hiv_test',
        'hbv_test',
        'hcv_test',
        'syphilis_test',
        'blood_bag_number',
        'collected_by',
        'tested_by',
        'approved_by',
        'tested_at',
        'approved_at',
        'branch_id',
        'notes',
    ];

    protected $casts = [
        'donation_date' => 'date',
        'expiry_date' => 'date',
        'tested_at' => 'datetime',
        'approved_at' => 'datetime',
        'volume_ml' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->donation_id)) {
                $model->donation_id = 'BD-' . strtoupper(Str::random(10));
            }
            
            // Set expiry date (42 days for whole blood)
            if (empty($model->expiry_date) && !empty($model->donation_date)) {
                $model->expiry_date = $model->donation_date->addDays(42);
            }
        });
    }

    // Relationships
    public function donor()
    {
        return $this->belongsTo(Patient::class, 'donor_id');
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function testedBy()
    {
        return $this->belongsTo(User::class, 'tested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function transfusions()
    {
        return $this->hasMany(Transfusion::class, 'donation_id');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'approved')
                    ->where('expiry_date', '>', now());
    }

    public function scopeByBloodGroup($query, $bloodGroup)
    {
        return $query->where('blood_group', $bloodGroup);
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'approved')
                    ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->expiry_date < now();
    }

    public function getIsAvailableAttribute()
    {
        return $this->status === 'approved' && !$this->is_expired;
    }

    public function getAllTestsPassedAttribute()
    {
        return $this->hiv_test === 'negative' &&
               $this->hbv_test === 'negative' &&
               $this->hcv_test === 'negative' &&
               $this->syphilis_test === 'negative';
    }
}

