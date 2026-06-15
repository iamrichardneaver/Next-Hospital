<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            // General Settings
            [
                'key' => 'hospital_name',
                'value' => 'NextHospital',
                'group' => 'general',
                'type' => 'string',
                'description' => 'Hospital or clinic name',
                'is_public' => true
            ],
            [
                'key' => 'system_email',
                'value' => 'info@nexthospital.com',
                'group' => 'general',
                'type' => 'string',
                'description' => 'System email address',
                'is_public' => false
            ],
            [
                'key' => 'system_phone',
                'value' => '',
                'group' => 'general',
                'type' => 'string',
                'description' => 'System phone number',
                'is_public' => true
            ],
            [
                'key' => 'address',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Hospital address',
                'is_public' => true
            ],
            [
                'key' => 'timezone',
                'value' => 'Africa/Accra',
                'group' => 'general',
                'type' => 'string',
                'description' => 'System timezone',
                'is_public' => false
            ],
            
            // Billing Settings
            [
                'key' => 'currency',
                'value' => 'GHS',
                'group' => 'billing',
                'type' => 'string',
                'description' => 'Default currency code',
                'is_public' => true
            ],
            [
                'key' => 'currency_symbol',
                'value' => '₵',
                'group' => 'billing',
                'type' => 'string',
                'description' => 'Currency symbol',
                'is_public' => true
            ],
            [
                'key' => 'tax_rate',
                'value' => '0',
                'group' => 'billing',
                'type' => 'number',
                'description' => 'Tax rate percentage',
                'is_public' => false
            ],
            
            // Lab Settings
            [
                'key' => 'default_lab_tat',
                'value' => '24',
                'group' => 'lab',
                'type' => 'number',
                'description' => 'Default lab turnaround time (hours)',
                'is_public' => false
            ],
            [
                'key' => 'lab_report_header',
                'value' => '',
                'group' => 'lab',
                'type' => 'text',
                'description' => 'Lab report header text',
                'is_public' => false
            ],
            [
                'key' => 'lab_report_footer',
                'value' => '',
                'group' => 'lab',
                'type' => 'text',
                'description' => 'Lab report footer text',
                'is_public' => false
            ],
            
            // Email Settings
            [
                'key' => 'smtp_host',
                'value' => '',
                'group' => 'email',
                'type' => 'string',
                'description' => 'SMTP server host',
                'is_public' => false
            ],
            [
                'key' => 'smtp_port',
                'value' => '587',
                'group' => 'email',
                'type' => 'number',
                'description' => 'SMTP server port',
                'is_public' => false
            ],
            [
                'key' => 'smtp_username',
                'value' => '',
                'group' => 'email',
                'type' => 'string',
                'description' => 'SMTP username',
                'is_public' => false
            ],
            [
                'key' => 'smtp_encryption',
                'value' => 'tls',
                'group' => 'email',
                'type' => 'string',
                'description' => 'SMTP encryption type',
                'is_public' => false
            ],
            
            // Notification Settings
            [
                'key' => 'notification_enabled',
                'value' => '1',
                'group' => 'notifications',
                'type' => 'boolean',
                'description' => 'Enable system notifications',
                'is_public' => false
            ],
            [
                'key' => 'sms_enabled',
                'value' => '0',
                'group' => 'notifications',
                'type' => 'boolean',
                'description' => 'Enable SMS notifications',
                'is_public' => false
            ],
            [
                'key' => 'email_notifications_enabled',
                'value' => '1',
                'group' => 'notifications',
                'type' => 'boolean',
                'description' => 'Enable email notifications',
                'is_public' => false
            ],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('✓ Default settings created successfully!');
    }
}
