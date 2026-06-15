<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enable_offline_mode',
        'sync_interval',
        'max_offline_days',
        'auto_sync_on_connect',
        'sync_on_startup',
        'conflict_resolution',
        'enable_real_time_sync',
        'websocket_url',
        'enable_compression',
        'max_file_size',
    ];

    protected $casts = [
        'enable_offline_mode' => 'boolean',
        'auto_sync_on_connect' => 'boolean',
        'sync_on_startup' => 'boolean',
        'enable_real_time_sync' => 'boolean',
        'enable_compression' => 'boolean',
    ];

    /**
     * Get current sync settings
     */
    public static function current()
    {
        return static::first() ?? static::create([
            'enable_offline_mode' => true,
            'sync_interval' => 300,
            'max_offline_days' => 7,
            'auto_sync_on_connect' => true,
            'sync_on_startup' => true,
            'conflict_resolution' => 'server_wins',
            'enable_real_time_sync' => true,
            'websocket_url' => null,
            'enable_compression' => true,
            'max_file_size' => 10485760,
        ]);
    }

    /**
     * Update sync settings
     */
    public static function updateSettings($data)
    {
        $settings = static::current();
        $settings->update($data);
        return $settings;
    }
}