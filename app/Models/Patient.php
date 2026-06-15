<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasIdPrefix;


class Patient extends Model
{
    use HasFactory, HasIdPrefix;

    /**
     * The field where the generated ID should be stored
     */
    protected $idField = 'patient_number';

    protected $fillable = [
        'id',
        'user_id',
        'patient_number',
        'first_name',
        'other_names',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'email',
        'password',
        'account_status',
        'account_activated_at',
        'activated_by',
        'rejection_reason',
        'address',
        'nhis_number',
        'ghana_card_number',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'photo',
        'branch_id',
        'registration_source',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return 'patient';
    }

    protected $casts = [
        'date_of_birth' => 'date',
        'account_activated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Capitalize each word (title case) for consistent name formatting.
     */
    protected function capitalizeName(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        return ucwords(strtolower(trim($value)));
    }

    /**
     * Set first_name with automatic capitalization.
     */
    public function setFirstNameAttribute(?string $value): void
    {
        $this->attributes['first_name'] = $this->capitalizeName($value);
    }

    /**
     * Set last_name with automatic capitalization.
     */
    public function setLastNameAttribute(?string $value): void
    {
        $this->attributes['last_name'] = $this->capitalizeName($value);
    }

    /**
     * Set other_names with automatic capitalization.
     */
    public function setOtherNamesAttribute(?string $value): void
    {
        $this->attributes['other_names'] = $this->capitalizeName($value);
    }

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'full_name',
        'age'
    ];

    /**
     * Get the user account associated with this patient.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch that owns the patient.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the patient.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if patient has active insurance.
     */
    public function hasActiveInsurance()
    {
        return $this->insurancePolicies()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->exists();
    }

    /**
     * Get active insurance policy.
     */
    public function getActiveInsurancePolicy()
    {
        return $this->insurancePolicies()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }

    /**
     * Get patient's age.
     */
    public function getAge()
    {
        return $this->date_of_birth ? now()->diffInYears($this->date_of_birth) : null;
    }

    /**
     * Get insurance type.
     */
    public function getInsuranceType()
    {
        $policy = $this->getActiveInsurancePolicy();
        return $policy && $policy->insuranceProvider ? $policy->insuranceProvider->name : 'none';
    }

    /**
     * Get the user who last updated the patient.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all appointments for this patient.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get all visits for this patient.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Get all consultations for this patient.
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    /**
     * Get all prescriptions for this patient.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get all lab requests for this patient.
     */
    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class);
    }

    /**
     * Get all radiology requests for this patient.
     */
    public function radiologyRequests(): HasMany
    {
        return $this->hasMany(RadiologyRequest::class);
    }

    /**
     * Get all scans for this patient.
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * Get all bed assignments for this patient.
     */
    public function bedAssignments(): HasMany
    {
        return $this->hasMany(BedAssignment::class);
    }

    /**
     * Get all invoices for this patient.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all allergies for this patient.
     */
    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    /**
     * Get all medical history for this patient.
     */
    public function medicalHistory(): HasMany
    {
        return $this->hasMany(PatientMedicalHistory::class);
    }

    /**
     * Get all insurance policies for this patient.
     */
    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    /**
     * Get all store orders for this patient.
     */
    public function storeOrders(): HasMany
    {
        return $this->hasMany(StoreOrder::class);
    }

    /**
     * Get the patient's full name.
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->first_name;
        if ($this->other_names) {
            $name .= ' ' . $this->other_names;
        }
        $name .= ' ' . $this->last_name;
        return $name;
    }

    /**
     * Get the patient's age.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Scope to search patients by name or patient number.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('other_names', 'like', "%{$search}%")
              ->orWhere('patient_number', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('nhis_number', 'like', "%{$search}%");
        });
    }
}