<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AppVersionSettingsController extends Controller
{
    /**
     * Display app versions management page
     */
    public function index()
    {
        $versions = AppVersion::orderBy('version_code', 'desc')->get();
        
        $statistics = [
            'total_versions' => AppVersion::count(),
            'active_versions' => AppVersion::where('is_active', true)->count(),
            'force_updates' => AppVersion::where('is_force_update', true)->where('is_active', true)->count(),
            'android_versions' => AppVersion::whereIn('platform', ['android', 'both'])->where('is_active', true)->count(),
            'ios_versions' => AppVersion::whereIn('platform', ['ios', 'both'])->where('is_active', true)->count(),
        ];
        
        return view('settings.app-versions', compact('versions', 'statistics'));
    }

    /**
     * Store new app version
     */
    public function store(Request $request)
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
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $version = AppVersion::create($request->all());

            return redirect()->route('settings.app-versions')
                ->with('success', 'App version created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating app version: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create app version. Please try again.');
        }
    }

    /**
     * Update app version
     */
    public function update(Request $request, AppVersion $version)
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
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $version->update($validator->validated());

            return redirect()->route('settings.app-versions')
                ->with('success', 'App version updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating app version: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'version_id' => $version->id,
                'request_data' => $request->except(['_token', '_method']),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update app version. Please try again.');
        }
    }

    /**
     * Delete app version
     */
    public function destroy(AppVersion $version)
    {
        try {
            $version->delete();

            return redirect()->route('settings.app-versions')
                ->with('success', 'App version deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting app version: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'version_id' => $version->id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->with('error', 'Failed to delete app version. Please try again.');
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(AppVersion $version)
    {
        $version->update(['is_active' => !$version->is_active]);

        return redirect()->route('settings.app-versions')
            ->with('success', 'Version status updated successfully!');
    }

    /**
     * Toggle force update
     */
    public function toggleForceUpdate(AppVersion $version)
    {
        $version->update(['is_force_update' => !$version->is_force_update]);

        return redirect()->route('settings.app-versions')
            ->with('success', 'Force update setting updated successfully!');
    }
}
