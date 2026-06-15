<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Payment;
use App\Models\RevenueTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RevenueReportService
{
    public const PERMISSION_SERVICE_MAP = [
        'dispense_drugs' => ['pharmacy'],
        'view_prescriptions' => ['pharmacy'],
        'process_lab_requests' => ['lab'],
        'enter_lab_results' => ['lab'],
        'process_radiology_requests' => ['imaging'],
        'upload_radiology_results' => ['imaging'],
        'create_consultations' => ['consultation'],
        'view_appointments' => ['consultation'],
        'process_payments' => ['consultation', 'lab', 'pharmacy', 'imaging', 'ward', 'surgery', 'ecommerce', 'insurance', 'other'],
    ];

    public function __construct(
        protected AccountingReportService $accountingReportService
    ) {}

    public function getRevenueComposition(?int $branchId, string $startDate, string $endDate): array
    {
        return $this->accountingReportService->getRevenueByServiceType($branchId, $startDate, $endDate);
    }

    public function getRevenueDrillDown(
        string $serviceType,
        ?int $branchId,
        string $startDate,
        string $endDate,
        ?int $doctorId = null
    ): Collection {
        $query = $this->scopedRevenueQuery($branchId, $startDate, $endDate, [$serviceType]);
        $this->applyDoctorScope($query, $doctorId);

        return $query
            ->with(['patient:id,patient_number,first_name,last_name', 'branch:id,name'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (RevenueTransaction $tx) => $this->formatDrillDownRow($tx));
    }

    public function userCanViewRevenue(User $user): bool
    {
        if ($this->userHasFullRevenueAccess($user)) {
            return true;
        }

        return !empty($this->resolveUserRevenueServiceTypes($user));
    }

    public function getDashboardRevenue(User $user, ?int $branchId): array
    {
        if (!$this->userCanViewRevenue($user)) {
            return [
                'revenue_visible' => false,
            ];
        }

        if ($this->userHasFullRevenueAccess($user)) {
            $amount = $this->accountingReportService->getTotalRevenue($branchId, null, null);
            $today = Carbon::today()->toDateString();

            return [
                'revenue_visible' => true,
                'revenue_amount' => round($amount, 2),
                'revenue_label' => 'Total Revenue (All Time)',
                'revenue_scope' => 'all_time',
                'revenue_tooltip' => 'Cumulative completed payments recorded in revenue_transactions from inception.',
                'today_revenue' => round($this->accountingReportService->getTotalRevenue($branchId, $today, $today), 2),
                'monthly_revenue' => round(
                    $this->accountingReportService->getTotalRevenue(
                        $branchId,
                        Carbon::now()->startOfMonth()->toDateString(),
                        $today
                    ),
                    2
                ),
            ];
        }

        $scoped = $this->getRoleScopedRevenue($user, $branchId);

        return [
            'revenue_visible' => true,
            'revenue_amount' => $scoped['amount'],
            'revenue_label' => $scoped['label'],
            'revenue_scope' => 'today_module',
            'revenue_tooltip' => $scoped['tooltip'],
            'revenue_service_types' => $scoped['service_types'],
        ];
    }

    public function getRoleScopedRevenue(User $user, ?int $branchId, ?string $date = null): array
    {
        $date = $date ?? Carbon::today()->toDateString();
        $serviceTypes = $this->resolveUserRevenueServiceTypes($user);

        if (empty($serviceTypes)) {
            return [
                'amount' => 0.0,
                'visible' => false,
                'label' => '',
                'tooltip' => '',
                'service_types' => [],
                'date' => $date,
            ];
        }

        $query = $this->scopedRevenueQuery($branchId, $date, $date, $serviceTypes);

        if ($this->userNeedsDoctorScope($user)) {
            $this->applyDoctorScope($query, $user->id);
        }

        $amount = (float) $query->sum('amount');

        return [
            'amount' => round($amount, 2),
            'visible' => true,
            'label' => $this->buildScopedRevenueLabel($user, $serviceTypes),
            'tooltip' => 'Today\'s completed payments for your module only. Resets daily at midnight.',
            'service_types' => $serviceTypes,
            'date' => $date,
        ];
    }

    public function resolveUserRevenueServiceTypes(User $user): array
    {
        if ($this->userHasFullRevenueAccess($user)) {
            return RevenueTransaction::query()
                ->distinct()
                ->pluck('service_type')
                ->filter()
                ->values()
                ->all();
        }

        $types = [];

        if ($user->can('dispense_drugs') || $user->can('view_prescriptions')) {
            $types[] = 'pharmacy';
        }

        if ($user->can('process_lab_requests') || $user->can('enter_lab_results')) {
            $types[] = 'lab';
        }

        if ($user->can('process_radiology_requests') || $user->can('upload_radiology_results')) {
            $types[] = 'imaging';
        }

        if ($user->can('create_consultations')) {
            $types[] = 'consultation';
        } elseif ($user->can('view_appointments') && !$user->can('create_consultations')) {
            $types[] = 'consultation';
        }

        if ($user->can('process_payments') && empty($types)) {
            $types = ['consultation', 'lab', 'pharmacy', 'imaging'];
        }

        return array_values(array_unique($types));
    }

    public function userHasFullRevenueAccess(User $user): bool
    {
        return $user->isAdminOrSuperAdmin()
            || $user->can('view_financial_reports');
    }

    public function userNeedsDoctorScope(User $user): bool
    {
        return $user->can('create_consultations')
            && $user->can('create_prescriptions')
            && !$user->isAdminOrSuperAdmin()
            && !$user->can('view_financial_reports');
    }

    protected function scopedRevenueQuery(
        ?int $branchId,
        string $startDate,
        string $endDate,
        array $serviceTypes
    ): Builder {
        $query = RevenueTransaction::query()
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        if (!empty($serviceTypes)) {
            $query->whereIn('service_type', $serviceTypes);
        }

        return $query;
    }

    protected function applyDoctorScope(Builder $query, ?int $doctorId): void
    {
        if (!$doctorId) {
            return;
        }

        $invoiceIds = Consultation::query()
            ->where('doctor_id', $doctorId)
            ->whereNotNull('invoice_id')
            ->pluck('invoice_id');

        if ($invoiceIds->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $outer) use ($invoiceIds) {
            $outer->whereHasMorph('source', [Payment::class], function (Builder $paymentQuery) use ($invoiceIds) {
                $paymentQuery->whereIn('invoice_id', $invoiceIds);
            })->orWhere(function (Builder $metaQuery) use ($invoiceIds) {
                foreach ($invoiceIds as $invoiceId) {
                    $metaQuery->orWhereJsonContains('metadata->invoice_id', $invoiceId);
                }
            });
        });
    }

    protected function formatDrillDownRow(RevenueTransaction $tx): array
    {
        $metadata = $tx->metadata ?? [];
        $patient = $tx->patient;

        return [
            'id' => $tx->id,
            'transaction_reference' => $tx->transaction_reference,
            'transaction_date' => $tx->transaction_date?->format('Y-m-d'),
            'service_type' => $tx->service_type,
            'service_label' => AccountingReportService::SERVICE_TYPE_LABELS[$tx->service_type] ?? ucfirst($tx->service_type ?? 'other'),
            'amount' => round((float) $tx->amount, 2),
            'payment_method' => $tx->payment_method,
            'invoice_id' => $metadata['invoice_id'] ?? null,
            'invoice_number' => $metadata['invoice_number'] ?? null,
            'patient_id' => $tx->patient_id,
            'patient_name' => $patient
                ? trim("{$patient->first_name} {$patient->last_name}")
                : '—',
            'patient_number' => $patient?->patient_number,
            'branch_name' => $tx->branch?->name,
        ];
    }

    protected function buildScopedRevenueLabel(User $user, array $serviceTypes): string
    {
        if ($this->userNeedsDoctorScope($user)) {
            return 'Today\'s Consultation Revenue';
        }

        if (count($serviceTypes) === 1) {
            $label = AccountingReportService::SERVICE_TYPE_LABELS[$serviceTypes[0]] ?? ucfirst($serviceTypes[0]);

            return "Today's {$label} Revenue";
        }

        return 'Today\'s Module Revenue';
    }
}
