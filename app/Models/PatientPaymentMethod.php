<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'payment_type',
        'provider',
        'account_name',
        'account_number',
        'card_last_four',
        'card_brand',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}

