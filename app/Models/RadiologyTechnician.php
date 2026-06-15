<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiologyTechnician extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'certification_body',
        'certification_date',
        'expiry_date',
        'specializations',
        'department_id',
        'is_active'
    ];

    protected $casts = [
        'certification_date' => 'date',
        'expiry_date' => 'date',
        'specializations' => 'array',
        'is_active' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(RadiologyDepartment::class, 'department_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(RadiologyRequest::class, 'technician_id');
    }

    public function studies(): HasMany
    {
        return $this->hasMany(RadiologyStudy::class, 'technician_id');
    }

    public function qcChecks(): HasMany
    {
        return $this->hasMany(RadiologyQcCheck::class, 'technician_id');
    }

    public function isCertificationValid(): bool
    {
        return $this->expiry_date > now();
    }

    public function canPerformModality(string $modalityCode): bool
    {
        return in_array($modalityCode, $this->specializations ?? []);
    }
}
