<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JitsiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JitsiSettingsController extends Controller
{
    public function __construct()
    {
        // Middleware is applied in routes
    }

    /**
     * Display Jitsi settings page
     */
    public function index()
    {
        $settings = JitsiSetting::current();
        return view('settings.jitsi', compact('settings'));
    }

    /**
     * Update Jitsi settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'boolean',
            'server_url' => 'required|url',
            'app_id' => 'nullable|string|max:255',
            'app_secret' => 'nullable|string|max:255',
            'jwt_secret' => 'nullable|string|max:255',
            'jwt_algorithm' => 'required|in:HS256,HS384,HS512,RS256,RS384,RS512',
            'meeting_duration_minutes' => 'required|integer|min:15|max:480',
            'recording_enabled' => 'boolean',
            'chat_enabled' => 'boolean',
            'screen_sharing_enabled' => 'boolean',
            'file_sharing_enabled' => 'boolean',
            'live_streaming_enabled' => 'boolean',
            'transcription_enabled' => 'boolean',
            'waiting_room_enabled' => 'boolean',
            'mute_on_entry' => 'boolean',
            'require_display_name' => 'boolean',
            'require_password' => 'boolean',
            'enable_knocking' => 'boolean',
            'enable_lobby' => 'boolean',
            'max_participants' => 'required|integer|min:2|max:1000',
            'default_language' => 'required|string|max:10',
            'default_timezone' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $settings = JitsiSetting::updateSettings($request->all());

        session()->flash('success', 'Jitsi settings updated successfully.');
        return redirect()->route('settings.jitsi');
    }

    /**
     * Test Jitsi connection
     */
    public function testConnection()
    {
        try {
            $settings = JitsiSetting::current();
            
            // Simple test to check if server URL is accessible
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $settings->server_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 400) {
                return response()->json([
                    'success' => true,
                    'message' => 'Jitsi server is accessible'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Jitsi server returned HTTP ' . $httpCode
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to Jitsi server: ' . $e->getMessage()
            ]);
        }
    }
}
