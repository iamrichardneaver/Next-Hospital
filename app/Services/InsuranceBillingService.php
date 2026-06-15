<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InsurancePolicy;
use App\Models\InsuranceClaim;
use App\Models\PreAuthorization;
use App\Services\InsuranceCoverageService;
use Illuminate\Support\Facades\DB;

class InsuranceBillingService
{
    protected $insuranceCoverageService;

    public function __construct(InsuranceCoverageService $insuranceCoverageService)
    {
        $this->insuranceCoverageService = $insuranceCoverageService;
    }

    /**
     * Process billing with insurance coverage calculation.
     */
    public function processBillingWithInsurance($patientId, $invoiceItems, $serviceDate = null)
    {
        $serviceDate = $serviceDate ?: now()->toDateString();
        $patient = Patient::findOrFail($patientId);

        // Get patient's active insurance policies
        $activePolicies = $patient->insurancePolicies()
            ->where('is_active', true)
            ->where('start_date', '<=', $serviceDate)
            ->where('end_date', '>=', $serviceDate)
            ->orderBy('is_primary', 'desc')
            ->get();

        if ($activePolicies->isEmpty()) {
            return $this->processCashBilling($patientId, $invoiceItems, $serviceDate);
        }

        $totalAmount = collect($invoiceItems)->sum('total_amount');
        $coverageResults = [];
        $remainingAmount = $totalAmount;

        // Calculate coverage for each service
        foreach ($invoiceItems as $item) {
            $coverage = $this->insuranceCoverageService->calculateCoverage(
                $patientId,
                $item['service_type'],
                $item['service_code'] ?? null,
                $item['total_amount'],
                $serviceDate
            );

            $coverageResults[] = [
                'item' => $item,
                'coverage' => $coverage
            ];

            $remainingAmount -= $coverage['covered_amount'];
        }

        // Create invoice
        $invoice = $this->createInvoice($patientId, $invoiceItems, $coverageResults, $serviceDate);

        // Create insurance claims if coverage is available
        if ($remainingAmount < $totalAmount) {
            $this->createInsuranceClaims($invoice, $coverageResults, $activePolicies->first());
        }

        return [
            'invoice' => $invoice,
            'coverage_results' => $coverageResults,
            'total_amount' => $totalAmount,
            'covered_amount' => $totalAmount - $remainingAmount,
            'patient_co_pay' => $remainingAmount
        ];
    }

    /**
     * Process cash billing without insurance.
     */
    private function processCashBilling($patientId, $invoiceItems, $serviceDate)
    {
        $invoice = Invoice::create([
            'patient_id' => $patientId,
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => $serviceDate,
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => collect($invoiceItems)->sum('total_amount'),
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => collect($invoiceItems)->sum('total_amount'),
            'paid_amount' => 0,
            'balance_amount' => collect($invoiceItems)->sum('total_amount'),
            'payment_status' => 'pending',
            'billing_type' => 'cash',
            'created_by' => auth()->id()
        ]);

        // Create invoice items
        foreach ($invoiceItems as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'service_type' => $item['service_type'],
                'service_code' => $item['service_code'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_amount' => $item['total_amount'],
                'insurance_covered' => false,
                'covered_amount' => 0,
                'co_pay_amount' => $item['total_amount']
            ]);
        }

