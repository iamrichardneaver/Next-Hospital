<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind services for dependency injection
        $this->app->singleton(\App\Services\SettingsService::class);
        $this->app->singleton(\App\Services\IdPrefixService::class);
        $this->app->singleton(\App\Services\FileStorageService::class);
        $this->app->singleton(\App\Services\PaymentService::class);
        $this->app->singleton(\App\Services\DebtorService::class);
        $this->app->singleton(\App\Services\PricingService::class);
        $this->app->singleton(\App\Services\ModulePricingService::class);
        $this->app->singleton(\App\Services\InvoiceService::class);
        $this->app->singleton(\App\Services\WardBillingService::class);
        $this->app->singleton(\App\Services\SurgeryBillingService::class);
        $this->app->singleton(\App\Services\LabTemplateSyncService::class);
        $this->app->singleton(\App\Services\PermissionSyncService::class);
        $this->app->singleton(\App\Services\PermissionAutoSync::class);
        $this->app->singleton(\App\Services\PushNotificationService::class);
        $this->app->singleton(\App\Services\AppNotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Block db:wipe / migrate:fresh / migrate:refresh on production (set APP_ENV=production in .env).
        DB::prohibitDestructiveCommands($this->app->isProduction());

        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)) {
            URL::forceScheme('https');
        }

        // Use Bootstrap 5 pagination globally
        Paginator::defaultView('vendor.pagination.bootstrap-5');
        Paginator::defaultSimpleView('vendor.pagination.bootstrap-5');
        
        // Register global view composer for dynamic branding
        View::composer('*', \App\View\Composers\BrandingComposer::class);
        
        // Register event listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\NotificationSent::class,
            \App\Listeners\SendNotificationListener::class
        );
        
        // Register observers for financial tracking
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
        \App\Models\Payment::observe(\App\Observers\PaymentObserver::class);
        \App\Models\Notification::observe(\App\Observers\NotificationObserver::class);

        // Auto-sync lab templates when test types, templates, or requests change
        \App\Models\LabTestType::observe(\App\Observers\LabTestTypeObserver::class);
        \App\Models\LabTestTemplate::observe(\App\Observers\LabTestTemplateObserver::class);
        \App\Models\LabRequest::observe(\App\Observers\LabRequestObserver::class);
        
        $this->app->booted(function () {
            if (!app()->runningUnitTests()) {
                app(\App\Services\PermissionAutoSync::class)->syncIfNeeded();
            }
        });

        // CRITICAL: Register Gate before() callback to ensure permissions are checked correctly
        // This ensures that @can() directives in Blade templates use our overridden can() method
        // We call can() directly which uses our optimized implementation
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            // Only handle permission strings, let policies go through normal flow
            if (is_string($ability) && method_exists($user, 'can')) {
                // Use our optimized can() method which checks direct permissions first
                // This avoids recursion and is more efficient
                return $user->can($ability) ? true : null;
            }
            return null;
        });
    }
}
