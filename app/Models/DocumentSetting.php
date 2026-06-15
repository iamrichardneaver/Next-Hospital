<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'header_template',
        'footer_template',
        'header_height',
        'footer_height',
        'margin_top',
        'margin_bottom',
        'margin_left',
        'margin_right',
        'font_family',
        'font_size',
        'logo_position',
        'show_company_info',
        'show_contact_info',
        'show_website',
    ];

    protected $casts = [
        'show_company_info' => 'boolean',
        'show_contact_info' => 'boolean',
        'show_website' => 'boolean',
    ];

    /**
     * Get current document settings
     */
    public static function current()
    {
        return static::first() ?? static::create([
            'header_template' => '',
            'footer_template' => '',
            'header_height' => 50,
            'footer_height' => 30,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
            'font_family' => 'Arial',
            'font_size' => 12,
            'logo_position' => 'left',
            'show_company_info' => true,
            'show_contact_info' => true,
            'show_website' => true,
        ]);
    }

    /**
     * Update document settings
     */
    public static function updateSettings($data)
    {
        $settings = static::current();
        $settings->update($data);
        return $settings;
    }

    /**
     * Get settings for specific document type
     */
    public static function getForDocumentType($documentType)
    {
        return static::current();
    }

    /**
     * Get all document settings
     */
    public static function getAll()
    {
        return static::get();
    }
}