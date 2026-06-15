<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasIdPrefix;

class Ward extends Model
{
    use HasFactory, HasIdPrefix;
    protected $fillable = [
        'id',
        'ward_number',
        'branch_id',
        'name',
        'code',
        'type',
        'total_beds',
        'description',
        'is_active'
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        // Don't generate ID prefix for primary key, use integer IDs
        return null;

    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($ward) {
            // Generate ward_number using ID prefix service
            if (empty($ward->ward_number)) {
                $ward->ward_number = app(\App\Services\IdPrefixService::class)->generateId('ward');
            }
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
        'total_beds' => 'integer',
        'branch_id' => 'integer'
    ];

    /**
     * Get the beds for the ward.
     */
    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }

    /**
     * Get the user who created the ward.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the ward.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
