<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\AccountingReportService;
use App\Services\IdPrefixService;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branch;

    protected ExpenseCategory $operationalCategory;

    protected ExpenseCategory $inventoryCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::where('is_active', true)->first()
            ?? Branch::factory()->create(['is_active' => true]);

        (new ExpenseCategorySeeder())->run();
        app(IdPrefixService::class)->getOrCreateSetting('expense', [
            'module_prefix' => 'EXP',
            'description' => 'ID pattern for operating expenses',
        ]);

        $this->operationalCategory = ExpenseCategory::where('code', 'DEPT_MISC')->firstOrFail();
        $this->inventoryCategory = ExpenseCategory::where('code', 'PHARM_STOCK')->firstOrFail();

        foreach (['create_expenses', 'view_own_expenses', 'approve_expenses', 'manage_expenses', 'view_expenses'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (['pharmacist', 'accountant', 'admin'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_staff_submission_creates_pending_expense(): void
    {
        $pharmacist = $this->createPharmacist();

        $response = $this->actingAs($pharmacist)->post('/expenses/submit', [
            'category_id' => $this->operationalCategory->id,
            'department' => 'pharmacy',
            'amount' => 75.50,
            'expense_date' => now()->toDateString(),
            'description' => 'Department petty cash purchase',
            'status' => 'approved',
        ]);

        $response->assertRedirect(route('expenses.my'));

        $this->assertDatabaseHas('expenses', [
            'created_by' => $pharmacist->id,
            'department' => 'pharmacy',
            'status' => 'pending',
            'amount' => 75.50,
        ]);
    }

    public function test_self_approval_is_blocked(): void
    {
        $accountant = $this->createAccountant();
        $expense = $this->createExpense([
            'created_by' => $accountant->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($accountant)->post('/accounting/expenses/' . $expense->id . '/approve');

        $response->assertForbidden();
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => 'pending',
        ]);
    }

    public function test_cross_department_submit_is_blocked(): void
    {
        $pharmacist = $this->createPharmacist();

        $response = $this->actingAs($pharmacist)->post('/expenses/submit', [
            'category_id' => $this->operationalCategory->id,
            'department' => 'lab',
            'amount' => 40.00,
            'expense_date' => now()->toDateString(),
            'description' => 'Cross-department attempt',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('expenses', [
            'description' => 'Cross-department attempt',
        ]);
    }

    public function test_inventory_category_rejected_on_staff_submit(): void
    {
        $pharmacist = $this->createPharmacist();
        $countBefore = Expense::count();

        $response = $this->actingAs($pharmacist)->post('/expenses/submit', [
            'category_id' => $this->inventoryCategory->id,
            'department' => 'pharmacy',
            'amount' => 100.00,
            'expense_date' => now()->toDateString(),
            'description' => 'Manual inventory expense attempt',
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertEquals($countBefore, Expense::count());
    }

    public function test_pending_expenses_excluded_from_total_expenses(): void
    {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->toDateString();
        $creator = User::factory()->create();
        $service = app(AccountingReportService::class);
        $before = $service->getTotalExpenses($this->branch->id, $startDate, $endDate);

        $this->createExpense([
            'branch_id' => $this->branch->id,
            'created_by' => $creator->id,
            'status' => 'approved',
            'amount' => 200.00,
            'description' => 'Approved expense workflow test ' . uniqid(),
        ]);

        $this->createExpense([
            'branch_id' => $this->branch->id,
            'created_by' => $creator->id,
            'status' => 'pending',
            'amount' => 500.00,
            'description' => 'Pending expense workflow test ' . uniqid(),
        ]);

        $after = $service->getTotalExpenses($this->branch->id, $startDate, $endDate);

        $this->assertEquals($before + 200.0, $after);
    }

    protected function createPharmacist(): User
    {
        $user = User::factory()->create();
        $user->assignRole('pharmacist');
        $user->givePermissionTo(['create_expenses', 'view_own_expenses']);
        $this->attachStaffProfile($user, 'EXP-TEST-' . $user->id);

        return $user;
    }

    protected function createAccountant(): User
    {
        $user = User::factory()->create();
        $user->assignRole('accountant');
        $user->givePermissionTo(['approve_expenses', 'view_expenses', 'manage_expenses']);
        $this->attachStaffProfile($user, 'EXP-ACCT-' . $user->id);

        return $user;
    }

    protected function attachStaffProfile(User $user, string $employeeId): void
    {
        $user->staffProfile()->create([
            'branch_id' => $this->branch->id,
            'employee_id' => $employeeId,
            'first_name' => 'Test',
            'last_name' => 'User',
            'is_active' => true,
        ]);
    }

    protected function createExpense(array $overrides = []): Expense
    {
        $status = $overrides['status'] ?? 'pending';
        unset($overrides['status']);

        $expense = Expense::create(array_merge([
            'category_id' => $this->operationalCategory->id,
            'branch_id' => $this->branch->id,
            'department' => 'pharmacy',
            'amount' => 100.00,
            'expense_date' => now()->toDateString(),
            'description' => 'Test expense',
            'created_by' => User::factory()->create()->id,
        ], $overrides));
        $expense->status = $status;
        $expense->save();

        return $expense->fresh();
    }
}
