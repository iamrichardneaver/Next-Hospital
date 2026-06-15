<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\BrandingSetting;
use App\Models\IdPrefixSetting;
use App\Models\EmailSetting;
use App\Models\SmsSetting;
use App\Models\PaymentSetting;
use App\Models\DocumentSetting;
use App\Models\SyncSetting;
use App\Models\SystemSetting;
use App\Models\MobileAppSetting;
use App\Models\BranchSetting;
use App\Models\ApiSetting;
use App\Models\JitsiSetting;

class SettingsService
{
    /**
     * Get all settings grouped by category
     */
    public function getAllSettings()
    {
        try {
            return [
                'general' => Setting::getByGroup('general'),
                'branding' => BrandingSetting::current(),
                'mobile_app' => MobileAppSetting::current(),
                'id_prefixes' => IdPrefixSetting::all(),
                'email' => EmailSetting::current(),
                'sms' => SmsSetting::current(),
                'payment' => PaymentSetting::current(),
                'document' => DocumentSetting::getAll(),
                'sync' => SyncSetting::current(),
                'system' => SystemSetting::current(),
                'api' => ApiSetting::current(),
                'jitsi' => JitsiSetting::current(),
            ];
        } catch (\Exception $e) {
            // Return default settings if any model fails
            return [
                'general' => [],
                'branding' => BrandingSetting::current(),
                'mobile_app' => MobileAppSetting::current(),
                'id_prefixes' => [],
                'email' => EmailSetting::current(),
                'sms' => SmsSetting::current(),
                'payment' => PaymentSetting::current(),
                'document' => DocumentSetting::current(),
                'sync' => SyncSetting::current(),
                'system' => SystemSetting::current(),
                'api' => ApiSetting::current(),
                'jitsi' => JitsiSetting::current(),
            ];
        }
    }

    /**
     * Get public settings for frontend
     */
    public function getPublicSettings()
    {
        return [
            'branding' => $this->getBrandingSettings(),
            'system' => $this->getSystemSettings(),
            'mobile_app' => $this->getMobileAppSettings(),
            'api' => $this->getApiSettings(),
            'general' => Setting::getPublicSettings(),
        ];
    }

    /**
     * Get API configuration settings
     */
    public function getApiSettings()
    {
        return ApiSetting::getPublicConfig();
    }

    /**
     * Get frontend API configuration
     */
    public function getFrontendApiConfig()
    {
        return ApiSetting::getFrontendConfig();
    }

    /**
     * Get mobile API configuration
     */
    public function getMobileApiConfig()
    {
        return ApiSetting::getMobileConfig();
    }

    /**
     * Update API settings
     */
    public function updateApiSettings($data)
    {
        return ApiSetting::updateSettings($data);
    }

    /**
     * Get branding settings
     */
    public function getBrandingSettings()
    {
        $branding = BrandingSetting::current();
        
        return [
            'platform_name' => $branding->platform_name,
            'business_name' => $branding->business_name,
            'tagline' => $branding->business_name,
            'business_address' => $branding->business_address,
            'business_phone' => $branding->business_phone,
            'business_email' => $branding->business_email,
            'business_website' => $branding->business_website,
            'logo_url' => $branding->logo_url,
            'logo_path' => $branding->getRawOriginal('logo_path'),
            'logo_absolute_path' => $branding->logo_absolute_path,
            'logo_base64' => $branding->logo_base64,
            'favicon_url' => $branding->favicon_url,
            'mobile_logo_url' => $branding->mobile_logo_url,
            'primary_color' => $branding->primary_color,
            'secondary_color' => $branding->secondary_color,
            'accent_color' => $branding->accent_color,
            'custom_css' => $branding->custom_css,
        ];
    }

    /**
     * Get system settings
     */
    public function getSystemSettings()
    {
        $system = SystemSetting::current();
        
        return [
            'timezone' => $system->timezone,
            'date_format' => $system->date_format,
            'time_format' => $system->time_format,
            'currency' => $system->currency,
            'currency_symbol' => $system->currency_symbol,
            'session_timeout' => $system->session_timeout,
            'enable_maintenance_mode' => $system->enable_maintenance_mode,
            'maintenance_message' => $system->maintenance_message,
            'registration_fee' => (float) ($system->registration_fee ?? 0),
            'registration_fee_apply_to_new_patients' => (bool) ($system->registration_fee_apply_to_new_patients ?? true),
        ];
    }

