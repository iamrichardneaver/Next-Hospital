<?php

namespace App\Services;

use App\Models\User;

class ReportCatalog
{
    /**
     * @return list<array{category: string, title: string, description: string, route: string, permission: string, icon: string}>
     */
    public static function definitions(): array
    {
        return [
            // Financial
            [
                'category' => 'Financial',
                'title' => 'Accounting Dashboard',
                'description' => 'KPIs, outstanding debt, payments by method, and module links.',
                'route' => 'accounting.index',
                'permission' => 'view_financial_dashboard|view_financial_reports|view_revenue_analytics',
                'icon' => 'bi-calculator',
            ],
            [
                'category' => 'Financial',
                'title' => 'Revenue Streams',
                'description' => 'Service module breakdown — consultation, lab, pharmacy, radiology.',
                'route' => 'accounting.revenue',
                'permission' => 'view_revenue_reports|view_revenue_analytics',
                'icon' => 'bi-pie-chart',
            ],
            [
                'category' => 'Financial',
                'title' => 'Expenses',
                'description' => 'Operating expenses with categories and approval workflow.',
                'route' => 'accounting.expenses.index',
                'permission' => 'view_expenses|manage_expenses',
                'icon' => 'bi-wallet2',
            ],
            [
                'category' => 'Financial',
                'title' => 'Balance Sheet',
                'description' => 'Assets, liabilities, and equity snapshot.',
                'route' => 'accounting.balance-sheet',
                'permission' => 'view_balance_sheet',
                'icon' => 'bi-clipboard-data',
            ],
            [
                'category' => 'Financial',
                'title' => 'Cash Flow',
                'description' => 'Operating inflows and outflows by period.',
                'route' => 'accounting.cash-flow',
                'permission' => 'view_cash_flow',
                'icon' => 'bi-arrow-left-right',
            ],
            [
                'category' => 'Financial',
                'title' => 'Revenue vs Expenses',
                'description' => 'Comparative monthly or quarterly performance.',
                'route' => 'accounting.revenue-vs-expenses',
                'permission' => 'view_revenue_reports',
                'icon' => 'bi-bar-chart-line',
            ],
            [
                'category' => 'Financial',
                'title' => 'Revenue Analytics',
                'description' => 'Daily revenue, payment methods, and department breakdown.',
                'route' => 'revenue.index',
                'permission' => 'view_revenue_analytics',
                'icon' => 'bi-cash-stack',
            ],
            [
                'category' => 'Financial',
                'title' => 'Financial Report',
                'description' => 'Invoices, payments, and billing summary from live data.',
                'route' => 'reports.financial',
                'permission' => 'view_financial_reports|view_revenue_analytics',
                'icon' => 'bi-receipt',
            ],
            [
                'category' => 'Financial',
                'title' => 'Billing & Invoices',
                'description' => 'Invoice listing, payment status, and billing records.',
                'route' => 'billing.index',
                'permission' => 'view_invoices|manage_billing',
                'icon' => 'bi-file-earmark-text',
            ],
            [
                'category' => 'Financial',
                'title' => 'Debtors Report',
                'description' => 'Outstanding balances and payment reminders.',
                'route' => 'debtors.report',
                'permission' => 'view_debtors|manage_debtors',
                'icon' => 'bi-people-fill',
            ],
            [
                'category' => 'Financial',
                'title' => 'Cashier Daily Report',
                'description' => 'End-of-day collections and payment summary.',
                'route' => 'cashier.daily-report',
                'permission' => 'process_payments|view_cashier_reports',
                'icon' => 'bi-cash-coin',
            ],
            [
                'category' => 'Financial',
                'title' => 'Service Pricing',
                'description' => 'Fee schedules and pricing rules for billable services.',
                'route' => 'pricing.index',
                'permission' => 'view_service_pricing|manage_service_pricing',
                'icon' => 'bi-tag',
            ],
            [
                'category' => 'Financial',
                'title' => 'Insurance Analytics',
                'description' => 'Claims, policies, and provider performance.',
                'route' => 'insurance.analytics',
                'permission' => 'view_insurance|view_insurance_analytics',
                'icon' => 'bi-graph-up',
            ],
            [
                'category' => 'Financial',
                'title' => 'E-Commerce Revenue',
                'description' => 'Store orders and online sales overview.',
                'route' => 'ecommerce.dashboard',
                'permission' => 'view_store_orders|view_store_items',
                'icon' => 'bi-shop',
            ],

            // Regulatory
            [
                'category' => 'Regulatory',
                'title' => 'GHS Reports',
                'description' => 'Ghana Health Service reporting templates and submissions.',
                'route' => 'reports.ghs.index',
                'permission' => 'view_reports|generate_reports',
                'icon' => 'bi-file-earmark-medical',
            ],
            [
                'category' => 'Regulatory',
                'title' => 'NHIS Claims',
                'description' => 'National Health Insurance Scheme claims and reports.',
                'route' => 'reports.nhis.index',
                'permission' => 'view_reports|generate_reports',
                'icon' => 'bi-shield-check',
            ],

            // Clinical
            [
                'category' => 'Clinical',
                'title' => 'Consultations',
                'description' => 'Consultation records, queues, and completion statistics.',
                'route' => 'consultations.index',
                'permission' => 'view_consultations',
                'icon' => 'bi-clipboard2-pulse',
            ],
            [
                'category' => 'Clinical',
                'title' => 'Appointments',
                'description' => 'Scheduled appointments and attendance overview.',
                'route' => 'appointments.index',
                'permission' => 'view_appointments',
                'icon' => 'bi-calendar-check',
            ],
            [
                'category' => 'Clinical',
                'title' => 'OPD Visits',
                'description' => 'Outpatient and inpatient visit register.',
                'route' => 'visits.index',
                'permission' => 'view_visits',
                'icon' => 'bi-door-open',
            ],
            [
                'category' => 'Clinical',
                'title' => 'Emergency Report',
                'description' => 'Emergency department visits and statistics.',
                'route' => 'emergency.index',
                'permission' => 'view_emergency_visits',
                'icon' => 'bi-hospital',
            ],
            [
                'category' => 'Clinical',
                'title' => 'Surgery Schedule',
                'description' => 'Theatre schedules and procedure status.',
                'route' => 'surgery.index',
                'permission' => 'view_surgery_schedules',
                'icon' => 'bi-scissors',
            ],
            [
                'category' => 'Clinical',
                'title' => 'Vitals',
                'description' => 'Patient vital signs records and trends.',
                'route' => 'vitals.index',
                'permission' => 'view_vitals',
                'icon' => 'bi-activity',
            ],
            [
                'category' => 'Clinical',
                'title' => 'Teleconsultations',
                'description' => 'Remote consultation sessions and outcomes.',
                'route' => 'teleconsultations.index',
                'permission' => 'teleconsultation.view',
                'icon' => 'bi-camera-video',
            ],
            [
                'category' => 'Clinical',
                'title' => 'Prescriptions',
                'description' => 'Prescription orders and dispensing status.',
                'route' => 'pharmacy.prescriptions',
                'permission' => 'view_prescriptions',
                'icon' => 'bi-capsule',
            ],

            // Laboratory
            [
                'category' => 'Laboratory',
                'title' => 'Lab Activity',
                'description' => 'Laboratory requests and result summaries.',
                'route' => 'lab.index',
                'permission' => 'view_lab_requests',
                'icon' => 'bi-heart-pulse',
            ],
            [
                'category' => 'Laboratory',
                'title' => 'Lab Archive',
                'description' => 'Historical lab results, comparisons, and trends.',
                'route' => 'lab.archive.index',
                'permission' => 'view_lab_requests',
                'icon' => 'bi-archive',
            ],
            [
                'category' => 'Laboratory',
                'title' => 'Lab Quality Control',
                'description' => 'QC runs, statistics, and equipment performance.',
                'route' => 'lab.quality-control.statistics',
                'permission' => 'manage_lab_setup',
                'icon' => 'bi-clipboard-data',
            ],

            // Radiology
            [
                'category' => 'Radiology',
                'title' => 'Radiology Reports',
                'description' => 'Imaging reports, studies, and signed findings.',
                'route' => 'radiology.reports',
                'permission' => 'view_radiology_reports',
                'icon' => 'bi-file-medical',
            ],

            // Pharmacy
            [
                'category' => 'Pharmacy',
                'title' => 'Pharmacy Analytics',
                'description' => 'Dispensing volumes, stock movement, and trends.',
                'route' => 'pharmacy.analytics',
                'permission' => 'view_pharmacy_analytics',
                'icon' => 'bi-bar-chart-line',
            ],
            [
                'category' => 'Pharmacy',
                'title' => 'Dispensing History',
                'description' => 'Completed dispensations and fulfillment log.',
                'route' => 'pharmacy.history',
                'permission' => 'dispense_drugs|manage_pharmacy_inventory',
                'icon' => 'bi-clock-history',
            ],

            // Operational
            [
                'category' => 'Operational',
                'title' => 'Queue Statistics',
                'description' => 'OPD, lab, pharmacy, and emergency queue metrics.',
                'route' => 'queues.statistics',
                'permission' => 'view_queues|view_queue_statistics',
                'icon' => 'bi-list-ol',
            ],
            [
                'category' => 'Operational',
                'title' => 'Walk-ins Register',
                'description' => 'Walk-in patient register and daily statistics.',
                'route' => 'walk-ins.statistics',
                'permission' => 'view_walk_ins_register|manage_walk_ins',
                'icon' => 'bi-person-walking',
            ],
            [
                'category' => 'Operational',
                'title' => 'Ward Occupancy',
                'description' => 'Ward beds, admissions, and capacity overview.',
                'route' => 'wards.index',
                'permission' => 'view_wards',
                'icon' => 'bi-building',
            ],

            // Administrative
            [
                'category' => 'Administrative',
                'title' => 'E-Commerce Dashboard',
                'description' => 'Store orders, deliveries, and sales overview.',
                'route' => 'ecommerce.dashboard',
                'permission' => 'view_store_items',
                'icon' => 'bi-shop',
            ],
            [
                'category' => 'Administrative',
                'title' => 'Complaints',
                'description' => 'Patient complaints register and resolution status.',
                'route' => 'complaints.index',
                'permission' => 'view_complaints|manage_complaints',
                'icon' => 'bi-chat-left-text',
            ],
        ];
    }

    public static function userHasPermission(User $user, string $permissionExpression): bool
    {
        foreach (preg_split('/[|,]/', $permissionExpression) as $permission) {
            if ($user->can(trim($permission))) {
                return true;
            }
        }

        return false;
    }

    public static function userCanAccessHub(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        foreach (self::definitions() as $report) {
            if (self::userHasPermission($user, $report['permission'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pipe-separated permissions for reports hub route middleware (OR logic).
     */
    public static function hubMiddlewarePermissions(): string
    {
        $permissions = [];

        foreach (self::definitions() as $report) {
            foreach (preg_split('/[|,]/', $report['permission']) as $permission) {
                $permissions[] = trim($permission);
            }
        }

        return implode('|', array_values(array_unique($permissions)));
    }

    /**
     * @return list<array{category: string, reports: list<array>}>
     */
    public static function accessibleGroupedFor(User $user): array
    {
        $grouped = [];

        foreach (self::definitions() as $report) {
            if (!self::userHasPermission($user, $report['permission'])) {
                continue;
            }

            $category = $report['category'];

            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'category' => $category,
                    'reports' => [],
                ];
            }

            $grouped[$category]['reports'][] = $report;
        }

        return array_values($grouped);
    }
}
