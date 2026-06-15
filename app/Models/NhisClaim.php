<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NhisClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'claim_id',
        'patient_id',
        'visit_id',
        'invoice_id',
        'insurance_policy_id',
        'nhis_number',
        'membership_id',
        'scheme_type',
        'scheme_code',
        'policy_start_date',
        'policy_expiry_date',
        'member_status',
        'facility_code',
        'branch_id',
        'visit_date',
        'visit_type',
        'admission_number',
        'admission_date',
        'discharge_date',
        'days_admitted',
        'icd_code',
        'diagnosis',
        'procedures',
        'medications',
        'investigations',
        'total_amount',
        'nhis_covered_amount',
        'patient_copay',
        'claimed_amount',
        'approved_amount',
        'rejected_amount',
        'paid_amount',
        'outstanding_amount',
        'claim_items',
        'status',
        'submission_date',
        'submission_batch_number',
        'claim_reference_number',
        'vetting_date',
        'vetted_by',
        'approval_date',
        'approval_reference',
        'rejection_reason',
        'query_details',
        'query_response_deadline',
        'query_response',
        'query_resolved_at',
        'payment_date',
        'payment_reference',
        'payment_voucher_number',
        'attached_documents',
        'has_prescription',
        'has_lab_results',
        'has_imaging_results',
        'prepared_by',
        'submitted_by',
        'doctor_id',
        'notes',
        'last_updated_by_nhia',
        'resubmission_count',
        'resubmitted_at',
    ];

    protected $casts = [
        'policy_start_date' => 'date',
        'policy_expiry_date' => 'date',
        'visit_date' => 'date',
        'admission_date' => 'date',
        'discharge_date' => 'date',
        'submission_date' => 'date',
        'vetting_date' => 'date',
        'approval_date' => 'date',
        'query_response_deadline' => 'date',
        'query_resolved_at' => 'date',
        'payment_date' => 'date',
        'resubmitted_at' => 'datetime',
        'last_updated_by_nhia' => 'datetime',
        'total_amount' => 'decimal:2',
        'nhis_covered_amount' => 'decimal:2',
        'patient_copay' => 'decimal:2',
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'rejected_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'claim_items' => 'array',
        'procedures' => 'array',
        'medications' => 'array',
        'investigations' => 'array',
        'attached_documents' => 'array',
        'has_prescription' => 'boolean',
        'has_lab_results' => 'boolean',
        'has_imaging_results' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->claim_id)) {
                $model->claim_id = 'NHIS-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
            
            // Calculate days admitted for IPD
            if ($model->visit_type === 'IPD' && $model->admission_date && $model->discharge_date) {
                $model->days_admitted = $model->admission_date->diffInDays($model->discharge_date);
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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function insurancePolicy()
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function vettedBy()
    {
        return $this->belongsTo(User::class, 'vetted_by');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_submission']);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'partially_approved']);
    }

    public function scopeQueried($query)
    {
        return $query->where('status', 'queried');
    }

    public function scopeByNhisNumber($query, $nhisNumber)
    {
        return $query->where('nhis_number', $nhisNumber);
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->status === 'queried' && 
               $this->query_response_deadline && 
               $this->query_response_deadline < now();
    }

    public function getApprovalRateAttribute()
    {
        if ($this->claimed_amount > 0) {
            return round(($this->approved_amount / $this->claimed_amount) * 100, 2);
        }
        return 0;
    }

    public function getIsFullyPaidAttribute()
    {
        return $this->paid_amount >= $this->approved_amount;
    }
}