    /**
     * Get mobile app settings
     */
    public function getMobileAppSettings()
    {
        $mobile = MobileAppSetting::current();
        
        return [
            'app_name' => $mobile->app_name,
            'app_short_name' => $mobile->app_short_name,
            'app_icon_url' => $mobile->app_icon_url,
            'splash_screen_url' => $mobile->splash_screen_url,
            'app_logo_url' => $mobile->app_logo_url,
            'package_name' => $mobile->package_name,
            'version' => $mobile->version,
            'app_description' => $mobile->app_description,
            'permissions' => $mobile->app_permissions,
            'features' => [
                'offline_mode' => $mobile->enable_offline_mode,
                'push_notifications' => $mobile->enable_push_notifications,
                'biometric_auth' => $mobile->enable_biometric_auth,
            ],
        ];
    }

    /**
     * Get email settings
     */
    public function getEmailSettings()
    {
        $email = EmailSetting::current();
        
        return [
            'mail_driver' => $email->mail_driver,
            'mail_host' => $email->mail_host,
            'mail_port' => $email->mail_port,
            'mail_username' => $email->mail_username,
            'mail_password' => $email->mail_password,
            'mail_encryption' => $email->mail_encryption,
            'mail_from_address' => $email->mail_from_address,
            'mail_from_name' => $email->mail_from_name,
            'mail_verify_peer' => $email->mail_verify_peer,
            'is_active' => $email->is_active,
        ];
    }

    /**
     * Get SMS settings
     */
    public function getSmsSettings()
    {
        $sms = SmsSetting::current();
        
        return [
            'provider' => $sms->provider,
            'api_url' => $sms->api_url,
            'api_key' => $sms->api_key,
            'api_secret' => $sms->api_secret,
            'sender_id' => $sms->sender_id,
            'custom_headers' => $sms->custom_headers,
            'request_body_template' => $sms->request_body_template,
            'response_success_field' => $sms->response_success_field,
            'is_active' => $sms->is_active,
        ];
    }

    /**
     * Get payment settings
     */
    public function getPaymentSettings()
    {
        $payment = PaymentSetting::current();
        
        return [
            'provider' => $payment->provider,
            'environment' => $payment->environment,
            'public_key' => $payment->public_key,
            'secret_key' => $payment->secret_key,
            'merchant_id' => $payment->merchant_id,
            'webhook_urls' => $payment->webhook_urls,
            'supported_currencies' => $payment->supported_currencies,
            'supported_payment_methods' => $payment->supported_payment_methods,
            'is_active' => $payment->is_active,
        ];
    }

