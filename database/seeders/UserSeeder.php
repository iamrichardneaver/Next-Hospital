<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Patient;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\BranchAssignmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainBranch = Branch::find(Branch::getPrimaryClinicalBranchId()) ?? Branch::getDefault();
        if (!$mainBranch) {
            $this->command?->error('No branch found. UserSeeder cannot continue.');
            return;
        }

        $password = Hash::make('password123');

        $this->seedDemoStaffUser([
            'email' => 'admin@nexthospital.com',
            'role' => 'super_admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'employee_id' => 'EMP-001',
            'phone' => '+233-123-456-789',
            'specialization' => 'System Administration',
        ], $mainBranch, $password);

        $this->seedDemoStaffUser([
            'email' => 'doctor@nexthospital.com',
            'role' => 'doctor',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'employee_id' => 'EMP-002',
            'phone' => '+233-123-456-790',
            'specialization' => 'General Medicine',
            'license_number' => 'MD-001',
        ], $mainBranch, $password);

        $this->seedDemoStaffUser([
            'email' => 'nurse@nexthospital.com',
            'role' => 'nurse',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'employee_id' => 'EMP-003',
            'phone' => '+233-123-456-791',
            'specialization' => 'General Nursing',
        ], $mainBranch, $password);

        $this->seedDemoStaffUser([
            'email' => 'pharmacist@nexthospital.com',
            'role' => 'pharmacist',
            'first_name' => 'Mike',
            'last_name' => 'Johnson',
            'employee_id' => 'EMP-004',
            'phone' => '+233-123-456-792',
            'specialization' => 'Pharmacy',
            'license_number' => 'PHARM-001',
        ], $mainBranch, $password);

        $this->seedDemoStaffUser([
            'email' => 'receptionist@nexthospital.com',
            'role' => 'receptionist',
            'first_name' => 'Sarah',
            'last_name' => 'Wilson',
            'employee_id' => 'EMP-005',
            'phone' => '+233-123-456-793',
            'specialization' => 'Front Desk',
        ], $mainBranch, $password);

        $this->seedDemoStaffUser([
            'email' => 'lab@nexthospital.com',
            'role' => 'lab_technician',
            'first_name' => 'David',
            'last_name' => 'Brown',
            'employee_id' => 'EMP-006',
            'phone' => '+233-123-456-794',
            'specialization' => 'Laboratory Technology',
            'license_number' => 'LAB-001',
        ], $mainBranch, $password);

        $this->seedDemoStaffUser([
            'email' => 'lab.scientist@nexthospital.com',
            'role' => 'lab_scientist',
            'first_name' => 'Ruth',
            'last_name' => 'Mensah',
            'employee_id' => 'EMP-006B',
            'phone' => '+233-123-456-7941',
            'specialization' => 'Laboratory Science',
            'license_number' => 'LAB-SCI-001',
        ], $mainBranch, $password);

        $this->seedDemoStaffUser([
            'email' => 'cashier@nexthospital.com',
            'role' => 'cashier',
            'first_name' => 'Grace',
            'last_name' => 'Mensah',
            'employee_id' => 'EMP-007',
            'phone' => '+233-123-456-795',
            'specialization' => 'Cashier / Billing',
        ], $mainBranch, $password);

        $this->seedDemoStaffUser([
            'email' => 'accountant@nexthospital.com',
            'role' => 'accountant',
            'first_name' => 'Kwame',
            'last_name' => 'Asante',
            'employee_id' => 'EMP-008',
            'phone' => '+233-123-456-796',
            'specialization' => 'Hospital Accountant',
        ], $mainBranch, $password);

        $this->seedDemoPatientUser($mainBranch, $password);
    }

    private function seedDemoStaffUser(array $account, Branch $branch, string $password): void
    {
        $name = trim($account['first_name'] . ' ' . $account['last_name']);

        $user = User::firstOrCreate(
            ['email' => $account['email']],
            [
                'name' => $name,
                'first_name' => $account['first_name'],
                'last_name' => $account['last_name'],
                'password' => $password,
                'is_active' => true,
            ]
        );

        $user->syncRoles([$account['role']]);

        $profileData = [
            'branch_id' => $branch->id,
            'employee_id' => $account['employee_id'],
            'first_name' => $account['first_name'],
            'last_name' => $account['last_name'],
            'phone' => $account['phone'],
            'specialization' => $account['specialization'],
            'online_status' => 'online',
            'is_active' => true,
        ];

        if (!empty($account['license_number'])) {
            $profileData['license_number'] = $account['license_number'];
            $profileData['license_expiry'] = now()->addYear();
        }

        unset($profileData['employee_id']);

        $profile = StaffProfile::firstOrNew(['user_id' => $user->id]);
        if (!$profile->exists) {
            $profile->employee_id = $account['employee_id'];
        }
        $profile->fill($profileData);
        $profile->save();

        app(BranchAssignmentService::class)->syncStaffBranch($user, $branch->id);
    }

    private function seedDemoPatientUser(Branch $branch, string $password): void
    {
        $patientUser = User::firstOrCreate(
            ['email' => 'patient@nexthospital.com'],
            [
                'name' => 'John Patient',
                'first_name' => 'John',
                'last_name' => 'Patient',
                'password' => $password,
                'is_active' => true,
            ]
        );

        $patientUser->syncRoles(['patient']);

        $sanctumPatientRole = Role::where('name', 'patient')->where('guard_name', 'sanctum')->first();
        if ($sanctumPatientRole) {
            $exists = DB::table('model_has_roles')
                ->where('role_id', $sanctumPatientRole->id)
                ->where('model_type', User::class)
                ->where('model_id', $patientUser->id)
                ->exists();

            if (!$exists) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $sanctumPatientRole->id,
                    'model_type' => User::class,
                    'model_id' => $patientUser->id,
                ]);
            }
        }

        $adminId = User::where('email', 'admin@nexthospital.com')->value('id');

        Patient::updateOrCreate(
            ['user_id' => $patientUser->id],
            [
                'first_name' => 'John',
                'last_name' => 'Patient',
                'gender' => 'Male',
                'date_of_birth' => '1990-01-15',
                'phone' => '+233-200-000-001',
                'email' => 'patient@nexthospital.com',
                'address' => 'Demo Street, Accra',
                'branch_id' => $branch->id,
                'registration_source' => 'web',
                'account_status' => 'active',
                'account_activated_at' => now(),
                'created_by' => $adminId ?? $patientUser->id,
            ]
        );
    }
}
