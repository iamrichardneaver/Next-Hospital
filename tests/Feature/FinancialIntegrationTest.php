<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RevenueTransaction;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FinancialIntegrationTest extends TestCase
{
    /**
     * Test invoice creation initializes payment tracking fields.
     */
    public function test_invoice_creation_initializes_payment_tracking()
    {
        $patient = Patient::factory()->create();
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [
                ['description' => 'Test Service', 'quantity' => 1, 'unit_price' => 100, 'total' => 100, 'service_type' => 'consultation']
            ],
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'status' => 'pending',
            'created_by' => $user->id
        ]);
        
        $invoice->refresh();
        
        // Assert InvoiceObserver initialized fields
        $this->assertEquals(0, $invoice->paid_amount);
        $this->assertEquals(100, $invoice->balance_amount);
        $this->assertEquals('unpaid', $invoice->payment_status);
        
        // Revenue is recorded when payment completes, not on invoice creation
        $this->assertDatabaseMissing('revenue_transactions', [
            'source_type' => Invoice::class,
            'source_id' => $invoice->id,
        ]);
    }

    /**
     * Test payment recording updates invoice automatically.
     */
    public function test_payment_updates_invoice_automatically()
    {
        $patient = Patient::factory()->create();
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [],
            'subtotal' => 200,
            'total_amount' => 200,
            'created_by' => $user->id
        ]);
        
        // Record payment using PaymentService
        $paymentService = app(PaymentService::class);
        $result = $paymentService->recordPayment(
            $invoice->id,
            100, // Pay half
            'cash',
            ['processed_by' => $user->id]
        );
        
        $this->assertTrue($result['success']);
        
        $invoice->refresh();
        
        // Assert automatic updates
        $this->assertEquals(100, $invoice->paid_amount);
        $this->assertEquals(100, $invoice->balance_amount);
        $this->assertEquals('partial', $invoice->payment_status);
        
        // Pay remaining
        $result2 = $paymentService->recordPayment(
            $invoice->id,
            100,
            'cash',
            ['processed_by' => $user->id]
        );
        
        $invoice->refresh();
        
        $this->assertEquals(200, $invoice->paid_amount);
        $this->assertEquals(0, $invoice->balance_amount);
        $this->assertEquals('paid', $invoice->payment_status);
        $this->assertEquals('paid', $invoice->status);
    }

    /**
     * Test payment includes patient_id and branch_id.
     */
    public function test_payment_includes_patient_and_branch()
    {
        $patient = Patient::factory()->create();
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'invoice_date' => now()->toDateString(),
            'items' => [],
            'total_amount' => 50,
            'created_by' => $user->id
        ]);
        
        $paymentService = app(PaymentService::class);
        $result = $paymentService->recordPayment(
            $invoice->id,
            50,
            'cash'
        );
        
        $payment = $result['payment'];
        
        $this->assertEquals($patient->id, $payment->patient_id);
        $this->assertEquals($branch->id, $payment->branch_id);
    }

    /**
     * Test revenue transaction is created for each payment.
     */
    public function test_revenue_transaction_created_for_payment()
    {
        $patient = Patient::factory()->create();
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'invoice_date' => now()->toDateString(),
            'items' => [],
            'total_amount' => 75,
            'created_by' => $user->id
        ]);
        
        $paymentService = app(PaymentService::class);
        $result = $paymentService->recordPayment(
            $invoice->id,
            75,
            'momo'
        );
        
        // Check revenue transaction for payment
        $this->assertDatabaseHas('revenue_transactions', [
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'source_type' => Payment::class,
            'source_id' => $result['payment']->id,
            'amount' => 75,
            'payment_method' => 'momo',
            'status' => 'completed'
        ]);
    }

    /**
     * Test API payment endpoint consistency.
     */
    public function test_api_payment_endpoint_consistency()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $branch = Branch::factory()->create(['id' => 1]);
        $user->staffProfile()->create(['branch_id' => 1]);
        
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'invoice_date' => now()->toDateString(),
            'items' => [],
            'total_amount' => 150,
            'created_by' => $user->id
        ]);
        
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cashier/process-payment', [
            'invoice_id' => $invoice->id,
            'amount' => 150,
            'payment_method' => 'cash',
            'reference_number' => 'TEST123'
        ]);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'invoice' => [
                    'paid_amount',
                    'balance_amount',
                    'payment_status'
                ]
            ]);
        
        $this->assertEquals(150, $response->json('invoice.paid_amount'));
        $this->assertEquals(0, $response->json('invoice.balance_amount'));
        $this->assertEquals('paid', $response->json('invoice.payment_status'));
    }
}

