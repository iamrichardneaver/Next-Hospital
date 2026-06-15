<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

/**
 * One-time registration fee: when enabled, a fee is added as an invoice for every new patient.
 * Admins set the amount and "apply to all new patients" in System Settings (Registration Fee).
 * Cashier sees the invoice when the patient pays.
 */
class RegistrationFeeService
{
    public const REGISTRATION_FEE_SERVICE_TYPE = 'registration_fee';

    /**
     * Get registration fee config from system settings.
     */
    public function getConfig(): array
    {
        $settings = SystemSetting::current();
        $amount = (float) ($settings->registration_fee ?? 0);
        $apply = (bool) ($settings->registration_fee_apply_to_new_patients ?? true);
        $currency = $settings->currency ?? 'GHS';

        return [
            'amount' => $amount,
            'currency' => $currency,
            'apply_to_new_patients' => $apply,
        ];
    }

    /**
     * Whether to automatically add registration fee invoice for new patients.
     */
    public function shouldApply(): bool
    {
        $config = $this->getConfig();
        return $config['apply_to_new_patients'] && $config['amount'] > 0;
    }

    /**
     * Check if patient already has a registration fee invoice (pending or paid).
     */
    public function patientHasRegistrationFeeInvoice(int $patientId): bool
    {
        $invoices = Invoice::where('patient_id', $patientId)->get();
        foreach ($invoices as $inv) {
            $items = $inv->items;
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (($item['service_type'] ?? '') === self::REGISTRATION_FEE_SERVICE_TYPE) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Create a one-time registration fee invoice for the patient if applicable.
     * Call this after a new patient is created. Idempotent: skips if fee disabled or already exists.
     *
     * @param Patient $patient
     * @param int|null $branchId Default from patient's branch_id
     * @return Invoice|null Created invoice or null if skipped
     */
    public function createInvoiceForPatient(Patient $patient, ?int $branchId = null): ?Invoice
    {
        if (!$this->shouldApply()) {
            return null;
        }

        if ($this->patientHasRegistrationFeeInvoice($patient->id)) {
            Log::debug('RegistrationFeeService: patient already has registration fee invoice', ['patient_id' => $patient->id]);
            return null;
        }

        $config = $this->getConfig();
        $branchId = $branchId ?? $patient->branch_id ?? 1;

        $invoiceService = app(InvoiceService::class);
        $items = [
            [
                'description' => 'Registration Fee (one-time)',
                'quantity' => 1,
                'unit_price' => $config['amount'],
                'service_type' => self::REGISTRATION_FEE_SERVICE_TYPE,
            ],
        ];

        $invoice = $invoiceService->createInvoice(
            $patient->id,
            $branchId,
            $items,
            [
                'notes' => 'One-time registration fee. Applied to all new patients.',
                'created_by' => auth()->id(),
            ]
        );

        Log::info('Registration fee invoice created for new patient', [
            'patient_id' => $patient->id,
            'invoice_id' => $invoice->id,
            'amount' => $config['amount'],
        ]);

        return $invoice;
    }
}
