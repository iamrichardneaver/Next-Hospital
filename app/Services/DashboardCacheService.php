<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardCacheService
{
    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION = 5; // 5 minutes for dashboard data
    const NOTIFICATION_CACHE_DURATION = 2; // 2 minutes for notifications
    const CHART_CACHE_DURATION = 10; // 10 minutes for chart data

    /**
     * Get cached dashboard data or fetch fresh data
     */
    public function getDashboardData($user, $userRole, $branchId)
    {
        $cacheKey = "dashboard_data_{$user->id}_{$userRole}_{$branchId}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user, $userRole, $branchId) {
            return $this->fetchFreshDashboardData($user, $userRole, $branchId);
        });
    }

    /**
     * Get cached notifications or fetch fresh data
     */
    public function getNotifications($user)
    {
        $cacheKey = "notifications_{$user->id}";
        
        return Cache::remember($cacheKey, self::NOTIFICATION_CACHE_DURATION, function () use ($user) {
            $notificationService = new RealtimeNotificationService();
            return $notificationService->getNotificationsForUser($user);
        });
    }

    /**
     * Get cached chart data or fetch fresh data
     */
    public function getChartData($user)
    {
        $cacheKey = "chart_data_{$user->id}";
        
        return Cache::remember($cacheKey, self::CHART_CACHE_DURATION, function () use ($user) {
            return $this->fetchFreshChartData($user);
        });
    }

    /**
     * Clear all dashboard caches for a user
     */
    public function clearUserCache($userId)
    {
        $patterns = [
            "dashboard_data_{$userId}_*",
            "notifications_{$userId}",
            "chart_data_{$userId}",
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }
    }

    /**
     * Clear all dashboard caches
     */
    public function clearAllDashboardCache()
    {
        $patterns = [
            'dashboard_data_*',
            'notifications_*',
            'chart_data_*',
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats()
    {
        return [
            'cache_duration' => self::CACHE_DURATION,
            'notification_cache_duration' => self::NOTIFICATION_CACHE_DURATION,
            'chart_cache_duration' => self::CHART_CACHE_DURATION,
            'memory_usage' => $this->getMemoryUsage(),
            'cache_hits' => $this->getCacheHits(),
        ];
    }

    /**
     * Fetch fresh dashboard data (placeholder - would be implemented by DashboardController)
     */
    protected function fetchFreshDashboardData($user, $userRole, $branchId)
    {
        // This would call the actual dashboard controller methods
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Fetch fresh chart data
     */
    protected function fetchFreshChartData($user)
    {
        $today = Carbon::today();
        $labels = [];
        $appointments = [];

        // Get last 7 days data
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $labels[] = $date->format('M d');
            
            $appointments[] = DB::table('appointments')
                ->whereDate('appointment_date', $date)
                ->count();
        }

        return [
            'labels' => $labels,
            'appointments' => $appointments,
        ];
    }

    /**
     * Clear cache by pattern (Redis implementation)
     */
    protected function clearCacheByPattern($pattern)
    {
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }
        } else {
            // For file cache, we can't easily clear by pattern
            // This would need to be implemented differently
            Cache::flush();
        }
    }

    /**
     * Get memory usage
     */
    protected function getMemoryUsage()
    {
        return [
            'used' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'formatted_used' => $this->formatBytes(memory_get_usage(true)),
            'formatted_peak' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }

    /**
     * Get cache hits (Redis implementation)
     */
    protected function getCacheHits()
    {
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $info = $redis->info('stats');
            return [
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
            ];
        }
        
        return ['hits' => 0, 'misses' => 0, 'hit_rate' => 0];
    }

    /**
     * Calculate cache hit rate
     */
    protected function calculateHitRate($info)
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Warm up cache for all active users
     */
    public function warmUpCache()
    {
        $activeUsers = DB::table('users')
            ->where('is_active', true)
            ->where('last_activity_at', '>', now()->subHours(1))
            ->get();

        foreach ($activeUsers as $user) {
            $this->getDashboardData($user, 'admin', null);
            $this->getNotifications($user);
            $this->getChartData($user);
        }
    }

    /**
     * Get cache performance metrics
     */
    public function getPerformanceMetrics()
    {
        return [
            'cache_duration' => self::CACHE_DURATION,
            'memory_usage' => $this->getMemoryUsage(),
            'cache_stats' => $this->getCacheHits(),
            'recommendations' => $this->getPerformanceRecommendations(),
        ];
    }

    /**
     * Get performance recommendations
     */
    protected function getPerformanceRecommendations()
    {
        $recommendations = [];
        $memoryUsage = $this->getMemoryUsage();
        
        if ($memoryUsage['used'] > 50 * 1024 * 1024) { // 50MB
            $recommendations[] = 'Consider increasing cache duration to reduce database queries';
        }
        
        if ($memoryUsage['peak'] > 100 * 1024 * 1024) { // 100MB
            $recommendations[] = 'High memory usage detected. Consider optimizing cache strategy';
        }
        
        $cacheStats = $this->getCacheHits();
        if ($cacheStats['hit_rate'] < 80) {
            $recommendations[] = 'Low cache hit rate. Consider increasing cache duration';
        }
        
        return $recommendations;
    }
}
