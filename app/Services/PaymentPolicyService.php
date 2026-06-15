<?php

namespace App\Services;

use App\Exceptions\PaymentGateException;
use App\Models\BedAssignment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\Visit;

/**
 * Central payment policy: OPD/outpatient requires full payment before service;
 * IPD/inpatient allows partial or full payment before or after service.
 *
 * Visit types (DB enum on visits.visit_type):
 *   OPD, IPD, Emergency, LabOnly, PharmacyOnly, RadiologyOnly
 */
class PaymentPolicyService
{
    public const VISIT_TYPE_OPD = 'OPD';
    public const VISIT_TYPE_IPD = 'IPD';
    public const VISIT_TYPE_EMERGENCY = 'Emergency';
    public const VISIT_TYPE_LAB_ONLY = 'LabOnly';
    public const VISIT_TYPE_PHARMACY_ONLY = 'PharmacyOnly';
    public const VISIT_TYPE_RADIOLOGY_ONLY = 'RadiologyOnly';

    /** Outpatient visit types — full payment required before service. */
    public const OUTPATIENT_VISIT_TYPES = [
        self::VISIT_TYPE_OPD,
        self::VISIT_TYPE_EMERGENCY,
        self::VISIT_TYPE_LAB_ONLY,
        self::VISIT_TYPE_PHARMACY_ONLY,
        self::VISIT_TYPE_RADIOLOGY_ONLY,
    ];

    /** Inpatient visit types — partial payment allowed; workflow not blocked on balance. */
    public const INPATIENT_VISIT_TYPES = [
        self::VISIT_TYPE_IPD,
    ];

    /** Queue types that enforce outpatient full-pay-before-service rules. */
    public const FULL_PAYMENT_QUEUE_TYPES = [
        'OPD',
        'Lab',
        'Pharmacy',
        'Radiology',
        'Emergency',
    ];

    public const ALL_VISIT_TYPES = [
        self::VISIT_TYPE_OPD,
        self::VISIT_TYPE_IPD,
        self::VISIT_TYPE_EMERGENCY,
        self::VISIT_TYPE_LAB_ONLY,
        self::VISIT_TYPE_PHARMACY_ONLY,
        self::VISIT_TYPE_RADIOLOGY_ONLY,
    ];

    public function __construct(
        protected PendingChargesService $pendingChargesService
    ) {}

    public function isInpatient(?Visit $visit): bool
    {
        if (!$visit) {
            return false;
        }

        if (in_array($visit->visit_type, self::INPATIENT_VISIT_TYPES, true)) {
            return true;
        }

        return BedAssignment::where('visit_id', $visit->id)
            ->where('status', 'active')
            ->exists();
    }

    public function isOutpatient(?Visit $visit): bool
    {
        return !$this->isInpatient($visit);
    }

    public function requiresFullPaymentBeforeService(?Visit $visit): bool
    {
        return $this->isOutpatient($visit);
    }

    public function allowsPartialPayment(?Visit $visit): bool
    {
        return $this->isInpatient($visit);
    }

    public function allowsPaymentAfterService(?Visit $visit): bool
    {
        return $this->isInpatient($visit);
    }

    /**
     * Outpatient service queues enforce full-pay rules (OPD, direct-service, emergency).
     */
    public function requiresFullPaymentForQueue(Queue $queue): bool
    {
        return in_array($queue->queue_type, self::FULL_PAYMENT_QUEUE_TYPES, true);
    }

    public function resolveVisitForQueue(Queue $queue): ?Visit
    {
        if ($queue->relationLoaded('visit') && $queue->visit) {
            return $queue->visit;
        }

        if ($queue->visit_id) {
            return Visit::find($queue->visit_id);
        }

        return null;
    }

