<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'timezone',
        'date_format',
        'time_format',
        'currency',
        'currency_symbol',
        'tax_rate',
        'delivery_fee',
        'registration_fee',
        'registration_fee_apply_to_new_patients',
        'session_timeout',
        'password_min_length',
        'require_password_change',
        'password_change_days',
        'enable_audit_logs',
        'audit_log_retention_days',
        'enable_maintenance_mode',
        'maintenance_message'
    ];

    protected $casts = [
        'require_password_change' => 'boolean',
        'enable_audit_logs' => 'boolean',
        'enable_maintenance_mode' => 'boolean',
        'tax_rate' => 'decimal:4',
        'delivery_fee' => 'decimal:2',
        'registration_fee' => 'decimal:2',
        'registration_fee_apply_to_new_patients' => 'boolean',
    ];

    /**
     * Get current system settings
     */
    public static function current()
    {
        return static::first() ?? static::create([
            'timezone' => 'Africa/Accra',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'currency' => 'GHS',
            'currency_symbol' => '₵',
            'session_timeout' => 120,
            'password_min_length' => 8,
            'require_password_change' => false,
            'password_change_days' => 90,
            'enable_audit_logs' => true,
            'audit_log_retention_days' => 365,
            'enable_maintenance_mode' => false,
            'maintenance_message' => 'System is under maintenance. Please try again later.',
            'registration_fee' => 0,
            'registration_fee_apply_to_new_patients' => true,
        ]);
    }

    /**
     * Update system settings
     */
    public static function updateSettings($data)
    {
        $settings = static::current();
        $settings->update($data);
        return $settings;
    }

    /**
     * Apply timezone to application
     */
    public function applyTimezone()
    {
        config(['app.timezone' => $this->timezone]);
        date_default_timezone_set($this->timezone);
    }

    /**
     * Check if maintenance mode is enabled
     */
    public function isMaintenanceMode()
    {
        return $this->enable_maintenance_mode;
    }

    /**
     * Get formatted date
     */
    public function formatDate($date)
    {
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        
        return $date->format($this->date_format);
    }

    /**
     * Get formatted time
     */
    public function formatTime($time)
    {
        if (is_string($time)) {
            $time = \Carbon\Carbon::parse($time);
        }
        
        return $time->format($this->time_format);
    }

    /**
     * Get formatted datetime
     */
    public function formatDateTime($datetime)
    {
        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }
        
        return $datetime->format($this->date_format . ' ' . $this->time_format);
    }
}
