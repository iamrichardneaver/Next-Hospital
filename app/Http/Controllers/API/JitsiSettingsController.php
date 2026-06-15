<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\JitsiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class JitsiSettingsController extends Controller
{
    /**
     * Get Jitsi settings
     */
    public function getSettings()
    {
        try {
            $settings = JitsiSetting::current();
            return response()->json([
                'success' => true,
                'data' => $settings->getClientConfig(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get Jitsi settings', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get Jitsi settings',
            ], 500);
        }
    }

    /**
     * Update Jitsi settings
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'boolean',
            'jitsi_server_url' => 'required|url',
            'app_id' => 'nullable|string',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'default_domain' => 'nullable|string',
            'jwt_app_id' => 'nullable|string',
            'jwt_app_secret' => 'nullable|string',
            'jwt_kid' => 'nullable|string',
            'jwt_issuer' => 'nullable|string',
            'jwt_audience' => 'nullable|string',
            'meeting_duration_minutes' => 'integer|min:1|max:480',
            'allow_guests' => 'boolean',
            'require_password' => 'boolean',
            'auto_record' => 'boolean',
            'live_streaming_enabled' => 'boolean',
            'screen_sharing_enabled' => 'boolean',
            'chat_enabled' => 'boolean',
            'lobby_enabled' => 'boolean',
            'moderator_only_access' => 'boolean',
            'default_features' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $settings = JitsiSetting::updateSettings($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Jitsi settings updated successfully',
                'data' => $settings->getClientConfig(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update Jitsi settings', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Jitsi settings',
            ], 500);
        }
    }

    /**
     * Test Jitsi connection
     */
    public function testConnection()
    {
        try {
            $settings = JitsiSetting::current();
            
            if (!$settings->enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jitsi Meet is not enabled',
                ]);
            }

            // Test connection to Jitsi server
            $serverUrl = rtrim($settings->jitsi_server_url, '/');
            $testUrl = $serverUrl . '/config.js';
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET',
                ],
            ]);
            
            $result = @file_get_contents($testUrl, false, $context);
            
            if ($result === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot connect to Jitsi server',
                    'server_url' => $serverUrl,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Connection to Jitsi server successful',
                'server_url' => $serverUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to test Jitsi connection', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to test connection: ' . $e->getMessage(),
            ], 500);
        }
    }
}
