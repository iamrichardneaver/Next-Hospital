<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Services\IdPrefixService;
use App\Services\FileStorageService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    protected $settingsService;
    protected $idPrefixService;
    protected $fileStorageService;

    public function __construct(SettingsService $settingsService, IdPrefixService $idPrefixService, FileStorageService $fileStorageService)
    {
        $this->settingsService = $settingsService;
        $this->idPrefixService = $idPrefixService;
        $this->fileStorageService = $fileStorageService;
    }

    /**
     * Get all settings
     */
    public function index(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getAllSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public settings for frontend
     */
    public function public(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getPublicSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch public settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get API settings
     */
    public function getApiSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getApiSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch API settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update API settings
     */
    public function updateApiSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'frontend_api_url' => 'required|url',
                'mobile_api_url' => 'required|url',
                'websocket_url' => 'nullable|string',
                'api_version' => 'required|string',
                'api_timeout' => 'required|integer|min:5|max:300',
                'max_retry_attempts' => 'required|integer|min:1|max:10',
                'enable_api_caching' => 'boolean',
                'api_cache_ttl' => 'required|integer|min:60|max:3600',
                'enable_rate_limiting' => 'boolean',
                'rate_limit_per_minute' => 'required|integer|min:1|max:1000',
                'allowed_origins' => 'nullable|array',
                'enable_api_logging' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $settings = $this->settingsService->updateApiSettings($request->all());
            
            // Send push notification to all mobile devices about configuration change
            $this->notifyMobileDevicesAboutConfigChange($settings);
            
            return response()->json([
                'success' => true,
                'message' => 'API settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update API settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get frontend API configuration
     */
    public function getFrontendApiConfig(): JsonResponse
    {
        try {
            $config = $this->settingsService->getFrontendApiConfig();
            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch frontend API configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get mobile API configuration
     */
    public function getMobileApiConfig(): JsonResponse
    {
        try {
            $config = $this->settingsService->getMobileApiConfig();
            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch mobile API configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notify mobile devices about configuration changes
     */
    private function notifyMobileDevicesAboutConfigChange($settings): void
    {
        try {
            $userIds = \App\Models\Device::query()
                ->where('platform', 'mobile')
                ->where('is_active', true)
                ->whereNotNull('user_id')
                ->whereNotNull('fcm_token')
                ->pluck('user_id')
                ->unique()
                ->values()
                ->all();

            if (empty($userIds)) {
                return;
            }

            app(PushNotificationService::class)->sendToUsers(
                $userIds,
                'Configuration Updated',
                'API configuration has been updated. The app will automatically refresh.',
                [
                    'type' => 'config_update',
                    'screen' => 'Dashboard',
                    'api_url' => (string) $settings->mobile_api_url,
                    'websocket_url' => (string) ($settings->websocket_url ?? ''),
                    'api_version' => (string) $settings->api_version,
                    'timestamp' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Failed to notify mobile devices about config change: ' . $e->getMessage());
        }
    }

    /**
     * Update branding settings
     */
    public function updateBranding(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform_name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'business_address' => 'nullable|string',
            'business_phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email|max:255',
            'business_website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
            'mobile_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'accent_color' => 'nullable|string|max:7',
            'custom_css' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->except(['logo', 'favicon', 'mobile_logo']);

            // Handle file uploads with cross-platform support
            if ($request->hasFile('logo')) {
                $logoFile = $request->file('logo');
                $validation = $this->fileStorageService->validateFile($logoFile, 'images');
                
                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Logo validation failed',
                        'errors' => $validation['errors']
                    ], 422);
                }

                $logoResult = $this->fileStorageService->storeImage($logoFile, 'branding', ['original' => null]);
                $data['logo_path'] = $logoResult['original']['relative_path'];
            }

            if ($request->hasFile('favicon')) {
                $faviconFile = $request->file('favicon');
                $validation = $this->fileStorageService->validateFile($faviconFile, 'images');
                
                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Favicon validation failed',
                        'errors' => $validation['errors']
                    ], 422);
                }

                $faviconResult = $this->fileStorageService->storeImage($faviconFile, 'branding', ['original' => null]);
                $data['favicon_path'] = $faviconResult['original']['relative_path'];
            }

            if ($request->hasFile('mobile_logo')) {
                $mobileLogoFile = $request->file('mobile_logo');
                $validation = $this->fileStorageService->validateFile($mobileLogoFile, 'images');
                
                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile logo validation failed',
                        'errors' => $validation['errors']
                    ], 422);
                }

                $mobileLogoResult = $this->fileStorageService->storeImage($mobileLogoFile, 'branding', ['original' => null]);
                $data['mobile_logo_path'] = $mobileLogoResult['original']['relative_path'];
            }

            $this->settingsService->updateBrandingSettings($data);

            return response()->json([
                'success' => true,
                'message' => 'Branding settings updated successfully',
                'data' => $this->settingsService->getBrandingSettings()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branding settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update system settings
     */
    public function updateSystem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:20',
            'currency' => 'required|string|max:3',
            'currency_symbol' => 'required|string|max:5',
            'session_timeout' => 'required|integer|min:1',
            'password_min_length' => 'required|integer|min:6',
            'require_password_change' => 'boolean',
            'password_change_days' => 'required|integer|min:1',
            'enable_audit_logs' => 'boolean',
            'audit_log_retention_days' => 'required|integer|min:1',
            'enable_maintenance_mode' => 'boolean',
            'maintenance_message' => 'nullable|string',
            'registration_fee' => 'nullable|numeric|min:0',
            'registration_fee_apply_to_new_patients' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = $this->settingsService->updateSystemSettings($request->all());

            return response()->json([
                'success' => true,
                'message' => 'System settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update system settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update mobile app settings
     */
    public function updateMobileApp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'app_short_name' => 'required|string|max:50',
            'app_icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'splash_screen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'package_name' => 'required|string|max:255',
            'version' => 'required|string|max:20',
            'app_description' => 'nullable|string',
            'app_permissions' => 'nullable|array',
            'enable_offline_mode' => 'boolean',
            'enable_push_notifications' => 'boolean',
            'enable_biometric_auth' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->except(['app_icon', 'splash_screen', 'app_logo']);

            // Handle file uploads with cross-platform support
            if ($request->hasFile('app_icon')) {
                $iconFile = $request->file('app_icon');
                $validation = $this->fileStorageService->validateFile($iconFile, 'images');
                
                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'App icon validation failed',
                        'errors' => $validation['errors']
                    ], 422);
                }

                $iconResult = $this->fileStorageService->storeImage($iconFile, 'mobile-app', ['original' => null]);
                $data['app_icon_path'] = $iconResult['original']['relative_path'];
            }

            if ($request->hasFile('splash_screen')) {
                $splashFile = $request->file('splash_screen');
                $validation = $this->fileStorageService->validateFile($splashFile, 'images');
                
                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Splash screen validation failed',
                        'errors' => $validation['errors']
                    ], 422);
                }

                $splashResult = $this->fileStorageService->storeImage($splashFile, 'mobile-app', ['original' => null]);
                $data['splash_screen_path'] = $splashResult['original']['relative_path'];
            }

            if ($request->hasFile('app_logo')) {
                $logoFile = $request->file('app_logo');
                $validation = $this->fileStorageService->validateFile($logoFile, 'images');
                
                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'App logo validation failed',
                        'errors' => $validation['errors']
                    ], 422);
                }

                $logoResult = $this->fileStorageService->storeImage($logoFile, 'mobile-app', ['original' => null]);
                $data['app_logo_path'] = $logoResult['original']['relative_path'];
            }

            $settings = $this->settingsService->updateMobileAppSettings($data);

            return response()->json([
                'success' => true,
                'message' => 'Mobile app settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update mobile app settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update email settings
     */
    public function updateEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mail_driver' => 'required|string|in:smtp,sendmail,mailgun,ses',
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|in:tls,ssl',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
            'mail_verify_peer' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = $this->settingsService->updateEmailSettings($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Email settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update email settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update SMS settings
     */
    public function updateSms(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:custom,twilio',
            'api_url' => 'required|url|max:255',
            'api_key' => 'required|string|max:255',
            'api_secret' => 'nullable|string|max:255',
            'sender_id' => 'required|string|max:20',
            'custom_headers' => 'nullable|array',
            'request_body_template' => 'nullable|array',
            'response_success_field' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = $this->settingsService->updateSmsSettings($request->all());

            return response()->json([
                'success' => true,
                'message' => 'SMS settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update SMS settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update payment settings
     */
    public function updatePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:hubtel,paystack',
            'environment' => 'required|string|in:sandbox,live',
            'public_key' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
            'merchant_id' => 'nullable|string|max:255',
            'webhook_urls' => 'nullable|array',
            'supported_currencies' => 'nullable|array',
            'supported_payment_methods' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = $this->settingsService->updatePaymentSettings($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Payment settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all ID prefix settings
     */
    public function getIdPrefixSettings(): JsonResponse
    {
        try {
            $settings = $this->idPrefixService->getAllSettings();
            $entityTypes = $this->idPrefixService->getAvailableEntityTypes();
            $patternExamples = $this->idPrefixService->getPatternExamples();

            return response()->json([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                    'entity_types' => $entityTypes,
                    'pattern_examples' => $patternExamples
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ID prefix settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update ID prefix settings
     */
    public function updateIdPrefix(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string|max:50',
            'company_prefix' => 'required|string|max:10',
            'module_prefix' => 'required|string|max:10',
            'pattern' => 'required|string|max:200',
            'sequence_length' => 'required|integer|min:1|max:10',
            'include_year' => 'boolean',
            'include_month' => 'boolean',
            'include_day' => 'boolean',
            'separator' => 'nullable|string|max:5',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate pattern format
            $patternValidation = $this->idPrefixService->validatePattern($request->pattern);
            if (!$patternValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $patternValidation['message']
                ], 422);
            }

            $settings = $this->idPrefixService->updateSetting($request->entity_type, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'ID prefix settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new ID prefix setting
     */
    public function createIdPrefix(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string|max:50|unique:id_prefix_settings,entity_type',
            'company_prefix' => 'required|string|max:10',
            'module_prefix' => 'required|string|max:10',
            'pattern' => 'required|string|max:200',
            'sequence_length' => 'required|integer|min:1|max:10',
            'include_year' => 'boolean',
            'include_month' => 'boolean',
            'include_day' => 'boolean',
            'separator' => 'nullable|string|max:5',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate pattern format
            $patternValidation = $this->idPrefixService->validatePattern($request->pattern);
            if (!$patternValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $patternValidation['message']
                ], 422);
            }

            $settings = $this->idPrefixService->getOrCreateSetting($request->entity_type, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'ID prefix setting created successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate test ID
     */
    public function generateTestId(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->idPrefixService->testIdGeneration($request->entity_type);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate test ID',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset sequence for entity type
     */
    public function resetSequence(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = $this->idPrefixService->resetSequence($request->entity_type);

            return response()->json([
                'success' => true,
                'message' => 'Sequence reset successfully',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lock setting for entity type
     */
    public function lockSetting(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = $this->idPrefixService->lockSetting($request->entity_type);

            return response()->json([
                'success' => true,
                'message' => 'Setting locked successfully',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlock setting for entity type
     */
    public function unlockSetting(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = $this->idPrefixService->unlockSetting($request->entity_type);

            return response()->json([
                'success' => true,
                'message' => 'Setting unlocked successfully',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate pattern format
     */
    public function validatePattern(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pattern' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->idPrefixService->validatePattern($request->pattern);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate pattern',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test SMS sending
     */
    public function testSms(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:20',
            'message' => 'required|string|max:160',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->settingsService->sendSms($request->phone_number, $request->message);

            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get document settings
     */
    public function getDocumentSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getDocumentSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch document settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update document settings
     */
    public function updateDocumentSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'header_template' => 'nullable|string',
            'footer_template' => 'nullable|string',
            'header_height' => 'required|integer|min:0|max:200',
            'footer_height' => 'required|integer|min:0|max:200',
            'margin_top' => 'required|integer|min:0|max:100',
            'margin_bottom' => 'required|integer|min:0|max:100',
            'margin_left' => 'required|integer|min:0|max:100',
            'margin_right' => 'required|integer|min:0|max:100',
            'font_family' => 'required|string|max:100',
            'font_size' => 'required|integer|min:8|max:72',
            'logo_position' => 'required|string|in:left,center,right',
            'show_company_info' => 'boolean',
            'show_contact_info' => 'boolean',
            'show_website' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = $this->settingsService->updateDocumentSettings($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Document settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync settings
     */
    public function getSyncSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getSyncSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sync settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update sync settings
     */
    public function updateSyncSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enable_offline_mode' => 'boolean',
            'sync_interval' => 'required|integer|min:5|max:3600',
            'max_offline_days' => 'required|integer|min:1|max:30',
            'auto_sync_on_connect' => 'boolean',
            'sync_on_startup' => 'boolean',
            'conflict_resolution' => 'required|string|in:server_wins,client_wins,manual',
            'enable_real_time_sync' => 'boolean',
            'websocket_url' => 'nullable|url|max:255',
            'enable_compression' => 'boolean',
            'max_file_size' => 'required|integer|min:1024|max:10485760',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = $this->settingsService->updateSyncSettings($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Sync settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sync settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get general settings
     */
    public function getGeneralSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getGeneralSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch general settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update general settings
     */
    public function updateGeneralSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform_name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'business_address' => 'nullable|string',
            'business_phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email|max:255',
            'business_website' => 'nullable|url|max:255',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:20',
            'currency' => 'required|string|max:3',
            'currency_symbol' => 'required|string|max:5',
            'language' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = $this->settingsService->updateGeneralSettings($request->all());
            return response()->json([
                'success' => true,
                'message' => 'General settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update general settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get mobile app configuration
     */
    public function getMobileAppConfig(): JsonResponse
    {
        try {
            $config = $this->settingsService->getMobileAppConfiguration();
            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch mobile app configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get ecommerce/pharmacy settings (tax rate, delivery fee)
     */
    public function getEcommerceSettings(): JsonResponse
    {
        try {
            $system = \App\Models\SystemSetting::current();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'tax_rate' => (float)($system->tax_rate ?? 0.15),
                    'delivery_fee' => (float)($system->delivery_fee ?? 5.00),
                    'currency' => $system->currency ?? 'GHS',
                    'currency_symbol' => $system->currency_symbol ?? 'GH₵',
                    'min_order_for_free_delivery' => 100.00,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ecommerce settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive app configuration
     */
    public function getAppConfiguration(): JsonResponse
    {
        try {
            $config = $this->settingsService->getAppConfiguration();
            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch app configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to_email' => 'required|email|max:255',
            'test_message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->settingsService->testEmailConfiguration($request->to_email, $request->test_message);
            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test payment configuration
     */
    public function testPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:1000',
            'currency' => 'required|string|max:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->settingsService->testPaymentConfiguration($request->amount, $request->currency);
            return response()->json([
                'success' => true,
                'message' => 'Payment test completed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test payment configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate settings
     */
    public function validateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'section' => 'required|string|in:general,branding,mobile_app,email,sms,payment,documents,sync',
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->settingsService->validateSettings($request->section, $request->settings);
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Backup settings
     */
    public function backupSettings(): JsonResponse
    {
        try {
            $backup = $this->settingsService->backupSettings();
            return response()->json([
                'success' => true,
                'message' => 'Settings backed up successfully',
                'data' => $backup
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to backup settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore settings
     */
    public function restoreSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'backup_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->settingsService->restoreSettings($request->backup_data);
            return response()->json([
                'success' => true,
                'message' => 'Settings restored successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export settings
     */
    public function exportSettings(): JsonResponse
    {
        try {
            $export = $this->settingsService->exportSettings();
            return response()->json([
                'success' => true,
                'data' => $export
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import settings
     */
    public function importSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings_file' => 'required|file|mimes:json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->settingsService->importSettings($request->file('settings_file'));
            return response()->json([
                'success' => true,
                'message' => 'Settings imported successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get settings audit log
     */
    public function getSettingsAudit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'section' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $audit = $this->settingsService->getSettingsAudit($request->all());
            return response()->json([
                'success' => true,
                'data' => $audit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings audit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get branding settings
     */
    public function getBrandingSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getBrandingSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branding settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email settings
     */
    public function getEmailSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getEmailSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch email settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SMS settings
     */
    public function getSmsSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getSmsSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SMS settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment settings
     */
    public function getPaymentSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getPaymentSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get mobile app settings
     */
    public function getMobileAppSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getMobileAppSettings();
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch mobile app settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get maintenance mode status
     */
    public function maintenanceStatus(): JsonResponse
    {
        try {
            $isMaintenance = $this->settingsService->isMaintenanceMode();
            $message = $isMaintenance ? $this->settingsService->getMaintenanceMessage() : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'is_maintenance_mode' => $isMaintenance,
                    'maintenance_message' => $message
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get maintenance status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
