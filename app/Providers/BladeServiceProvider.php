<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BladeServiceProvider extends ServiceProvider
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
        // Safe patient name directive
        Blade::directive('patientName', function ($expression) {
            return "<?php echo \$visit && \$visit->patient ? (\$visit->patient->first_name ?? '') . ' ' . (\$visit->patient->last_name ?? '') : 'Patient Not Found'; ?>";
        });
        
        // Safe patient number directive
        Blade::directive('patientNumber', function ($expression) {
            return "<?php echo \$visit && \$visit->patient ? (\$visit->patient->patient_number ?? 'N/A') : 'ID: ' . (\$visit->patient_id ?? 'N/A'); ?>";
        });
        
        // Safe patient NHIS directive
        Blade::directive('patientNhis', function ($expression) {
            return "<?php echo \$visit && \$visit->patient ? (\$visit->patient->nhis_number ?? '') : ''; ?>";
        });
        
        // Check if patient exists directive
        Blade::directive('hasPatient', function ($expression) {
            return "<?php if(\$visit && \$visit->patient): ?>";
        });
        
        Blade::directive('endHasPatient', function ($expression) {
            return "<?php else: ?>";
        });
        
        Blade::directive('endHasPatientElse', function ($expression) {
            return "<?php endif; ?>";
        });
    }
}
