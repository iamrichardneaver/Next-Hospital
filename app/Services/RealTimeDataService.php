<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\Branch;
use Carbon\Carbon;

class RealTimeDataService
{
    private $cachePrefix = 'realtime_data_';
    private $defaultTtl = 300; // 5 minutes
    private $highFrequencyTtl = 30; // 30 seconds for critical data
    private $lowFrequencyTtl = 600; // 10 minutes for static data

    /**
     * Get real-time data for a specific module and user
     */
    public function getModuleData(string $module, int $userId, int $branchId, array $filters = [])
    {
        $cacheKey = $this->getCacheKey($module, $userId, $branchId, $filters);
        
        // Try to get from cache first
        $cachedData = Cache::get($cacheKey);
        if ($cachedData && $this->isDataFresh($cachedData)) {
            return $cachedData;
        }

        // Fetch fresh data based on module
        $data = $this->fetchModuleData($module, $userId, $branchId, $filters);
        
        // Cache the data with appropriate TTL
        $ttl = $this->getModuleTtl($module);
        Cache::put($cacheKey, $data, $ttl);
        
        return $data;
    }

    /**
     * Check if data has changed since last check
     */
    public function hasDataChanged(string $module, int $userId, int $branchId, string $lastCheckTime, array $filters = [])
    {
        $cacheKey = $this->getCacheKey($module, $userId, $branchId, $filters);
        $cachedData = Cache::get($cacheKey);
        
        if (!$cachedData) {
            return true; // No cached data means it's new
        }

        $lastModified = $cachedData['last_modified'] ?? null;
        if (!$lastModified) {
            return true;
        }

        return Carbon::parse($lastModified)->gt(Carbon::parse($lastCheckTime));
    }

    /**
     * Get data change summary for multiple modules
     */
    public function getDataChangeSummary(int $userId, int $branchId, string $lastCheckTime)
    {
        $modules = $this->getUserModules($userId);
        $changes = [];

        foreach ($modules as $module) {
            if ($this->hasDataChanged($module, $userId, $branchId, $lastCheckTime)) {
                $changes[$module] = [
                    'has_changes' => true,
                    'last_modified' => $this->getLastModifiedTime($module, $userId, $branchId),
                    'change_count' => $this->getChangeCount($module, $userId, $branchId, $lastCheckTime)
                ];
            }
        }

        return $changes;
    }

    /**
     * Invalidate cache for specific module and user
     */
    public function invalidateModuleCache(string $module, int $userId, int $branchId)
    {
        $pattern = $this->cachePrefix . "{$module}_{$userId}_{$branchId}_*";
        
        // Get all matching cache keys
        $keys = Cache::getRedis()->keys($pattern);
        
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }

