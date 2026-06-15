<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BrandingSetting;
use App\Models\SystemSetting;
use App\Models\MobileAppSetting;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class EmergencyContactController extends Controller
{
    /**
     * Get emergency contact information for mobile app
     */
    public function index(Request $request)
    {
        try {
            $branchId = $request->get('branch_id');
            $branch = null;
            
            // Get branch-specific information if branch_id is provided
            if ($branchId) {
                $branch = Branch::find($branchId);
            }
            
            // Get system settings
            $brandingSettings = BrandingSetting::current();
            $systemSettings = SystemSetting::current();
            $mobileSettings = MobileAppSetting::current();
            
            // Build emergency contact information
            $emergencyContacts = [
                'hospital_name' => $brandingSettings->business_name ?? $mobileSettings->app_name ?? config('app.name', 'Hospital'),
                'hospital_phone' => $branch ? $branch->phone : ($brandingSettings->business_phone ?? '+233-123-456-789'),
                'hospital_address' => $branch ? $branch->address : ($brandingSettings->business_address ?? '123 Hospital Street, Accra, Ghana'),
                'hospital_email' => $brandingSettings->business_email ?? 'info@nexthospital.com',
                'hospital_website' => $brandingSettings->business_website ?? config('app.url'),
                'emergency_number' => '193', // Ghana emergency number
                'ambulance_number' => $branch ? $branch->phone : ($brandingSettings->business_phone ?? '+233-123-456-789'),
                'police_number' => '191',
                'fire_service' => '192',
                'emergency_contacts' => $this->getEmergencyContactsList($branch, $brandingSettings, $mobileSettings),
            ];
            
            // Add branch-specific emergency contact if available
            if ($branch && isset($branch->settings['emergency_contact'])) {
                $emergencyContacts['branch_emergency_contact'] = $branch->settings['emergency_contact'];
                
                // Add branch emergency contact to the list
                $emergencyContacts['emergency_contacts'][] = [
                    'name' => $branch->name . ' Emergency',
                    'number' => $branch->settings['emergency_contact'],
                    'type' => 'hospital_branch',
                    'description' => $branch->name . ' Emergency Line'
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $emergencyContacts,
                'message' => 'Emergency contacts retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve emergency contacts: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get list of emergency contacts
     */
    private function getEmergencyContactsList($branch, $brandingSettings, $mobileSettings)
    {
        $hospitalName = $brandingSettings->business_name ?? $mobileSettings->app_name ?? config('app.name', 'Hospital');
        $hospitalPhone = $branch ? $branch->phone : ($brandingSettings->business_phone ?? '+233-123-456-789');
        
        return [
            [
                'name' => 'Emergency Services',
                'number' => '193',
                'type' => 'emergency',
                'description' => 'Ghana Emergency Services'
            ],
            [
                'name' => 'Police',
                'number' => '191',
                'type' => 'police',
                'description' => 'Ghana Police Service'
            ],
            [
                'name' => 'Fire Service',
                'number' => '192',
                'type' => 'fire',
                'description' => 'Ghana Fire Service'
            ],
            [
                'name' => 'Hospital Emergency',
                'number' => $hospitalPhone,
                'type' => 'hospital',
                'description' => $hospitalName . ' Emergency Line'
            ]
        ];
    }
    
    /**
     * Get emergency contact information for a specific branch
     */
    public function getBranchEmergencyContacts(Request $request, $branchId)
    {
        try {
            $branch = Branch::find($branchId);
            
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found'
                ], 404);
            }
            
            $brandingSettings = BrandingSetting::current();
            $mobileSettings = MobileAppSetting::current();
            
            $emergencyContacts = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'branch_code' => $branch->code,
                'hospital_name' => $brandingSettings->business_name ?? $mobileSettings->app_name ?? config('app.name', 'Hospital'),
                'hospital_phone' => $branch->phone,
                'hospital_address' => $branch->address,
                'hospital_email' => $branch->email ?? $brandingSettings->business_email,
                'emergency_number' => '193',
                'ambulance_number' => $branch->phone,
                'police_number' => '191',
                'fire_service' => '192',
                'emergency_contacts' => $this->getEmergencyContactsList($branch, $brandingSettings, $mobileSettings),
            ];
            
            // Add branch-specific emergency contact if available
            if (isset($branch->settings['emergency_contact'])) {
                $emergencyContacts['branch_emergency_contact'] = $branch->settings['emergency_contact'];
                
                // Add branch emergency contact to the list
                $emergencyContacts['emergency_contacts'][] = [
                    'name' => $branch->name . ' Emergency',
                    'number' => $branch->settings['emergency_contact'],
                    'type' => 'hospital_branch',
                    'description' => $branch->name . ' Emergency Line'
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $emergencyContacts,
                'message' => 'Branch emergency contacts retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve branch emergency contacts: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update emergency contact information for a branch
     */
    public function updateBranchEmergencyContacts(Request $request, $branchId)
    {
        try {
            $branch = Branch::find($branchId);
            
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found'
                ], 404);
            }
            
            $request->validate([
                'emergency_contact' => 'nullable|string|max:20',
                'working_hours' => 'nullable|string|max:100',
            ]);
            
            // Get current settings
            $settings = $branch->settings ?? [];
            
            // Update emergency contact settings
            if ($request->has('emergency_contact')) {
                $settings['emergency_contact'] = $request->emergency_contact;
            }
            
            if ($request->has('working_hours')) {
                $settings['working_hours'] = $request->working_hours;
            }
            
            // Update branch settings
            $branch->update(['settings' => $settings]);
            
            return response()->json([
                'success' => true,
                'data' => $branch,
                'message' => 'Branch emergency contacts updated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch emergency contacts: ' . $e->getMessage()
            ], 500);
        }
    }
}