    /**
     * Update branding settings
     */
    public function updateBrandingSettings($data)
    {
        return BrandingSetting::updateSettings($data);
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings($data)
    {
        $settings = SystemSetting::updateSettings($data);
        
        // Apply timezone if changed
        if (isset($data['timezone'])) {
            $settings->applyTimezone();
        }
        
        return $settings;
    }

    /**
     * Update mobile app settings
     */
    public function updateMobileAppSettings($data)
    {
        return MobileAppSetting::updateSettings($data);
    }

    /**
     * Update email settings
     */
    public function updateEmailSettings($data)
    {
        $settings = EmailSetting::updateSettings($data);
        
        // Apply settings to config
        $settings->applyToConfig();
        
        return $settings;
    }

    /**
     * Update SMS settings
     */
    public function updateSmsSettings($data)
    {
        return SmsSetting::updateSettings($data);
    }

    /**
     * Update payment settings
     */
    public function updatePaymentSettings($data)
    {
        return PaymentSetting::updateSettings($data);
    }

    /**
     * Update sync settings
     */
    public function updateSyncSettings($data)
    {
        return SyncSetting::updateSettings($data);
    }

    /**
     * Update ID prefix settings
     */
    public function updateIdPrefixSettings($data)
    {
        $settings = IdPrefixSetting::updateOrCreate(
            ['entity_type' => $data['entity_type']],
            $data
        );
        
        return $settings;
    }

    /**
     * Update document settings
     */
    public function updateDocumentSettings($documentType, $data)
    {
        return DocumentSetting::updateOrCreate(
            ['document_type' => $documentType],
            $data
        );
    }

    /**
     * Generate ID for entity type
     */
    public function generateId($entityType)
    {
        return IdPrefixSetting::generateId($entityType);
    }

    /**
     * Send SMS using configured provider
     */
    public function sendSms($phoneNumber, $message)
    {
        $smsSettings = SmsSetting::current();
        return $smsSettings->sendSms($phoneNumber, $message);
    }

    /**
     * Get document settings for PDF generation
     */
    public function getDocumentSettings($documentType)
    {
        return DocumentSetting::getForDocumentType($documentType);
    }

    /**
     * Get branch-specific setting
     */
    public function getBranchSetting($branchId, $key, $default = null)
    {
        return BranchSetting::getValue($branchId, $key, $default);
    }

    /**
     * Set branch-specific setting
     */
    public function setBranchSetting($branchId, $key, $value, $type = 'string')
    {
        return BranchSetting::setValue($branchId, $key, $value, $type);
    }

    /**
     * Get all branch settings
     */
    public function getAllBranchSettings($branchId)
    {
        return BranchSetting::getAllForBranch($branchId);
    }

    /**
     * Check if maintenance mode is enabled
     */
    public function isMaintenanceMode()
    {
        return SystemSetting::current()->isMaintenanceMode();
    }

    /**
     * Get maintenance message
     */
    public function getMaintenanceMessage()
    {
        return SystemSetting::current()->maintenance_message;
    }

    /**
     * Get general settings
     */
    public function getGeneralSettings()
    {
        $general = Setting::getByGroup('general');
        $branding = BrandingSetting::current();
        $system = SystemSetting::current();
        
        return [
            'platform_name' => $branding->platform_name,
            'business_name' => $branding->business_name,
            'business_address' => $branding->business_address,
            'business_phone' => $branding->business_phone,
            'business_email' => $branding->business_email,
            'business_website' => $branding->business_website,
            'timezone' => $system->timezone,
            'date_format' => $system->date_format,
            'time_format' => $system->time_format,
            'currency' => $system->currency,
            'currency_symbol' => $system->currency_symbol,
            'language' => $general['language'] ?? 'en',
        ];
    }

    /**
     * Update general settings
     */
    public function updateGeneralSettings($data)
    {
        // Update branding settings
        $brandingData = [
            'platform_name' => $data['platform_name'],
            'business_name' => $data['business_name'],
            'business_address' => $data['business_address'],
            'business_phone' => $data['business_phone'],
            'business_email' => $data['business_email'],
            'business_website' => $data['business_website'],
        ];
        BrandingSetting::updateSettings($brandingData);

        // Update system settings
        $systemData = [
            'timezone' => $data['timezone'],
            'date_format' => $data['date_format'],
            'time_format' => $data['time_format'],
            'currency' => $data['currency'],
            'currency_symbol' => $data['currency_symbol'],
        ];
        SystemSetting::updateSettings($systemData);

        // Update general settings
        Setting::setValue('language', $data['language'], 'string', 'general', 'Application language');

        return $this->getGeneralSettings();
    }



    /**
     * Get sync settings
     */
    public function getSyncSettings()
    {
        $sync = SyncSetting::current();
        
        return [
            'enable_offline_mode' => $sync->enable_offline_mode,
            'sync_interval' => $sync->sync_interval,
            'max_offline_days' => $sync->max_offline_days,
            'auto_sync_on_connect' => $sync->auto_sync_on_connect,
            'sync_on_startup' => $sync->sync_on_startup,
            'conflict_resolution' => $sync->conflict_resolution,
            'enable_real_time_sync' => $sync->enable_real_time_sync,
            'websocket_url' => $sync->websocket_url,
            'enable_compression' => $sync->enable_compression,
            'max_file_size' => $sync->max_file_size,
        ];
    }

    /**
     * Get mobile app configuration
     */
    public function getMobileAppConfiguration()
    {
        $mobile = MobileAppSetting::current();
        $branding = BrandingSetting::current();
        $system = SystemSetting::current();
        $jitsi = JitsiSetting::current();
        
        return [
            // Hospital Branding Information
            'hospital_name' => $branding->business_name ?? $mobile->app_name ?? config('app.name'),
            'hospital_tagline' => $branding->platform_name ?? 'Your Health, Our Priority',
            'hospital_address' => $branding->business_address ?? '',
            'hospital_phone' => $branding->business_phone ?? '',
            'hospital_email' => $branding->business_email ?? '',
            'hospital_website' => $branding->business_website ?? config('app.url'),
            'hospital_logo' => $branding->mobile_logo_url ?? $branding->logo_url,
            
            // App Information
            'app_name' => $mobile->app_name,
            'app_short_name' => $mobile->app_short_name,
            'app_version' => $mobile->version,
            'app_description' => $mobile->app_description,
            'app_icon_url' => $mobile->app_icon_url,
            'splash_screen_url' => $mobile->splash_screen_url,
            
            // Branding Colors
            'primary_color' => $branding->primary_color ?? '#2196F3',
            'secondary_color' => $branding->secondary_color ?? '#FF9800',
            'accent_color' => $branding->accent_color ?? '#4CAF50',
            
            // Feature Flags
            'enable_video_calling' => (bool) ($jitsi->enabled ?? false),
            'jitsi_server_url' => $jitsi->server_url ?? null,
            'enable_push_notifications' => $mobile->enable_push_notifications ?? true,
            'enable_offline_mode' => $mobile->enable_offline_mode ?? true,
            'enable_video_call_recording' => false,
            'enable_video_call_chat' => true,
            'force_update' => false,
            'maintenance_mode' => $system->enable_maintenance_mode ?? false,
            'maintenance_message' => $system->maintenance_message ?? 'The app is currently under maintenance. Please try again later.',
            
            // Appointment Settings
            'max_advance_booking_days' => 30,
            'min_advance_booking_hours' => 2,
            
            // Video Call Settings
            'video_call_max_duration' => 3600,
            
            // System Configuration
            'timezone' => $system->timezone ?? 'UTC',
            'date_format' => $system->date_format ?? 'Y-m-d',
            'time_format' => $system->time_format ?? 'H:i',
            'currency' => $system->currency ?? 'USD',
            'currency_symbol' => $system->currency_symbol ?? '$',
            
            // API Configuration — deployer sets mobile_api_url in Admin → API Settings
            'api_base_url' => ApiSetting::current()->mobile_api_url,
            'api_timeout' => 30000,
            'api_retry_attempts' => 3,
            
            // App Permissions
            'permissions' => $mobile->app_permissions ?? [
                'camera' => 'Camera access for patient photos',
                'storage' => 'Storage access for documents',
                'location' => 'Location access for emergency services',
                'notifications' => 'Push notifications for appointments'
            ],
        ];
    }

    /**
     * Get comprehensive app configuration
     */
    public function getAppConfiguration()
    {
        return [
            'general' => $this->getGeneralSettings(),
            'branding' => $this->getBrandingSettings(),
            'mobile_app' => $this->getMobileAppConfiguration(),
            'system' => $this->getSystemSettings(),
            'sync' => $this->getSyncSettings(),
            'document' => $this->getDocumentSettings(),
            'features' => [
                'offline_mode' => true,
                'real_time_sync' => true,
                'multi_branch' => true,
                'audit_logs' => true,
                'backup_restore' => true,
            ],
            'api_endpoints' => [
                'base_url' => config('app.url') . '/api',
                'auth' => '/auth',
                'patients' => '/patients',
                'appointments' => '/appointments',
                'consultations' => '/consultations',
                'lab' => '/lab',
                'pharmacy' => '/pharmacy',
                'billing' => '/billing',
                'notifications' => '/notifications',
                'sync' => '/sync',
            ],
        ];
    }

    /**
     * Test email configuration
     */
    public function testEmailConfiguration($toEmail, $testMessage = null)
    {
        $emailSettings = EmailSetting::current();
        $message = $testMessage ?? 'This is a test email from ' . config('app.name', 'Hospital') . ' system.';
        
        try {
            $emailSettings->sendTestEmail($toEmail, $message);
            return [
                'success' => true,
                'message' => 'Test email sent successfully',
                'to' => $toEmail,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
                'to' => $toEmail,
            ];
        }
    }

    /**
     * Test payment configuration
     */
    public function testPaymentConfiguration($amount, $currency)
    {
        $paymentSettings = PaymentSetting::current();
        
        try {
            $result = $paymentSettings->testPayment($amount, $currency);
            return [
                'success' => true,
                'message' => 'Payment test completed successfully',
                'amount' => $amount,
                'currency' => $currency,
                'result' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment test failed: ' . $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency,
            ];
        }
    }

    /**
     * Validate settings
     */
    public function validateSettings($section, $settings)
    {
        $validationRules = $this->getValidationRules($section);
        $validator = \Validator::make($settings, $validationRules);
        
        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->toArray(),
            'section' => $section,
        ];
    }

