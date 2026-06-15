<?php

/**
 * Dry-run / trace user deletion logic (rolls back unless --commit passed).
 *
 * Usage:
 *   php scripts/test_user_deletion.php [user_id]
 *   php scripts/test_user_deletion.php 87 --commit
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Services\UserDeletionService;

$userId = isset($argv[1]) && is_numeric($argv[1]) ? (int) $argv[1] : null;
$commit = in_array('--commit', $argv, true);

$actor = User::role('super_admin')->first() ?? User::first();
if (!$actor) {
    echo "No users in database.\n";
    exit(1);
}

$service = app(UserDeletionService::class);

if ($userId) {
    $candidates = User::with(['roles', 'staffProfile', 'patient'])->where('id', $userId)->get();
} else {
    $candidates = User::with(['roles', 'staffProfile', 'patient'])
        ->where('id', '!=', $actor->id)
        ->limit(5)
        ->get();
}

echo "Actor: {$actor->email} (#{$actor->id})\n";
echo $commit ? "Mode: COMMIT\n\n" : "Mode: DRY-RUN (rollback)\n\n";

foreach ($candidates as $user) {
    echo str_repeat('-', 60) . "\n";
    echo "User #{$user->id} {$user->email}\n";
    echo 'Roles: ' . $user->roles->pluck('name')->join(', ') . "\n";
    echo 'Staff profile: ' . ($user->staffProfile ? 'yes' : 'no') . "\n";
    echo 'Patient link: ' . ($user->patient ? $user->patient->patient_number : 'no') . "\n";

    $block = $service->getBlockReason($user, $actor);
    if ($block) {
        echo "BLOCKED: {$block}\n";
        continue;
    }

    try {
        DB::beginTransaction();
        $summary = $service->deleteUser($user, $actor);
        echo 'SUCCESS: ' . $service->buildSuccessMessage($user, $summary) . "\n";
        echo 'Summary: ' . json_encode($summary) . "\n";

        if ($commit) {
            DB::commit();
            echo "Committed.\n";
        } else {
            DB::rollBack();
            echo "Rolled back (dry-run).\n";
        }
    } catch (Throwable $e) {
        DB::rollBack();
        echo 'FAILED: ' . $service->describeDeletionFailure($e) . "\n";
        echo $e->getMessage() . "\n";
    }
}

echo "\nDone.\n";
