<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasIdPrefix;

class Drug extends Model
{
    use HasFactory, HasIdPrefix;

    protected $fillable = [
        'id',
        'drug_number',
        'name',
        'generic_name',
        'drug_code',
        'category',
        'dosage_form',
        'strength',
        'unit',
        'manufacturer',
        'description',
        'indications',
        'contraindications',
        'side_effects',
        'dosage_instructions',
        'storage_conditions',
        'requires_prescription',
        'controlled_substance',
        'nhis_covered',
        'cost_price',
        'selling_price',
        'nhis_price',
        'is_active',
        'created_by',
        'updated_by'
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
        
        static::creating(function ($drug) {
            // Generate drug_number using ID prefix service
            if (empty($drug->drug_number)) {
                $drug->drug_number = app(\App\Services\IdPrefixService::class)->generateId('drug');
            }
        });
    }

    protected $casts = [
        'requires_prescription' => 'boolean',
        'controlled_substance' => 'boolean',
        'nhis_covered' => 'boolean',
        'is_active' => 'boolean',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'nhis_price' => 'decimal:2'
    ];

    /**
     * Get the drug stocks for this drug.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(DrugStock::class);
    }

    /**
     * Get the drug orders for this drug.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(DrugOrder::class);
    }

    /**
     * Get the prescriptions for this drug.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get the user who created the drug.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the drug.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active drugs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get drugs by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search drugs by name or code.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('generic_name', 'like', "%{$search}%")
              ->orWhere('drug_code', 'like', "%{$search}%");

        });
    }

    /**
     * Check if drug is in stock.
     */
    public function isInStock($branchId = null)
    {
        $query = $this->stocks()->where('current_stock', '>', 0);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->exists();
    }

    /**
     * Get current stock level.
     */
    public function getCurrentStock($branchId = null)
    {
        $query = $this->stocks();
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->sum('current_stock');
    }

    /**
     * Get the formatted drug name with strength.
     */
    public function getFormattedNameAttribute()
    {
        return $this->name . ' ' . $this->strength . ' ' . $this->unit;
    }

    /**
     * Check if drug requires prescription.
     */
    public function requiresPrescription()
    {
        return $this->requires_prescription;
    }

    /**
     * Check if drug is controlled substance.
     */
    public function isControlledSubstance()
    {
        return $this->controlled_substance;
    }

    /**
     * Check if drug is NHIS covered.
     */
    public function isNhisCovered()
    {
        return $this->nhis_covered;
    }
}
