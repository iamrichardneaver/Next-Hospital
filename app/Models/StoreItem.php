<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'drug_id',
        'name',
        'description',
        'category',
        'price',
        'stock_quantity',
        'minimum_stock',
        'image_url',
        'is_active',
        'is_available',
        'prescription_required',
        'dosage_instructions',
        'side_effects',
        'contraindications',
        'manufacturer',
        'batch_number',
        'expiry_date',
        'cost_price',
        'selling_price',
        'sku',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'prescription_required' => 'boolean',
        'expiry_date' => 'date',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'minimum_stock' => 'integer',
        'metadata' => 'array'
    ];

    /**
     * Resolve stored image path to a full URL for web/API consumers.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }

                return asset(ltrim($value, '/'));
            },
            set: fn (?string $value) => $value
        );
    }

    /**
     * Get the drug that owns the store item.
     */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    /**
     * Get the order items for this store item.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the user who created the store item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the store item.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get available items.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('stock_quantity', '>', 0);
    }

    /**
     * Scope to get low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= minimum_stock');
    }

    /**
     * Scope to get items by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search items.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhereHas('drug', function($drugQuery) use ($search) {
                  $drugQuery->where('name', 'like', "%{$search}%")
                           ->orWhere('generic_name', 'like', "%{$search}%");
              });
        });
    }
}