    /**
     * Resolve billing context for a patient (OPD vs IPD) for cashier UI.
     */
    public function resolvePatientBillingContext(int $patientId): array
    {
        $activeIpdVisit = Visit::where('patient_id', $patientId)
            ->where('status', 'active')
            ->where('visit_type', self::VISIT_TYPE_IPD)
            ->latest('id')
            ->first();

        $hasActiveAdmission = BedAssignment::where('patient_id', $patientId)
            ->where('status', 'active')
            ->exists();

        $isIpd = $activeIpdVisit !== null || $hasActiveAdmission;
        $visit = $activeIpdVisit;

        if (!$visit && $hasActiveAdmission) {
            $assignment = BedAssignment::where('patient_id', $patientId)
                ->where('status', 'active')
                ->whereNotNull('visit_id')
                ->latest('id')
                ->first();
            if ($assignment?->visit_id) {
                $visit = Visit::find($assignment->visit_id);
            }
        }

        return $this->buildPolicyContext($isIpd ? ($visit ?? new Visit(['visit_type' => self::VISIT_TYPE_IPD])) : null, $isIpd ? 'IPD' : 'OPD');
    }

    public function buildPolicyContext(?Visit $visit, ?string $contextLabel = null): array
    {
        $isIpd = $visit ? $this->isInpatient($visit) : ($contextLabel === 'IPD');

        return [
            'context' => $isIpd ? 'IPD' : 'OPD',
            'visit_type' => $visit?->visit_type,
            'requires_full_payment_before_service' => !$isIpd,
            'allows_partial_payment' => $isIpd,
            'allows_payment_after_service' => $isIpd,
            'policy_message' => $isIpd
                ? 'Inpatient (IPD): partial or full payment allowed before or after service.'
                : 'Outpatient (OPD): full payment required before service. Partial payments are not allowed.',
        ];
    }

