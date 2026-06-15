<?php

namespace App\Services;

class HintGuideService
{
    /**
     * Get the current page identifier for hint guide
     */
    public static function getCurrentPage(): string
    {
        $route = request()->route();
        if (!$route || !$route->getName()) {
            return 'dashboard';
        }

        $routeName = $route->getName();
        
        // Map route names to page identifiers
        $pageMap = [
            'patients.index' => 'patients.index',
            'patients.show' => 'patients.show',
            'patients.create' => 'patients.create',
            'patients.edit' => 'patients.create', // Use same hints as create
            'appointments.index' => 'appointments.index',
            'appointments.create' => 'appointments.create',
            'appointments.edit' => 'appointments.create', // Use same hints as create
            'consultations.doctor-queue' => 'consultations.doctor-queue',
            'consultations.show' => 'consultations.show',
            'consultations.create' => 'consultations.show', // Use same hints as show
            'consultations.edit' => 'consultations.show', // Use same hints as show
            'pharmacy.prescriptions' => 'pharmacy.prescriptions.index',
            'pharmacy.prescriptions.show' => 'pharmacy.prescriptions.show',
            'pharmacy.prescriptions.edit' => 'pharmacy.prescriptions.show', // Use same hints as show
            'pharmacy.dispensing' => 'pharmacy.dispensing',
            'pharmacy.stock' => 'pharmacy.stock',
            'pharmacy.analytics' => 'pharmacy.analytics',
            'lab.requests.index' => 'lab.requests.index',
            'lab.results.index' => 'lab.results.index',
            'billing.invoices.index' => 'billing.invoices.index',
            'billing.invoices.create' => 'billing.invoices.create',
            'billing.invoices.edit' => 'billing.invoices.create', // Use same hints as create
            'teleconsultations.index' => 'teleconsultations.index',
            'teleconsultations.show' => 'teleconsultations.index', // Use same hints as index
            'settings.index' => 'settings.index',
            'users.index' => 'users.index',
            'users.create' => 'users.index', // Use same hints as index
            'users.edit' => 'users.index', // Use same hints as index
            'branches.index' => 'branches.index',
            'branches.create' => 'branches.index', // Use same hints as index
            'branches.edit' => 'branches.index', // Use same hints as index
            'lab.archive.index' => 'lab.archive.index',
            'lab.archive.patient-history' => 'lab.archive.patient-history',
            'lab.archive.compare-results' => 'lab.archive.compare-results',
            'pharmacy.history' => 'pharmacy.history',
            'billing.payments.index' => 'billing.payments.index',
            'billing.reports.index' => 'billing.reports.index',
            'reports.index' => 'reports.index',
            'audit.index' => 'audit.index',
            'notifications.index' => 'notifications.index',
            'profile.show' => 'profile.show',
            'profile.edit' => 'profile.edit',
            // Insurance Module Routes
            'insurance.index' => 'insurance.index',
            'insurance.providers' => 'insurance.providers',
            'insurance.policies' => 'insurance.policies',
            'insurance.claims' => 'insurance.claims',
            'insurance.pre-authorizations' => 'insurance.pre-authorizations',
            'insurance.analytics' => 'insurance.analytics',
            // Queue Management Routes
            'queues.index' => 'queues.index',
            'queues.opd' => 'queues.index',
            'queues.lab' => 'queues.index',
            'queues.pharmacy' => 'queues.index',
            'queues.emergency' => 'queues.index',
            // Visits Module Routes
            'visits.index' => 'visits.index',
            'visits.create' => 'visits.index',
            'visits.show' => 'visits.index',
            'visits.edit' => 'visits.index',
            // Walk-ins Module Routes
            'walk-ins.index' => 'walk-ins.index',
            'walk-ins.show' => 'walk-ins.index',
            'walk-ins.export-pdf' => 'walk-ins.index',
            // Radiology Module Routes
            'radiology.index' => 'radiology.index',
            'radiology.create' => 'radiology.index',
            'radiology.show' => 'radiology.index',
            'radiology.edit' => 'radiology.index',
            'radiology.studies.index' => 'radiology.index',
            'radiology.studies.create' => 'radiology.index',
            'radiology.reports' => 'radiology.index',
            'radiology.reports.index' => 'radiology.index',
            'radiology.reports.show' => 'radiology.index',
            'radiology.reports.edit' => 'radiology.index',
            // Lab Module Additional Routes
            'lab.index' => 'lab.requests.index',
            'lab.create' => 'lab.requests.index',
            'lab.show' => 'lab.requests.index',
            'lab.edit' => 'lab.requests.index',
            'lab.results.enter' => 'lab.results.index',
            'lab.templates.index' => 'lab.requests.index',
            'lab.templates.create' => 'lab.requests.index',
            'lab.templates.show' => 'lab.requests.index',
            'lab.templates.edit' => 'lab.requests.index',
            'lab.test-types.index' => 'lab.requests.index',
            'lab.test-types.create' => 'lab.requests.index',
            'lab.test-types.show' => 'lab.requests.index',
            'lab.test-types.edit' => 'lab.requests.index',
            'lab.tests.index' => 'lab.requests.index',
            'lab.tests.create' => 'lab.requests.index',
            'lab.tests.show' => 'lab.requests.index',
            'lab.tests.edit' => 'lab.requests.index',
            'lab.categories.index' => 'lab.requests.index',
            'lab.categories.create' => 'lab.requests.index',
            'lab.categories.edit' => 'lab.requests.index',
            'lab.parameters.create' => 'lab.requests.index',
            'lab.parameters.edit' => 'lab.requests.index',
            'lab.reference-ranges.create' => 'lab.requests.index',
            'lab.reference-ranges.edit' => 'lab.requests.index',
            // Pharmacy Module Additional Routes
            'pharmacy.index' => 'pharmacy.prescriptions.index',
            'pharmacy.create' => 'pharmacy.prescriptions.index',
            'pharmacy.show' => 'pharmacy.prescriptions.show',
            'pharmacy.edit' => 'pharmacy.prescriptions.show',
            'pharmacy.dispensing.index' => 'pharmacy.dispensing',
            'pharmacy.stock.index' => 'pharmacy.stock',
            'pharmacy.history.index' => 'pharmacy.history',
            'pharmacy.analytics.index' => 'pharmacy.analytics',
            'pharmacy.prescriptions.create' => 'pharmacy.prescriptions.index',
            'pharmacy.prescriptions.edit' => 'pharmacy.prescriptions.show',
            'pharmacy.prescriptions.dispense' => 'pharmacy.prescriptions.show',
            'pharmacy.prescriptions.print' => 'pharmacy.prescriptions.show',
            'pharmacy.prescriptions.generate-billing' => 'pharmacy.prescriptions.show',
            'pharmacy.prescriptions.interactions' => 'pharmacy.prescriptions.show',
            'pharmacy.stock-alerts' => 'pharmacy.stock',
        ];
        
        return $pageMap[$routeName] ?? 'dashboard';
    }
    
