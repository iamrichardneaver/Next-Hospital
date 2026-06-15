<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BrandingSetting;
use App\Models\Setting;
use App\Services\BrandingService;
use App\Services\CrossPlatformService;
use App\Services\DataCleanupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        BrandingSetting::repairStoredPaths();
        $this->ensurePublicStorageLink();

        // Get all settings grouped by 'group' field
        $settings = Setting::all()->groupBy('group');
        
        // Ensure default groups exist even if empty
        $defaultGroups = ['general', 'billing', 'lab', 'email', 'notifications'];
        foreach ($defaultGroups as $group) {
            if (!isset($settings[$group])) {
                $settings[$group] = collect();
            }
        }
        
        // Get ID Prefix settings
        $idPrefixes = \App\Models\IdPrefixSetting::orderBy('entity_type')->get();
        
        // Get Branding settings (fresh after any path repair)
        $branding = BrandingSetting::current()->fresh();
        
        // Get system settings (for registration fee, etc.)
        $systemSettings = \App\Models\SystemSetting::current();
        
        // Data cleanup stats (manage_data_cleanup permission only)
        $cleanableModules = null;
        $systemStats = null;
        if (auth()->user()->can('manage_data_cleanup')) {
            $dataCleanupService = new DataCleanupService();
            $cleanableModules = $dataCleanupService->getCleanableModules();
            $systemStats = $dataCleanupService->getSystemStats();
        }
        
        return view('settings.index', compact('settings', 'idPrefixes', 'branding', 'systemSettings', 'cleanableModules', 'systemStats'));
    }
    
    public function update(Request $request)
    {
        try {
            $request->validate([
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
                'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg,ico|max:1024',
            ]);

            $uploadErrors = $this->collectUploadErrors($request);
            if (!empty($uploadErrors)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', implode(' ', $uploadErrors));
            }

            $logoUpdated = false;
            $faviconUpdated = false;

            $branding = BrandingSetting::current();

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('branding', 'public');
                $this->deleteStoredPublicFile($branding->getRawOriginal('logo_path'));
                $request->merge(['logo_path' => CrossPlatformService::normalizeStorageRelativePath($logoPath)]);
                $logoUpdated = true;
            }

            // Handle favicon upload
            if ($request->hasFile('favicon')) {
                $faviconPath = $request->file('favicon')->store('branding', 'public');
                $this->deleteStoredPublicFile($branding->getRawOriginal('favicon_path'));
                $request->merge(['favicon_path' => CrossPlatformService::normalizeStorageRelativePath($faviconPath)]);
                $faviconUpdated = true;
            }

            // Update branding settings
            if ($request->has('platform_name') || $request->has('business_name') || $logoUpdated || $faviconUpdated) {
                $branding->update(BrandingSetting::normalizeFilePathsInData($request->only([
                    'platform_name', 'business_name', 'business_address',
                    'business_phone', 'business_email', 'business_website',
                    'logo_path', 'favicon_path', 'primary_color', 'secondary_color', 'accent_color',
                ])));

                $branding->touch();
                BrandingService::clearCache();
            }
            
            // Update system settings (registration fee - one-time fee for new patients)
            if ($request->has('registration_fee') || $request->has('registration_fee_apply_to_new_patients')) {
                \App\Models\SystemSetting::updateSettings([
                    'registration_fee' => $request->input('registration_fee', 0),
                    'registration_fee_apply_to_new_patients' => $request->boolean('registration_fee_apply_to_new_patients', true),
                ]);
            }

            // Update general settings
            foreach ($request->except('_token', 'logo', 'favicon', 'platform_name', 'business_name', 'business_address', 'business_phone', 'business_email', 'business_website', 'logo_path', 'favicon_path', 'primary_color', 'secondary_color', 'accent_color', 'registration_fee', 'registration_fee_apply_to_new_patients') as $key => $value) {
                // Determine the group based on the key
                $group = $this->determineGroup($key);
                
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'group' => $group,
                        'type' => 'string',
                        'is_public' => false
                    ]
                );
            }
            
            if ($logoUpdated || $faviconUpdated) {
                Artisan::call('view:clear');
            }

            $successMessage = 'Settings updated successfully!';
            if ($logoUpdated && $faviconUpdated) {
                $successMessage = 'Settings saved. Logo and favicon updated — changes are visible immediately.';
            } elseif ($logoUpdated) {
                $successMessage = 'Settings saved. Logo updated — changes are visible immediately.';
            } elseif ($faviconUpdated) {
                $successMessage = 'Settings saved. Favicon updated — changes are visible immediately.';
            }

            return redirect()->route('settings.index')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            \Log::error('Settings update failed', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }
    
    /**
     * Ensure public/storage symlink exists (optional when branding.file route is active).
     */
    private function ensurePublicStorageLink(): void
    {
        $link = public_path('storage');
        if (file_exists($link)) {
            return;
        }

        try {
            Artisan::call('storage:link');
        } catch (\Throwable $e) {
            \Log::warning('Could not create storage symlink', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return list<string>
     */
    private function collectUploadErrors(Request $request): array
    {
        $errors = [];

        foreach (['logo', 'favicon'] as $field) {
            if (!$request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            if ($file->isValid()) {
                continue;
            }

            $errors[] = match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => ucfirst($field) . ' exceeds the maximum upload size allowed by the server.',
                UPLOAD_ERR_PARTIAL => ucfirst($field) . ' was only partially uploaded. Please try again.',
                default => ucfirst($field) . ' upload failed. Please try again.',
            };
        }

        return $errors;
    }

    private function deleteStoredPublicFile(?string $path): void
    {
        $relativePath = CrossPlatformService::normalizeStorageRelativePath($path);
        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    /**
     * Determine the group for a setting key
     */
    private function determineGroup($key)
    {
        $groupMap = [
            'hospital_name' => 'general',
            'system_email' => 'general',
            'system_phone' => 'general',
            'address' => 'general',
            'timezone' => 'general',
            'currency' => 'billing',
            'tax_rate' => 'billing',
            'default_lab_tat' => 'lab',
            'smtp_host' => 'email',
            'smtp_port' => 'email',
            'notification_enabled' => 'notifications',
        ];
        
        return $groupMap[$key] ?? 'general';
    }
    
    /**
     * Show clean data page (super admin only)
     */
    public function cleanData()
    {
        // Check permission instead of role (flexible RBAC)
        if (!auth()->user()->can('manage_data_cleanup')) {
            abort(403, 'Unauthorized access. You need the manage_data_cleanup permission to clean system data.');
        }
        
        $dataCleanupService = new DataCleanupService();
        $cleanableModules = $dataCleanupService->getCleanableModules();
        $systemStats = $dataCleanupService->getSystemStats();
        $protectedTables = $dataCleanupService->getProtectedTables();
        $confirmationPhrase = config('data_cleanup.confirmation_phrase', 'DELETE ALL DATA');

        return view('settings.clean-data', compact(
            'cleanableModules',
            'systemStats',
            'protectedTables',
            'confirmationPhrase'
        ));
    }
    
    /**
     * Process clean data request (super admin only)
     */
    public function processCleanData(Request $request)
    {
        // Check permission instead of role (flexible RBAC)
        if (!auth()->user()->can('manage_data_cleanup')) {
            abort(403, 'Unauthorized access. You need the manage_data_cleanup permission to clean system data.');
        }
        
        $confirmationPhrase = config('data_cleanup.confirmation_phrase', 'DELETE ALL DATA');

        $request->validate([
            'modules' => 'required|array|min:1',
            'modules.*' => 'string',
            'confirmation_text' => 'required|string|in:' . $confirmationPhrase,
        ], [
            'modules.required' => 'Please select at least one module to clean.',
            'confirmation_text.required' => 'Please type the confirmation text exactly as shown.',
            'confirmation_text.in' => 'Confirmation text must be exactly "' . $confirmationPhrase . '".',
        ]);
        
        // Validate that selected modules are valid
        $dataCleanupService = new DataCleanupService();
        $validModules = array_keys($dataCleanupService->getCleanableModules());
        $invalidModules = array_diff($request->modules, $validModules);
        
        if (!empty($invalidModules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid modules selected: ' . implode(', ', $invalidModules));
        }
        
        try {
            $results = $dataCleanupService->cleanModuleData(
                $request->modules, 
                auth()->id()
            );
            
            $totalRecordsCleaned = collect($results)->sum('records_cleaned');
            $modulesCleaned = count($results);
            
            return redirect()->route('settings.index')
                ->with('success', "Successfully cleaned data from {$modulesCleaned} modules. Total records cleaned: {$totalRecordsCleaned}");
                
        } catch (\Exception $e) {
            \Log::error('Data cleanup failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'modules' => $request->modules,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Provide more specific error messages
            $errorMessage = 'Failed to clean data: ' . $e->getMessage();
            
            // Check for common error types
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $errorMessage = 'Failed to clean data due to foreign key constraints. Some tables have dependencies that prevent deletion. Please try cleaning modules individually or contact system administrator.';
            } elseif (strpos($e->getMessage(), 'permission denied') !== false) {
                $errorMessage = 'Permission denied. You may not have sufficient database privileges to perform this operation.';
            } elseif (strpos($e->getMessage(), 'table') !== false && strpos($e->getMessage(), 'doesn\'t exist') !== false) {
                $errorMessage = 'One or more tables referenced in the cleanup process do not exist. This may indicate a database schema issue.';
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }
    
    /**
     * Clean individual module data
     */
    public function cleanIndividualModule(Request $request)
    {
        // Check permission instead of role (flexible RBAC)
        if (!auth()->user()->can('manage_data_cleanup')) {
            return redirect()->back()->with('error', 'Unauthorized access. You need the manage_data_cleanup permission.');
        }
        
        $confirmationPhrase = config('data_cleanup.confirmation_phrase', 'DELETE ALL DATA');

        $request->validate([
            'module' => 'required|string',
            'confirmation_text' => 'required|in:' . $confirmationPhrase,
        ], [
            'confirmation_text.in' => 'Confirmation text must be exactly "' . $confirmationPhrase . '".',
        ]);
        
        try {
            $dataCleanupService = new DataCleanupService();
            $results = $dataCleanupService->cleanModuleData([$request->module], auth()->id());
            
            if (isset($results[$request->module])) {
                $result = $results[$request->module];
                $totalRecordsCleaned = $result['records_cleaned'];
                $tablesCleaned = count($result['cleaned_tables']);
                
                return redirect()->route('settings.clean-data')
                    ->with('success', "Successfully cleaned {$tablesCleaned} tables from {$result['name']}. Records cleaned: {$totalRecordsCleaned}");
            } else {
                return redirect()->back()->with('error', 'Module not found or no data to clean.');
            }
                
        } catch (\Exception $e) {
            \Log::error('Individual module cleanup failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'module' => $request->module
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to clean module data: ' . $e->getMessage());
        }
    }

    /**
     * Get module data statistics via AJAX
     */
    public function getModuleStats(Request $request)
    {
        if (!auth()->user()->can('manage_data_cleanup')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $dataCleanupService = new DataCleanupService();
            $modules = $dataCleanupService->getCleanableModules();
            $systemStats = $dataCleanupService->getSystemStats();

            return response()->json([
                'modules' => $modules,
                'system_stats' => $systemStats,
                'protected_tables_count' => count($dataCleanupService->getProtectedTables()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Preview cleanup impact for selected modules via AJAX
     */
    public function previewCleanData(Request $request)
    {
        if (!auth()->user()->can('manage_data_cleanup')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'modules' => 'required|array|min:1',
            'modules.*' => 'string',
        ]);

        try {
            $dataCleanupService = new DataCleanupService();
            $validModules = array_keys($dataCleanupService->getCleanableModules());
            $selected = array_values(array_intersect($request->modules, $validModules));

            if (empty($selected)) {
                return response()->json(['error' => 'No valid modules selected.'], 422);
            }

            $preview = $dataCleanupService->getCleanupPreview($selected);

            return response()->json([
                'preview' => $preview,
                'confirmation_phrase' => config('data_cleanup.confirmation_phrase', 'DELETE ALL DATA'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
