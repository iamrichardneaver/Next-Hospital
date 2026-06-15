<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AppVersionController extends Controller
{
    /**
     * Check if app update is required
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkVersion(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'platform' => 'required|in:android,ios',
                'current_version_code' => 'required|integer',
                'current_version_name' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $platform = $request->input('platform');
            $currentVersionCode = (int) $request->input('current_version_code');
            $currentVersionName = $request->input('current_version_name');

            // Check for updates using the model's helper method
            $updateCheck = AppVersion::checkUpdateRequired($platform, $currentVersionCode);

            $latestVersion = $updateCheck['latest_version'];

            // Build response
            $response = [
                'success' => true,
                'requires_update' => $updateCheck['requires_update'],
                'force_update' => $updateCheck['force_update'],
                'current_version' => [
                    'code' => $currentVersionCode,
                    'name' => $currentVersionName,
                ],
            ];

            if ($latestVersion) {
                $response['latest_version'] = [
                    'code' => $latestVersion->version_code,
                    'name' => $latestVersion->version_name,
                    'build_number' => $latestVersion->build_number,
                    'release_notes' => $latestVersion->release_notes,
                ];

                // Add appropriate download/store URLs based on platform
                if ($platform === 'android') {
                    $response['download_url'] = $latestVersion->download_url;
                    $response['store_url'] = $latestVersion->play_store_url;
                } elseif ($platform === 'ios') {
                    $response['store_url'] = $latestVersion->app_store_url;
                }
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check app version',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all app versions (admin only - optional)
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $versions = AppVersion::orderBy('version_code', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $versions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch app versions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new app version (admin only - optional)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'platform' => 'required|in:android,ios,both',
                'version_code' => 'required|integer',
                'version_name' => 'required|string',
                'build_number' => 'required|integer',
                'is_force_update' => 'boolean',
                'min_supported_version' => 'nullable|integer',
                'download_url' => 'nullable|url|max:500',
                'play_store_url' => 'nullable|url|max:500',
                'app_store_url' => 'nullable|url|max:500',
                'release_notes' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $version = AppVersion::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'App version created successfully',
                'data' => $version
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create app version',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing app version (admin only - optional)
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $version = AppVersion::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'platform' => 'sometimes|in:android,ios,both',
                'version_code' => 'sometimes|integer',
                'version_name' => 'sometimes|string',
                'build_number' => 'sometimes|integer',
                'is_force_update' => 'boolean',
                'min_supported_version' => 'nullable|integer',
                'download_url' => 'nullable|url|max:500',
                'play_store_url' => 'nullable|url|max:500',
                'app_store_url' => 'nullable|url|max:500',
                'release_notes' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $version->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'App version updated successfully',
                'data' => $version
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update app version',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an app version (admin only - optional)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $version = AppVersion::findOrFail($id);
            $version->delete();

            return response()->json([
                'success' => true,
                'message' => 'App version deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete app version',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
