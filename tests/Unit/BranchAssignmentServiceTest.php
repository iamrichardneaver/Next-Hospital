<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\FacilityUser;
use App\Models\User;
use App\Services\BranchAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'doctor', 'guard_name' => 'web']);
        Role::create(['name' => 'patient', 'guard_name' => 'web']);
    }

    public function test_assign_user_to_branch_creates_facility_users_and_staff_profile(): void
    {
        $branch = Branch::create([
            'name' => 'Main Hospital',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Test Doctor',
            'first_name' => 'Test',
            'last_name' => 'Doctor',
            'email' => 'testdoctor@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole('doctor');

        $service = app(BranchAssignmentService::class);
        $assignedBranchId = $service->assignUserToBranch($user, $branch->id);

        $user->refresh();

        $this->assertSame($branch->id, $assignedBranchId);
        $this->assertDatabaseHas('facility_users', [
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $this->assertSame($branch->id, $user->staffProfile->branch_id);
        $this->assertTrue($user->branches()->where('branches.id', $branch->id)->exists());
    }

    public function test_resolve_branch_uses_default_main_branch(): void
    {
        $main = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'is_active' => true]);
        Branch::create(['name' => 'Other', 'code' => 'OTHER', 'is_active' => true]);

        $resolved = app(BranchAssignmentService::class)->resolveBranchId();

        $this->assertSame($main->id, $resolved);
    }

    public function test_patient_role_does_not_require_branch_assignment(): void
    {
        $user = User::create([
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('patient');

        $service = app(BranchAssignmentService::class);

        $this->assertFalse($service->isStaffRole('patient'));
        $this->expectException(\InvalidArgumentException::class);
        $service->assignUserToBranch($user);
    }
}