        // Also invalidate related caches
        $this->invalidateRelatedCaches($module, $userId, $branchId);
    }

    /**
     * Get intelligent polling interval based on user activity and data criticality
     */
    public function getPollingInterval(int $userId, string $module = null)
    {
        $user = User::find($userId);
        if (!$user) {
            return 30000; // Default 30 seconds
        }

        // Base interval by role
        $baseInterval = $this->getRoleBasedInterval($user->role);
        
        // Adjust based on user activity
        $activityMultiplier = $this->getActivityMultiplier($userId);
        
        // Adjust based on module criticality
        $moduleMultiplier = $module ? $this->getModuleCriticalityMultiplier($module) : 1;
        
        // Adjust based on system load
        $loadMultiplier = $this->getSystemLoadMultiplier();
        
        $finalInterval = $baseInterval * $activityMultiplier * $moduleMultiplier * $loadMultiplier;
        
        // Ensure minimum and maximum bounds
        return max(5000, min(300000, $finalInterval)); // 5 seconds to 5 minutes
    }

    /**
     * Get user's active modules based on role and permissions
     */
    private function getUserModules(int $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        $modules = [];
        
        // Add modules based on user role and permissions
        if ($user->can('view_patients')) {
            $modules[] = 'patients';
        }
        if ($user->can('view_appointments')) {
            $modules[] = 'appointments';
        }
        if ($user->can('view_queue')) {
            $modules[] = 'queue';
        }
        if ($user->can('view_lab_results')) {
            $modules[] = 'lab_results';
        }
        if ($user->can('view_prescriptions')) {
            $modules[] = 'prescriptions';
        }
        if ($user->can('view_billing')) {
            $modules[] = 'billing';
        }
        if ($user->can('view_emergency_alerts')) {
            $modules[] = 'emergency_alerts';
        }
        if ($user->can('view_wards')) {
            $modules[] = 'wards';
        }
        if ($user->can('view_pharmacy')) {
            $modules[] = 'pharmacy';
        }

        return $modules;
    }

    /**
     * Fetch data for specific module
     */
    private function fetchModuleData(string $module, int $userId, int $branchId, array $filters)
    {
        $user = User::find($userId);
        $branch = Branch::find($branchId);
        
        if (!$user || !$branch) {
            return null;
        }

        switch ($module) {
            case 'patients':
                return $this->fetchPatientsData($user, $branch, $filters);
            case 'appointments':
                return $this->fetchAppointmentsData($user, $branch, $filters);
            case 'queue':
                return $this->fetchQueueData($user, $branch, $filters);
            case 'lab_results':
                return $this->fetchLabResultsData($user, $branch, $filters);
            case 'prescriptions':
                return $this->fetchPrescriptionsData($user, $branch, $filters);
            case 'billing':
                return $this->fetchBillingData($user, $branch, $filters);
            case 'emergency_alerts':
                return $this->fetchEmergencyAlertsData($user, $branch, $filters);
            case 'wards':
                return $this->fetchWardsData($user, $branch, $filters);
            case 'pharmacy':
                return $this->fetchPharmacyData($user, $branch, $filters);
            default:
                return null;
        }
    }

    /**
     * Fetch patients data
     */
    private function fetchPatientsData($user, $branch, $filters)
    {
        $query = $user->patients()->where('branch_id', $branch->id);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('first_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('last_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('patient_id', 'like', '%' . $filters['search'] . '%');
            });
        }

        $patients = $query->orderBy('updated_at', 'desc')->limit(50)->get();
        
        return [
            'data' => $patients,
            'last_modified' => now()->toISOString(),
            'total_count' => $query->count(),
            'module' => 'patients'
        ];
    }

    /**
     * Fetch appointments data
     */
    private function fetchAppointmentsData($user, $branch, $filters)
    {
        $query = $user->appointments()->where('branch_id', $branch->id);
        
        if (isset($filters['date'])) {
            $query->whereDate('appointment_date', $filters['date']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->limit(50)->get();
        
        return [
            'data' => $appointments,
            'last_modified' => now()->toISOString(),
            'total_count' => $query->count(),
            'module' => 'appointments'
        ];
    }

    /**
     * Fetch queue data
     */
    private function fetchQueueData($user, $branch, $filters)
    {
        $query = \App\Models\Queue::where('branch_id', $branch->id);
        
        // Filter by user's role-specific queues
        if ($user->hasRole('doctor')) {
            $query->where('queue_type', 'opd');
        } elseif ($user->hasRole('nurse')) {
            $query->whereIn('queue_type', ['opd', 'ipd']);
        } elseif ($user->hasRole('lab_technician')) {
            $query->where('queue_type', 'lab');
        } elseif ($user->hasRole('pharmacist')) {
            $query->where('queue_type', 'pharmacy');
        } elseif ($user->hasRole('radiologist')) {
            $query->where('queue_type', 'Radiology');
        }

        if (isset($filters['queue_type'])) {
            $query->where('queue_type', $filters['queue_type']);
        }

        $queues = $query->orderBy('created_at', 'desc')->limit(50)->get();
        
        return [
            'data' => $queues,
            'last_modified' => now()->toISOString(),
            'total_count' => $query->count(),
            'module' => 'queue'
        ];
    }

    /**
     * Fetch lab results data
     */
    private function fetchLabResultsData($user, $branch, $filters)
    {
        $query = \App\Models\LabResult::where('branch_id', $branch->id);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        $results = $query->orderBy('created_at', 'desc')->limit(50)->get();
        
        return [
            'data' => $results,
            'last_modified' => now()->toISOString(),
            'total_count' => $query->count(),
            'module' => 'lab_results'
        ];
    }

    /**
     * Fetch prescriptions data
     */
    private function fetchPrescriptionsData($user, $branch, $filters)
    {
        $query = \App\Models\Prescription::where('branch_id', $branch->id);
        
        if ($user->hasRole('doctor')) {
            $query->where('doctor_id', $user->id);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $prescriptions = $query->orderBy('created_at', 'desc')->limit(50)->get();
        
        return [
            'data' => $prescriptions,
            'last_modified' => now()->toISOString(),
            'total_count' => $query->count(),
            'module' => 'prescriptions'
        ];
    }

    /**
     * Fetch billing data
     */
    private function fetchBillingData($user, $branch, $filters)
    {
        $query = \App\Models\Invoice::where('branch_id', $branch->id);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        $invoices = $query->orderBy('created_at', 'desc')->limit(50)->get();
        
        return [
            'data' => $invoices,
            'last_modified' => now()->toISOString(),
            'total_count' => $query->count(),
            'module' => 'billing'
        ];
    }

    /**
     * Fetch emergency alerts data
     */
    private function fetchEmergencyAlertsData($user, $branch, $filters)
    {
        $query = \App\Models\EmergencyAlert::where('branch_id', $branch->id)
            ->where('created_at', '>=', now()->subHours(24)); // Last 24 hours
        
        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $alerts = $query->orderBy('created_at', 'desc')->limit(20)->get();
        
        return [
            'data' => $alerts,
            'last_modified' => now()->toISOString(),
            'total_count' => $query->count(),
            'module' => 'emergency_alerts'
        ];
    }

    /**
     * Fetch wards data
     */
    private function fetchWardsData($user, $branch, $filters)
    {
        $query = \App\Models\Ward::where('branch_id', $branch->id);
        
        if (isset($filters['ward_type'])) {
            $query->where('ward_type', $filters['ward_type']);
        }

        $wards = $query->with(['beds' => function($q) {
            $q->with('patient');
        }])->get();
        
        return [
            'data' => $wards,
            'last_modified' => now()->toISOString(),
            'total_count' => $wards->count(),
            'module' => 'wards'
        ];
    }

    /**
     * Fetch pharmacy data
     */
    private function fetchPharmacyData($user, $branch, $filters)
    {
        $query = \App\Models\DrugStock::where('branch_id', $branch->id);
        
        if (isset($filters['low_stock'])) {
            $query->whereColumn('current_stock', '<=', 'reorder_level');
        }

        $stocks = $query->with('drug')->orderBy('current_stock', 'asc')->limit(50)->get();
        
        return [
            'data' => $stocks,
            'last_modified' => now()->toISOString(),
            'total_count' => $query->count(),
            'module' => 'pharmacy'
        ];
    }

    /**
     * Get cache key for module data
     */
    private function getCacheKey(string $module, int $userId, int $branchId, array $filters = [])
    {
        $filterHash = md5(serialize($filters));
        return "{$this->cachePrefix}{$module}_{$userId}_{$branchId}_{$filterHash}";
    }

    /**
     * Check if cached data is still fresh
     */
    private function isDataFresh(array $cachedData)
    {
        $lastModified = $cachedData['last_modified'] ?? null;
        if (!$lastModified) {
            return false;
        }

        $module = $cachedData['module'] ?? 'default';
        $ttl = $this->getModuleTtl($module);
        
        return Carbon::parse($lastModified)->addSeconds($ttl)->gt(now());
    }

    /**
     * Get TTL for specific module
     */
    private function getModuleTtl(string $module)
    {
        $highFrequencyModules = ['queue', 'emergency_alerts', 'wards'];
        $lowFrequencyModules = ['patients', 'appointments'];
        
        if (in_array($module, $highFrequencyModules)) {
            return $this->highFrequencyTtl;
        } elseif (in_array($module, $lowFrequencyModules)) {
            return $this->lowFrequencyTtl;
        }
        
        return $this->defaultTtl;
    }

    /**
     * Get role-based polling interval
     */
    private function getRoleBasedInterval($role)
    {
        $intervals = [
            'doctor' => 15000,      // 15 seconds
            'nurse' => 20000,       // 20 seconds
            'lab_technician' => 25000, // 25 seconds
            'pharmacist' => 30000,   // 30 seconds
            'receptionist' => 20000, // 20 seconds
            'admin' => 60000,       // 1 minute
            'super_admin' => 120000, // 2 minutes
        ];

        return $intervals[$role] ?? 30000;
    }

    /**
     * Get activity multiplier based on user activity
     */
    private function getActivityMultiplier(int $userId)
    {
        $lastActivity = Cache::get("user_activity_{$userId}");
        if (!$lastActivity) {
            return 1.0;
        }

        $minutesSinceActivity = now()->diffInMinutes(Carbon::parse($lastActivity));
        
        if ($minutesSinceActivity < 5) {
            return 0.5; // More frequent updates for active users
        } elseif ($minutesSinceActivity < 15) {
            return 0.8;
        } elseif ($minutesSinceActivity < 60) {
            return 1.0;
        } else {
            return 2.0; // Less frequent updates for inactive users
        }
    }

    /**
     * Get module criticality multiplier
     */
    private function getModuleCriticalityMultiplier(string $module)
    {
        $criticality = [
            'emergency_alerts' => 0.3,  // Very frequent
            'queue' => 0.5,            // High frequency
            'wards' => 0.7,            // Medium-high frequency
            'lab_results' => 0.8,      // Medium frequency
            'prescriptions' => 0.9,    // Medium frequency
            'appointments' => 1.0,     // Normal frequency
            'patients' => 1.2,         // Lower frequency
            'billing' => 1.5,          // Lower frequency
            'pharmacy' => 1.0,         // Normal frequency
        ];

        return $criticality[$module] ?? 1.0;
    }

    /**
     * Get system load multiplier
     */
    private function getSystemLoadMultiplier()
    {
        $loadAverage = sys_getloadavg()[0] ?? 1.0;
        
        if ($loadAverage < 1.0) {
            return 0.8; // System is light, can poll more frequently
        } elseif ($loadAverage < 2.0) {
            return 1.0; // Normal load
        } elseif ($loadAverage < 4.0) {
            return 1.5; // High load, poll less frequently
        } else {
            return 2.0; // Very high load, poll much less frequently
        }
    }

    /**
     * Get last modified time for module
     */
    private function getLastModifiedTime(string $module, int $userId, int $branchId)
    {
        $cacheKey = $this->getCacheKey($module, $userId, $branchId);
        $cachedData = Cache::get($cacheKey);
        
        return $cachedData['last_modified'] ?? now()->toISOString();
    }

    /**
     * Get change count for module since last check
     */
    private function getChangeCount(string $module, int $userId, int $branchId, string $lastCheckTime)
    {
        // This would implement actual change counting logic
        // For now, return a simple count based on recent activity
        return rand(0, 5);
    }

    /**
     * Invalidate related caches when data changes
     */
    private function invalidateRelatedCaches(string $module, int $userId, int $branchId)
    {
        $relatedModules = $this->getRelatedModules($module);
        
        foreach ($relatedModules as $relatedModule) {
            $pattern = $this->cachePrefix . "{$relatedModule}_{$userId}_{$branchId}_*";
            $keys = Cache::getRedis()->keys($pattern);
            
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }

    /**
     * Get modules related to the given module
     */
    private function getRelatedModules(string $module)
    {
        $relations = [
            'patients' => ['appointments', 'queue', 'billing'],
            'appointments' => ['patients', 'queue'],
            'queue' => ['patients', 'appointments'],
            'lab_results' => ['patients', 'prescriptions'],
            'prescriptions' => ['patients', 'pharmacy'],
            'billing' => ['patients', 'appointments'],
            'emergency_alerts' => ['patients', 'queue'],
            'wards' => ['patients', 'queue'],
            'pharmacy' => ['prescriptions', 'patients'],
        ];

        return $relations[$module] ?? [];
    }
}
