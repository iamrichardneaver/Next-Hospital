<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\RevenueTransaction;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\RevenueReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RevenueDashboardScopingTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected Patient $patient;
    protected User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create();
        $this->patient = Patient::factory()->create(['branch_id' => $this->branch->id]);
        $this->actor = User::factory()->create();
        $this->actor->staffProfile()->create(['branch_id' => $this->branch->id]);

        Permission::findOrCreate('view_dashboard', 'web');
        Permission::findOrCreate('dispense_drugs', 'web');
        Permission::findOrCreate('view_prescriptions', 'web');
        Permission::findOrCreate('view_financial_reports', 'web');
        Permission::findOrCreate('view_invoices', 'web');

        Role::findOrCreate('pharmacist', 'web');
        Role::findOrCreate('admin', 'web');
    }

    public function test_admin_dashboard_uses_all_time_revenue_transactions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo(['view_dashboard', 'view_invoices', 'view_financial_reports']);

        $this->createCompletedPayment(100, 'pharmacy', Carbon::yesterday()->toDateString());
        $this->createCompletedPayment(50, 'lab', Carbon::today()->toDateString());

        $stats = app(RevenueReportService::class)->getDashboardRevenue($admin, $this->branch->id);

        $this->assertTrue($stats['revenue_visible']);
        $this->assertSame('all_time', $stats['revenue_scope']);
        $this->assertEquals(150.0, $stats['revenue_amount']);
        $this->assertEquals(50.0, $stats['today_revenue']);
    }

    public function test_pharmacist_sees_only_todays_pharmacy_revenue(): void
    {
        $pharmacist = User::factory()->create();
        $pharmacist->assignRole('pharmacist');
        $pharmacist->givePermissionTo(['view_dashboard', 'dispense_drugs', 'view_prescriptions']);

        $this->createCompletedPayment(80, 'pharmacy', Carbon::today()->toDateString());
        $this->createCompletedPayment(120, 'lab', Carbon::today()->toDateString());
        $this->createCompletedPayment(40, 'pharmacy', Carbon::yesterday()->toDateString());

        $stats = app(RevenueReportService::class)->getDashboardRevenue($pharmacist, $this->branch->id);

        $this->assertTrue($stats['revenue_visible']);
        $this->assertSame('today_module', $stats['revenue_scope']);
        $this->assertEquals(80.0, $stats['revenue_amount']);
        $this->assertStringContainsString('Pharmacy', $stats['revenue_label']);
    }

    public function test_nurse_without_billing_permissions_sees_no_revenue(): void
    {
        $nurse = User::factory()->create();
        $nurse->givePermissionTo('view_dashboard');

        $this->createCompletedPayment(200, 'consultation', Carbon::today()->toDateString());

        $stats = app(RevenueReportService::class)->getDashboardRevenue($nurse, $this->branch->id);

        $this->assertFalse($stats['revenue_visible']);
    }

    public function test_admin_total_is_at_least_sum_of_service_streams(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo(['view_dashboard', 'view_financial_reports']);

        $today = Carbon::today()->toDateString();
        $this->createCompletedPayment(30, 'pharmacy', $today);
        $this->createCompletedPayment(70, 'lab', $today);

        $service = app(RevenueReportService::class);
        $composition = $service->getRevenueComposition($this->branch->id, $today, $today);
        $streamSum = array_sum(array_column($composition, 'total'));
        $dashboard = $service->getDashboardRevenue($admin, $this->branch->id);

        $this->assertGreaterThanOrEqual($streamSum, $dashboard['revenue_amount']);
    }

    protected function createCompletedPayment(float $amount, string $serviceType, string $date): Payment
    {
        $this->actingAs($this->actor);

        $invoice = Invoice::create([
            'patient_id' => $this->patient->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => $date,
            'due_date' => Carbon::parse($date)->addDays(30)->toDateString(),
            'items' => [
                [
                    'description' => 'Test',
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'total' => $amount,
                    'service_type' => $serviceType === 'imaging' ? 'radiology' : $serviceType,
                ],
            ],
            'subtotal' => $amount,
            'total_amount' => $amount,
            'created_by' => $this->actor->id,
        ]);

        $result = app(PaymentService::class)->recordPayment(
            $invoice->id,
            $amount,
            'cash',
            ['processed_by' => $this->actor->id, 'payment_date' => $date]
        );

        $payment = $result['payment'];
        RevenueTransaction::where('source_type', Payment::class)
            ->where('source_id', $payment->id)
            ->update(['transaction_date' => $date, 'service_type' => $serviceType]);

        return $payment->fresh();
    }
}