    /**
     * Check if hints should be shown for current page
     */
    public static function shouldShowHints(): bool
    {
        $currentPage = self::getCurrentPage();
        
        // Don't show hints on dashboard
        if ($currentPage === 'dashboard') {
            return false;
        }
        
        // Don't show hints on error pages
        $route = request()->route();
        if ($route && str_contains((string) $route->getName(), 'error')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get contextual hints based on user role and page
     */
    public static function getContextualHints(string $page): array
    {
        $user = auth()->user();
        $role = $user ? $user->getRoleNames()->first() : 'guest';
        
        $contextualHints = [
            'pharmacy.prescriptions.show' => [
                'pharmacist' => [
                    'Always verify patient identity before dispensing',
                    'Check for drug interactions and allergies',
                    'Ensure proper storage conditions for medications'
                ],
                'doctor' => [
                    'Review prescription details before patient pickup',
                    'Consider patient compliance when prescribing',
                    'Update prescription if patient condition changes'
                ]
            ],
            'consultations.show' => [
                'doctor' => [
                    'Document all findings thoroughly for legal compliance',
                    'Use standardized medical terminology',
                    'Consider follow-up appointments for complex cases'
                ],
                'nurse' => [
                    'Record accurate vital signs measurements',
                    'Note any patient concerns or symptoms',
                    'Prepare consultation room before doctor arrives'
                ]
            ],
            'lab.requests.index' => [
                'lab_technician' => [
                    'Process urgent requests first',
                    'Verify patient identity before testing',
                    'Follow proper specimen handling procedures'
                ],
                'doctor' => [
                    'Provide clear clinical history for accurate results',
                    'Specify urgency level for time-sensitive tests',
                    'Review results promptly for patient care'
                ]
            ]
        ];
        
        return $contextualHints[$page][$role] ?? [];
    }
}
