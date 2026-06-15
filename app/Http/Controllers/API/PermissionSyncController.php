<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PermissionAutoSync;
use App\Services\PermissionSyncService;
use App\Support\PermissionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class PermissionSyncController extends Controller
{
    public function status(): JsonResponse
    {
        $this->ensureSuperAdmin();

        return response()->json([
            'success' => true,
            'data' => [
                'registry_total' => count(PermissionRegistry::definitions()),
                'config_total' => count(PermissionRegistry::configDefinitions()),
                'discovered_total' => count(PermissionRegistry::discoveredNames()),
                'config_duplicates' => PermissionRegistry::configDuplicateMap(),
                'db_total' => Permission::where('guard_name', PermissionRegistry::guard())->count(),
                'last_sync' => PermissionAutoSync::lastManualSyncMeta(),
            ],
        ]);
    }

    public function sync(Request $request, PermissionSyncService $syncService): JsonResponse
    {
        $user = $request->user();
        $this->ensureSuperAdmin();

        $result = $syncService->sync();

        PermissionAutoSync::markSynced();
        PermissionAutoSync::recordManualSync(
            $result,
            (int) $user->id,
            (string) ($user->name ?? $user->email ?? 'super_admin')
        );

        Log::info('Permissions manually synced via API.', [
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

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result,
        ]);
    }

    private function ensureSuperAdmin(): void
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only super administrators can sync permissions.');
        }
    }
}
