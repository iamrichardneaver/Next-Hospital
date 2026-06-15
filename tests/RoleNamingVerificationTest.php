<?php

/**
 * Role Naming Standardization Verification Test
 * 
 * This script verifies that role name standardization did not break RBAC functionality
 * 
 * Run: php backend/tests/RoleNamingVerificationTest.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "   ROLE NAMING STANDARDIZATION - VERIFICATION TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

$allPassed = true;

// TEST 1: Verify all role names follow convention
echo "TEST 1: Role Naming Convention\n";
echo "-----------------------------------------------------\n";

$roles = \Spatie\Permission\Models\Role::all();
$incorrectRoles = [];

foreach ($roles as $role) {
    // Check if role name follows lowercase_with_underscores pattern
    if (!preg_match('/^[a-z]+(_[a-z]+)*$/', $role->name)) {
        $incorrectRoles[] = $role->name;
    }
}

if (empty($incorrectRoles)) {
    echo "✅ PASS: All {$roles->count()} roles follow lowercase_with_underscores convention\n";
} else {
    echo "❌ FAIL: " . count($incorrectRoles) . " role(s) have incorrect format:\n";
    foreach ($incorrectRoles as $roleName) {
        echo "   - {$roleName}\n";
    }
    $allPassed = false;
}
echo "\n";

// TEST 2: Verify users are still assigned to roles
echo "TEST 2: User Role Assignments\n";
echo "-----------------------------------------------------\n";

$totalUsers = \App\Models\User::count();
$usersWithRoles = DB::table('model_has_roles')
    ->distinct('model_id')
    ->count('model_id');

if ($usersWithRoles > 0) {
    echo "✅ PASS: {$usersWithRoles} out of {$totalUsers} users have role assignments\n";
} else {
    echo "❌ FAIL: No users have role assignments!\n";
    $allPassed = false;
}
echo "\n";

// TEST 3: Verify super_admin has all permissions
echo "TEST 3: Super Admin Permissions\n";
echo "-----------------------------------------------------\n";

$superAdminRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
$totalPermissions = \Spatie\Permission\Models\Permission::count();

if ($superAdminRole) {
    $superAdminPermCount = $superAdminRole->permissions()->count();
    if ($superAdminPermCount === $totalPermissions) {
        echo "✅ PASS: super_admin has all {$totalPermissions} permissions\n";
    } else {
        echo "❌ FAIL: super_admin has {$superAdminPermCount} permissions, expected {$totalPermissions}\n";
        $allPassed = false;
    }
} else {
    echo "❌ FAIL: super_admin role not found!\n";
    $allPassed = false;
}
echo "\n";

// TEST 4: Verify User model helper methods work
echo "TEST 4: User Model Helper Methods\n";
echo "-----------------------------------------------------\n";

$testsPassed = 0;
$totalTests = 0;

// Test with super_admin user
$superAdminUser = \App\Models\User::role('super_admin')->first();
if ($superAdminUser) {
    $totalTests++;
    if ($superAdminUser->hasRole('super_admin') && $superAdminUser->isSuperAdmin()) {
        $testsPassed++;
        echo "✅ super_admin user: hasRole() and isSuperAdmin() work\n";
    } else {
        echo "❌ super_admin user: hasRole() or isSuperAdmin() failed\n";
        $allPassed = false;
    }
}

// Test with doctor user
$doctorUser = \App\Models\User::role('doctor')->first();
if ($doctorUser) {
    $totalTests++;
    if ($doctorUser->hasRole('doctor') && $doctorUser->isDoctor()) {
        $testsPassed++;
        echo "✅ doctor user: hasRole() and isDoctor() work\n";
    } else {
        echo "❌ doctor user: hasRole() or isDoctor() failed\n";
        $allPassed = false;
    }
}

// Test with nurse user
$nurseUser = \App\Models\User::role('nurse')->first();
if ($nurseUser) {
    $totalTests++;
    if ($nurseUser->hasRole('nurse') && $nurseUser->isNurse()) {
        $testsPassed++;
        echo "✅ nurse user: hasRole() and isNurse() work\n";
    } else {
        echo "❌ nurse user: hasRole() or isNurse() failed\n";
        $allPassed = false;
    }
}

if ($testsPassed === $totalTests) {
    echo "✅ PASS: All {$totalTests} user helper method tests passed\n";
} else {
    echo "❌ FAIL: {$testsPassed}/{$totalTests} user helper method tests passed\n";
    $allPassed = false;
}
echo "\n";

// TEST 5: Verify permission checks work
echo "TEST 5: Permission Checks\n";
echo "-----------------------------------------------------\n";

if ($superAdminUser) {
    // Super admin should have all permissions
    $canViewDashboard = $superAdminUser->can('view_dashboard');
    $canManageRoles = $superAdminUser->can('manage_roles');
    $canViewPatients = $superAdminUser->can('view_patients');
    
    if ($canViewDashboard && $canManageRoles && $canViewPatients) {
        echo "✅ PASS: super_admin permission checks work correctly\n";
    } else {
        echo "❌ FAIL: super_admin permission checks failed\n";
        echo "   - view_dashboard: " . ($canViewDashboard ? 'YES' : 'NO') . "\n";
        echo "   - manage_roles: " . ($canManageRoles ? 'YES' : 'NO') . "\n";
        echo "   - view_patients: " . ($canViewPatients ? 'YES' : 'NO') . "\n";
        $allPassed = false;
    }
}

if ($doctorUser) {
    // Doctor should have create_consultations
    $canCreateConsultations = $doctorUser->can('create_consultations');
    
    if ($canCreateConsultations) {
        echo "✅ PASS: doctor permission checks work correctly\n";
    } else {
        echo "❌ FAIL: doctor should have create_consultations permission\n";
        $allPassed = false;
    }
}
echo "\n";

// TEST 6: Verify no orphaned users
echo "TEST 6: Orphaned Users Check\n";
echo "-----------------------------------------------------\n";

$usersWithoutRoles = \App\Models\User::whereDoesntHave('roles')->get();

if ($usersWithoutRoles->isEmpty()) {
    echo "✅ PASS: No users without role assignments\n";
} else {
    echo "⚠️  WARNING: {$usersWithoutRoles->count()} user(s) have no role assignments\n";
    foreach ($usersWithoutRoles as $user) {
        echo "   - {$user->email}\n";
    }
}
echo "\n";

// FINAL RESULT
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
if ($allPassed) {
    echo "   ✅ ALL TESTS PASSED - RBAC SYSTEM WORKING CORRECTLY\n";
} else {
    echo "   ❌ SOME TESTS FAILED - PLEASE REVIEW ABOVE\n";
}
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

exit($allPassed ? 0 : 1);