    /**
     * Get validation rules for section
     */
    private function getValidationRules($section)
    {
        $rules = [
            'general' => [
                'platform_name' => 'required|string|max:255',
                'business_name' => 'nullable|string|max:255',
                'timezone' => 'required|string|max:50',
                'currency' => 'required|string|max:3',
            ],
            'email' => [
                'mail_driver' => 'required|string|in:smtp,sendmail,mailgun,ses',
                'mail_host' => 'required|string|max:255',
                'mail_port' => 'required|integer|min:1|max:65535',
                'mail_from_address' => 'required|email|max:255',
            ],
            'sms' => [
                'provider' => 'required|string|in:custom,twilio',
                'api_url' => 'required|url|max:255',
                'api_key' => 'required|string|max:255',
                'sender_id' => 'required|string|max:20',
            ],
            'payment' => [
                'provider' => 'required|string|in:hubtel,paystack',
                'environment' => 'required|string|in:sandbox,live',
                'public_key' => 'required|string|max:255',
                'secret_key' => 'required|string|max:255',
            ],
        ];

        return $rules[$section] ?? [];
    }

    /**
     * Backup settings
     */
    public function backupSettings()
    {
        $backup = [
            'timestamp' => now()->toISOString(),
            'version' => '1.0',
            'settings' => [
                'general' => $this->getGeneralSettings(),
                'branding' => $this->getBrandingSettings(),
                'mobile_app' => $this->getMobileAppSettings(),
                'email' => EmailSetting::current()->toArray(),
                'sms' => SmsSetting::current()->toArray(),
                'payment' => PaymentSetting::current()->toArray(),
                'document' => $this->getDocumentSettings(),
                'sync' => $this->getSyncSettings(),
                'system' => $this->getSystemSettings(),
                'id_prefixes' => IdPrefixSetting::all()->toArray(),
            ],
        ];

        // Store backup in storage
        $filename = 'settings_backup_' . now()->format('Y_m_d_H_i_s') . '.json';
        \Storage::put('backups/' . $filename, json_encode($backup, JSON_PRETTY_PRINT));

        return [
            'filename' => $filename,
            'size' => strlen(json_encode($backup)),
            'timestamp' => $backup['timestamp'],
        ];
    }

