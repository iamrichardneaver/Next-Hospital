<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasIdPrefix;

class Branch extends Model
{
    use HasFactory, HasIdPrefix;

    protected $fillable = [
        'id',
        'branch_number',
        'name',
        'location',
        'address',
        'phone',
        'email',
        'is_active'
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        // Don't generate ID prefix for branches, use integer IDs
        return null;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($branch) {
            // Generate branch_number using ID prefix service
            if (empty($branch->branch_number)) {
                $branch->branch_number = app(\App\Services\IdPrefixService::class)->generateId('branch');
            }
        });
    }

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    public function emergencyVisits()
    {
        return $this->hasMany(EmergencyVisit::class);
    }

    public function theatres()
    {
        return $this->hasMany(Theatre::class);
    }

    public function crashCarts()
    {
        return $this->hasMany(CrashCart::class);
    }

    public function wards()
    {
        return $this->hasMany(Ward::class);
    }

    public function facilityUsers()
    {
        return $this->hasMany(FacilityUser::class);
    }

    public function branchSettings()
    {
        return $this->hasMany(BranchSetting::class);
    }

    /**
     * Default active branch: MAIN code first, then first active by ID.
     */
    public static function getDefault(): ?self
    {
        return static::where('code', 'MAIN')->where('is_active', true)->first()
            ?? static::where('is_active', true)->orderBy('id')->first();
    }

    /**
     * Branch holding the most patient records (primary clinical data branch).
     */
    public static function getPrimaryClinicalBranchId(): int|string|null
    {
        $fromPatients = Patient::query()
            ->selectRaw('branch_id, COUNT(*) as total')
            ->groupBy('branch_id')
            ->orderByDesc('total')
            ->value('branch_id');

        if ($fromPatients) {
            return (int) $fromPatients;
        }

        return static::getDefault()?->id;
    }
}