        return [
            'invoice' => $invoice,
            'coverage_results' => [],
            'total_amount' => $invoice->total_amount,
            'covered_amount' => 0,
            'patient_co_pay' => $invoice->total_amount
        ];
    }

    /**
     * Create invoice with insurance coverage.
     */
    private function createInvoice($patientId, $invoiceItems, $coverageResults, $serviceDate)
    {
        $subtotal = collect($invoiceItems)->sum('total_amount');
        $coveredAmount = collect($coverageResults)->sum(function($result) {
            return $result['coverage']['covered_amount'];
        });
        $patientCoPay = $subtotal - $coveredAmount;

        $invoice = Invoice::create([
            'patient_id' => $patientId,
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => $serviceDate,
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
            'paid_amount' => 0,
            'balance_amount' => $patientCoPay,
            'payment_status' => $patientCoPay > 0 ? 'pending' : 'paid',
            'billing_type' => 'insurance',
            'insurance_covered_amount' => $coveredAmount,
            'patient_co_pay_amount' => $patientCoPay,
            'created_by' => auth()->id()
        ]);

        // Create invoice items
        foreach ($coverageResults as $result) {
            $item = $result['item'];
            $coverage = $result['coverage'];

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'service_type' => $item['service_type'],
                'service_code' => $item['service_code'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_amount' => $item['total_amount'],
                'insurance_covered' => $coverage['has_coverage'],
                'covered_amount' => $coverage['covered_amount'],
                'co_pay_amount' => $coverage['co_pay_amount'],
                'coverage_percentage' => $coverage['coverage_percentage']
            ]);
        }

        return $invoice;
    }

    /**
     * Create insurance claims for covered services.
     */
    private function createInsuranceClaims($invoice, $coverageResults, $primaryPolicy)
    {
        $coveredItems = collect($coverageResults)->filter(function($result) {
            return $result['coverage']['has_coverage'] && $result['coverage']['covered_amount'] > 0;
        });

        if ($coveredItems->isEmpty()) {
            return;
        }

        $claimItems = $coveredItems->map(function($result) {
            $item = $result['item'];
            $coverage = $result['coverage'];
            
            return [
                'service_type' => $item['service_type'],
                'service_code' => $item['service_code'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_amount' => $item['total_amount'],
                'covered_amount' => $coverage['covered_amount'],
                'co_pay_amount' => $coverage['co_pay_amount']
            ];
        })->toArray();

        $totalClaimAmount = collect($claimItems)->sum('total_amount');
        $totalCoveredAmount = collect($claimItems)->sum('covered_amount');
        $totalCoPayAmount = collect($claimItems)->sum('co_pay_amount');

        $claim = InsuranceClaim::create([
            'patient_id' => $invoice->patient_id,
            'insurance_provider_id' => $primaryPolicy->insurance_provider_id,
            'policy_id' => $primaryPolicy->id,
            'invoice_id' => $invoice->id,
            'claim_number' => $this->generateClaimNumber(),
            'service_type' => 'mixed',
            'service_date' => $invoice->invoice_date,
            'total_amount' => $totalClaimAmount,
            'covered_amount' => $totalCoveredAmount,
            'co_pay_amount' => $totalCoPayAmount,
            'status' => 'draft',
            'claim_items' => $claimItems,
            'requires_pre_authorization' => $primaryPolicy->requires_pre_authorization,
            'created_by' => auth()->id()
        ]);

        // Create claim items
        foreach ($claimItems as $item) {
            $claim->claimItems()->create([
                'service_type' => $item['service_type'],
                'service_code' => $item['service_code'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_amount' => $item['total_amount'],
                'covered_amount' => $item['covered_amount'],
                'co_pay_amount' => $item['co_pay_amount']
            ]);
        }

        return $claim;
    }

    /**
     * Validate insurance coverage before service.
     */
    public function validateInsuranceCoverage($patientId, $serviceType, $serviceCode, $serviceAmount, $serviceDate = null)
    {
        return $this->insuranceCoverageService->validateCoverage(
            $patientId,
            $serviceType,
            $serviceCode,
            $serviceAmount,
            $serviceDate
        );
    }

    /**
     * Check if pre-authorization is required.
     */
    public function requiresPreAuthorization($patientId, $serviceType, $serviceCode, $serviceAmount, $serviceDate = null)
    {
        return $this->insuranceCoverageService->requiresPreAuthorization(
            $patientId,
            $serviceType,
            $serviceCode,
            $serviceAmount
        );
    }

    /**
     * Create pre-authorization request.
     */
    public function createPreAuthorizationRequest($patientId, $serviceType, $serviceCode, $serviceDescription, $requestedAmount, $urgency = 'routine')
    {
        return $this->insuranceCoverageService->createPreAuthorization(
            $patientId,
            $serviceType,
            $serviceCode,
            $serviceDescription,
            $requestedAmount,
            $urgency
        );
    }

    /**
     * Process insurance claim payment.
     */
    public function processClaimPayment($claimId, $processedAmount, $status = 'approved')
    {
        $claim = InsuranceClaim::findOrFail($claimId);
        
        $claim->update([
            'status' => $status,
            'processed_amount' => $processedAmount,
            'processed_date' => now()->toDateString(),
            'processed_by' => auth()->id()
        ]);

        // Update invoice if claim is approved
        if ($status === 'approved' && $claim->invoice_id) {
            $invoice = Invoice::find($claim->invoice_id);
            if ($invoice) {
                $newBalance = $invoice->balance_amount - $processedAmount;
                $invoice->update([
                    'balance_amount' => max(0, $newBalance),
                    'payment_status' => $newBalance <= 0 ? 'paid' : 'partial'
                ]);
            }
        }

        return $claim;
    }

    /**
     * Get patient insurance summary.
     */
    public function getPatientInsuranceSummary($patientId, $year = null)
    {
        $year = $year ?: now()->year;
        
        $patient = Patient::findOrFail($patientId);
        
        $policies = $patient->insurancePolicies()
            ->where('is_active', true)
            ->with('insuranceProvider')
            ->get();

        $claims = InsuranceClaim::where('patient_id', $patientId)
            ->whereYear('service_date', $year)
            ->get();

        $invoices = Invoice::where('patient_id', $patientId)
            ->where('billing_type', 'insurance')
            ->whereYear('invoice_date', $year)
            ->get();

        return [
            'patient' => $patient,
            'policies' => $policies,
            'claims' => $claims,
            'invoices' => $invoices,
            'summary' => [
                'total_claims' => $claims->count(),
                'total_claim_amount' => $claims->sum('total_amount'),
                'total_covered_amount' => $claims->sum('covered_amount'),
                'total_co_pay_amount' => $claims->sum('co_pay_amount'),
                'total_invoices' => $invoices->count(),
                'total_invoice_amount' => $invoices->sum('total_amount'),
                'total_paid_amount' => $invoices->sum('paid_amount'),
                'total_balance_amount' => $invoices->sum('balance_amount')
            ]
        ];
    }

    /**
     * Generate invoice number.
     */
    private function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $random;
    }

    /**
     * Generate claim number.
     */
    private function generateClaimNumber()
    {
        $prefix = 'CLM';
        $date = now()->format('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $random;
    }
}
