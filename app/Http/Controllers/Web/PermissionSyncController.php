<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PermissionAutoSync;
use App\Services\PermissionSyncService;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionSyncController extends Controller
{
    public function index(): View
    {
        $this->ensureSuperAdmin();

        $registryTotal = count(PermissionRegistry::definitions());
        $configTotal = count(PermissionRegistry::configDefinitions());
        $discoveredTotal = count(PermissionRegistry::discoveredNames());
        $configDuplicates = PermissionRegistry::configDuplicateMap();
        $dbTotal = Permission::where('guard_name', PermissionRegistry::guard())->count();
        $lastSync = PermissionAutoSync::lastManualSyncMeta();

        return view('settings.permissions-sync', compact(
            'registryTotal',
            'configTotal',
            'discoveredTotal',
            'configDuplicates',
            'dbTotal',
            'lastSync'
        ));
    }

    public function sync(PermissionSyncService $syncService): RedirectResponse
    {
        $user = auth()->user();
        $this->ensureSuperAdmin();

        $result = $syncService->sync();

        PermissionAutoSync::markSynced();
        PermissionAutoSync::recordManualSync(
            $result,
            (int) $user->id,
            (string) ($user->name ?? $user->email ?? 'super_admin')
        );

        Log::info('Permissions manually synced from admin UI.', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'created' => $result['created'],
            'existing' => $result['existing'],
            'total' => $result['total'],
            'discovered' => $result['discovered'],
        ]);

        $message = sprintf(
            'Permission sync complete. Created %d new permission(s), %d already existed (%d in registry: %d from config, %d discovered in codebase).',
            $result['created'],
            $result['existing'],
            $result['total'],
            $result['from_config'],
            $result['discovered']
        );

        if ($result['config_duplicates'] !== []) {
            $duplicateCount = count($result['config_duplicates']);
            $message .= sprintf(
                ' %d duplicate name(s) in config were prevented from creating extra rows (first module entry wins).',
                $duplicateCount
            );
        }

        return redirect()
            ->route('settings.permissions-sync')
            ->with('success', $message);
    }

    private function ensureSuperAdmin(): void
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only super administrators can sync permissions.');
        }
    }
}
