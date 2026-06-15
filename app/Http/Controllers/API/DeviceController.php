<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'fcm_token' => 'required|string',
            'platform' => 'required|in:android,ios,mobile',
            'app_version' => 'nullable|string|max:50',
            'device_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $platform = $request->input('platform');
        $osVersion = $request->input('os_version', $platform);

        $device = Device::updateOrCreate(
            ['device_id' => $request->input('device_id')],
            [
                'user_id' => $request->user()->id,
                'device_name' => $request->input('device_name'),
                'platform' => 'mobile',
                'fcm_token' => $request->input('fcm_token'),
                'app_version' => $request->input('app_version'),
                'os_version' => $osVersion,
                'last_seen_at' => now(),
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'data' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'platform' => $device->os_version ?? $platform,
                'app_version' => $device->app_version,
            ],
        ]);
    }

    public function unregister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        Device::query()
            ->where('device_id', $request->input('device_id'))
            ->where('user_id', $request->user()->id)
            ->update([
                'fcm_token' => null,
                'is_active' => false,
                'last_seen_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Device unregistered successfully',
        ]);
    }
}
