<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GhsReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'report_id',
        'report_type',
        'report_period',
        'period_start_date',
        'period_end_date',
        'reporting_month',
        'reporting_quarter',
        'reporting_year',
        'disease_data',
        'total_cases',
        'total_deaths',
        'total_recoveries',
        'anc_visits',
        'anc_first_trimester',
        'deliveries_total',
        'deliveries_facility',
        'deliveries_home',
        'cesarean_sections',
        'maternal_deaths',
        'stillbirths',
        'live_births',
        'neonatal_deaths',
        'infant_deaths',
        'under_five_deaths',
        'bcg_vaccinations',
        'opv_vaccinations',
        'pentavalent_vaccinations',
        'measles_vaccinations',
        'yellow_fever_vaccinations',
        'malaria_cases',
        'malaria_deaths',
        'tb_cases',
        'tb_deaths',
        'hiv_cases',
        'hiv_deaths',
        'cholera_cases',
        'meningitis_cases',
        'typhoid_cases',
        'covid_cases',
        'covid_deaths',
        'covid_recoveries',
        'covid_vaccinations',
        'additional_indicators',
        'comments',
        'challenges',
        'recommendations',
        'status',
        'submitted_at',
        'accepted_at',
        'prepared_by',
        'reviewed_by',
        'submitted_by',
        'report_file_path',
        'supporting_documents',
        'branch_id',
        'facility_code',
        'district',
        'region',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'disease_data' => 'array',
        'additional_indicators' => 'array',
        'supporting_documents' => 'array',
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->report_id)) {
                $prefix = match($model->report_type) {
                    'monthly_disease_surveillance' => 'MDS',
                    'weekly_disease_surveillance' => 'WDS',
                    'idsr' => 'IDSR',
                    'maternal_health' => 'MH',
                    'child_health' => 'CH',
                    default => 'GHS',
                };
                $model->report_id = $prefix . '-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('reporting_year', $year);
    }

    public function scopeByMonth($query, $month)
    {
        return $query->where('reporting_month', $month);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_review']);
    }

    // Accessors
    public function getTotalBirthsAttribute()
    {
        return $this->live_births + $this->stillbirths;
    }

    public function getMaternalMortalityRateAttribute()
    {
        if ($this->deliveries_total > 0) {
            return round(($this->maternal_deaths / $this->deliveries_total) * 100000, 2);
        }
        return 0;
    }

    public function getInfantMortalityRateAttribute()
    {
        if ($this->live_births > 0) {
            return round(($this->infant_deaths / $this->live_births) * 1000, 2);
        }
        return 0;
    }
}

