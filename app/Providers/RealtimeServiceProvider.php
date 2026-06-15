<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class RealtimeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Blade directive for real-time data
        Blade::directive('realtime', function ($expression) {
            return "<?php echo view('components.realtime-data', ['module' => $expression])->render(); ?>";
        });

        // Register Blade directive for real-time table
        Blade::directive('realtimeTable', function ($expression) {
            return "<?php echo view('components.realtime-table', ['module' => $expression])->render(); ?>";
        });

        // Register Blade directive for real-time counter
        Blade::directive('realtimeCounter', function ($expression) {
            return "<?php echo view('components.realtime-counter', ['module' => $expression])->render(); ?>";
        });
    }
}
