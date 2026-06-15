<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchSetting extends Model
{
    use HasFactory;


    protected $fillable = [
        'branch_id',
        'setting_key',
        'setting_value',
        'setting_type'
    ];

    protected $casts = [
        'setting_value' => 'string',
    ];

    /**
     * Get setting value for branch
     */
    public static function getValue($branchId, $key, $default = null)
    {
        $setting = static::where('branch_id', $branchId)
            ->where('setting_key', $key)
            ->first();

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->setting_value, $setting->setting_type);
    }

    /**
     * Set setting value for branch
     */
    public static function setValue($branchId, $key, $value, $type = 'string')
    {
        return static::updateOrCreate(
            ['branch_id' => $branchId, 'setting_key' => $key],
            ['setting_value' => $value, 'setting_type' => $type]
        );
    }

    /**
     * Cast value based on type
     */
    private static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Get all settings for branch
     */
    public static function getAllForBranch($branchId)
    {
        return static::where('branch_id', $branchId)
            ->pluck('setting_value', 'setting_key')
            ->toArray();
    }

    /**
     * Relationship with Branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}