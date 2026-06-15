<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientCart extends Model
{
    use HasFactory;

    protected $table = 'patient_cart';

    protected $fillable = [
        'patient_id',
        'store_item_id',
        'drug_id',
        'item_type',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function storeItem()
    {
        return $this->belongsTo(StoreItem::class);
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }
}