    /**
     * Restore settings from backup
     */
    public function restoreSettings($backupData)
    {
        try {
            // Validate backup structure
            if (!isset($backupData['settings']) || !is_array($backupData['settings'])) {
                throw new \Exception('Invalid backup data structure');
            }

            $settings = $backupData['settings'];

            // Restore each section
            if (isset($settings['general'])) {
                $this->updateGeneralSettings($settings['general']);
            }

            if (isset($settings['branding'])) {
                $this->updateBrandingSettings($settings['branding']);
            }

            if (isset($settings['mobile_app'])) {
                $this->updateMobileAppSettings($settings['mobile_app']);
            }

            if (isset($settings['email'])) {
                $this->updateEmailSettings($settings['email']);
            }

            if (isset($settings['sms'])) {
                $this->updateSmsSettings($settings['sms']);
            }

            if (isset($settings['payment'])) {
                $this->updatePaymentSettings($settings['payment']);
            }

            if (isset($settings['document'])) {
                $this->updateDocumentSettings($settings['document']);
            }

            if (isset($settings['sync'])) {
                $this->updateSyncSettings($settings['sync']);
            }

            return [
                'success' => true,
                'message' => 'Settings restored successfully',
                'restored_sections' => array_keys($settings),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to restore settings: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Export settings
     */
    public function exportSettings()
    {
        $export = [
            'export_info' => [
                'exported_at' => now()->toISOString(),
                'version' => '1.0',
                'platform' => config('app.name', 'Hospital'),
            ],
            'settings' => $this->getAllSettings(),
        ];

        return $export;
    }

    /**
     * Import settings
     */
    public function importSettings($file)
    {
        try {
            $content = file_get_contents($file->getPathname());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file: ' . json_last_error_msg());
            }

            if (!isset($data['settings'])) {
                throw new \Exception('Invalid settings file format');
            }

            $result = $this->restoreSettings($data);
            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to import settings: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get settings audit log
     */
    public function getSettingsAudit($filters = [])
    {
        $query = \DB::table('settings_audit_log')
            ->select('*')
            ->orderBy('created_at', 'desc');

        if (isset($filters['section'])) {
            $query->where('section', $filters['section']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 50;
        return $query->paginate($perPage);
    }

    /**
     * Log settings change
     */
    public function logSettingsChange($section, $action, $oldValues = [], $newValues = [])
    {
        \DB::table('settings_audit_log')->insert([
            'section' => $section,
            'action' => $action,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($newValues),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Apply all settings to application
     */
    public function applyAllSettings()
    {
        // Apply timezone
        SystemSetting::current()->applyTimezone();
        
        // Apply email settings
        EmailSetting::current()->applyToConfig();
        
        // Apply other settings as needed
    }
}