    public function getOutstandingAmount(int $patientId, ?int $branchId = null): float
    {
        $pendingCharges = $this->pendingChargesService->getPatientPendingCharges($patientId, $branchId);
        $pendingTotal = array_sum(array_map(
            fn ($c) => (float) ($c['amount'] ?? 0),
            $pendingCharges
        ));

        $invoiceQuery = Invoice::where('patient_id', $patientId)
            ->where('balance_amount', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue']);

        if ($branchId) {
            $invoiceQuery->where('branch_id', $branchId);
        }

        $invoiceBalance = (float) $invoiceQuery->sum('balance_amount');

        return round($pendingTotal + $invoiceBalance, 2);
    }

    public function getPaymentStatusSummary(int $patientId, ?int $branchId = null, ?Visit $visit = null): array
    {
        $amountDue = $this->getOutstandingAmount($patientId, $branchId);
        $requiresPayment = $visit
            ? $this->requiresFullPaymentBeforeService($visit)
            : true;

        $canProceed = !$requiresPayment || $amountDue <= 0;

        return [
            'can_proceed' => $canProceed,
            'payment_required' => $requiresPayment && $amountDue > 0,
            'is_paid' => $amountDue <= 0,
            'amount_due' => $amountDue,
            'status' => $amountDue <= 0 ? 'paid' : 'unpaid',
            'status_label' => $amountDue <= 0 ? 'Paid' : 'Unpaid',
            'message' => $canProceed
                ? null
                : 'Full payment required before service. Amount due: GHS ' . number_format($amountDue, 2),
            'cashier_url' => url('/cashier') . '?patient_id=' . $patientId,
            'policy' => $this->buildPolicyContext($visit),
        ];
    }

    /**
     * Group pending charges by type for payment banners.
     */
    public function getChargeBreakdown(int $patientId, ?int $branchId = null): array
    {
        $pendingCharges = $this->pendingChargesService->getPatientPendingCharges($patientId, $branchId);

        $groups = [
            'consultation' => ['label' => 'Consultation', 'count' => 0, 'amount' => 0.0],
            'lab' => ['label' => 'Lab Tests', 'count' => 0, 'amount' => 0.0],
            'prescription' => ['label' => 'Prescriptions', 'count' => 0, 'amount' => 0.0],
            'radiology' => ['label' => 'Radiology', 'count' => 0, 'amount' => 0.0],
            'appointment' => ['label' => 'Appointments', 'count' => 0, 'amount' => 0.0],
            'invoice' => ['label' => 'Invoices', 'count' => 0, 'amount' => 0.0],
            'other' => ['label' => 'Other', 'count' => 0, 'amount' => 0.0],
        ];

        foreach ($pendingCharges as $charge) {
            $type = $charge['type'] ?? 'other';
            $key = array_key_exists($type, $groups) ? $type : 'other';
            $groups[$key]['count']++;
            $groups[$key]['amount'] += (float) ($charge['amount'] ?? 0);
        }

        return array_values(array_filter($groups, fn ($group) => $group['count'] > 0));
    }

    public function assertCanProceedWithQueue(Queue $queue): void
    {
        if (!$this->requiresFullPaymentForQueue($queue)) {
            return;
        }

        $visit = $this->resolveVisitForQueue($queue);
        $this->assertCanProceedWithService($visit, (int) $queue->patient_id, (int) $queue->branch_id);
    }

    public function assertCanProceedWithService(?Visit $visit, ?int $patientId = null, ?int $branchId = null): void
    {
        if ($visit && $this->isInpatient($visit)) {
            return;
        }

        $patientId = $patientId ?? $visit?->patient_id;
        if (!$patientId) {
            return;
        }

        $summary = $this->getPaymentStatusSummary($patientId, $branchId ?? $visit?->branch_id, $visit);

        if (!$summary['can_proceed']) {
            throw new PaymentGateException(
                $summary['message'] ?? 'Full payment required before service',
                $summary['amount_due'],
                $patientId,
                $visit?->id
            );
        }
    }

    public function assertCanProceedWithConsultation(?Visit $visit, int $patientId, ?int $branchId = null): void
    {
        $this->assertCanProceedWithService($visit, $patientId, $branchId);
    }

    /**
     * Reject partial payments when policy requires full payment (OPD/outpatient).
     */
    public function validatePaymentAmount(?Visit $visit, Invoice $invoice, float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero');
        }

        $balance = (float) ($invoice->balance_amount ?? max(0, $invoice->total_amount - $invoice->paid_amount));

        if ($visit && $this->allowsPartialPayment($visit)) {
            if ($amount > $balance + 0.01) {
                throw new \InvalidArgumentException('Payment amount cannot exceed invoice balance');
            }
            return;
        }

        if (!$visit) {
            $context = $this->resolvePatientBillingContext((int) $invoice->patient_id);
            if ($context['allows_partial_payment']) {
                if ($amount > $balance + 0.01) {
                    throw new \InvalidArgumentException('Payment amount cannot exceed invoice balance');
                }
                return;
            }
        }

        if ($amount < $balance - 0.01) {
            throw new \InvalidArgumentException(
                'Full payment required for outpatient services. Partial payments are not allowed. Balance due: GHS '
                . number_format($balance, 2)
            );
        }
    }

    /**
     * Reject partial payment on a batch of selected charges (OPD cashier flow).
     */
    public function validateChargePaymentAmount(?Visit $visit, float $selectedTotal, float $paymentAmount, int $patientId): void
    {
        $context = $visit
            ? $this->buildPolicyContext($visit)
            : $this->resolvePatientBillingContext($patientId);

        if ($context['allows_partial_payment']) {
            if ($paymentAmount > $selectedTotal + 0.01) {
                throw new \InvalidArgumentException('Payment amount cannot exceed selected charges total');
            }
            return;
        }

        if (abs($paymentAmount - $selectedTotal) > 0.01) {
            throw new \InvalidArgumentException(
                'Full payment required for outpatient services. You must pay the full selected amount of GHS '
                . number_format($selectedTotal, 2)
            );
        }
    }

    public function getUnpaidInvoicesForPatient(int $patientId, ?int $branchId = null)
    {
        $query = Invoice::where('patient_id', $patientId)
            ->where('balance_amount', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->orderBy('due_date');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }
}
