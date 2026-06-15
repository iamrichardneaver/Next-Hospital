<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    use ExportsListData;

    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::forWebAdmin()
            ->with('permissions')
            ->withCount('users')
            ->latest('id')
            ->get();

        $statistics = [
            'total_roles' => $roles->count(),
            'total_permissions' => Permission::count(),
            'total_users_with_roles' => DB::table('model_has_roles')->distinct('model_id')->count('model_id'),
        ];

        return view('roles.index', compact('roles', 'statistics'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get();

        $groupedPermissions = $permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);
            $module = implode('_', array_slice($parts, 1));

            return ucwords(str_replace('_', ' ', $module));
        });

        return view('roles.create', compact('permissions', 'groupedPermissions'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

            if ($request->has('permissions') && is_array($request->permissions)) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->givePermissionTo($permissions);
            }

            DB::commit();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return redirect()->route('roles.index')
                ->with('success', 'Role created successfully!');
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->with('error', 'Error creating role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users']);

        $statistics = [
            'users_count' => $role->getAssignedUsersCount(),
            'permissions_count' => $role->getValidPermissions()->count(),
        ];

        $groupedPermissions = $role->getValidPermissions()->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);
            $module = implode('_', array_slice($parts, 1));

            return ucwords(str_replace('_', ' ', $module));
        });

        return view('roles.show', compact('role', 'statistics', 'groupedPermissions'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        if ($role->isProtected()) {
            return redirect()->route('roles.show', $role)
                ->with('error', "Cannot edit protected system role \"{$role->displayName()}\".");
        }

        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        $groupedPermissions = $permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);
            $module = implode('_', array_slice($parts, 1));

            return ucwords(str_replace('_', ' ', $module));
        });

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions', 'groupedPermissions'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        if ($role->isProtected()) {
            return redirect()->back()
                ->with('error', "Cannot modify protected system role \"{$role->displayName()}\".");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            $role->update([
                'name' => $validated['name'],
            ]);

            if ($request->has('permissions') && is_array($request->permissions)) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions([]);
            }

            DB::commit();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return redirect()->route('roles.show', $role)
                ->with('success', 'Role updated successfully!');
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->with('error', 'Error updating role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        $blockReason = $role->getDeletionBlockReason();

        if ($blockReason) {
            return redirect()->back()->with('error', $blockReason);
        }

        DB::beginTransaction();
        try {
            $role->syncPermissions([]);
            $role->delete();

            DB::commit();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return redirect()->route('roles.index')
                ->with('success', 'Role deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Error deleting role: ' . $e->getMessage());
        }
    }

    /**
     * Display permissions management page.
     */
    public function permissions()
    {
        $permissions = Permission::where('guard_name', config('permissions.guard', 'web'))
            ->orderBy('name')
            ->get();

        $nameToModule = \App\Support\PermissionRegistry::nameToModuleLabel();

        $groupedPermissions = $permissions->groupBy(function ($permission) use ($nameToModule) {
            if (isset($nameToModule[$permission->name])) {
                return $nameToModule[$permission->name];
            }

            return ucwords(str_replace('_', ' ', \App\Support\PermissionModuleGuesser::guess($permission->name)));
        })->sortKeys();

        $statistics = [
            'total_permissions' => $permissions->count(),
            'total_roles' => Role::forWebAdmin()->count(),
        ];

        $descriptions = \App\Support\PermissionRegistry::definitions();

        return view('roles.permissions', compact('permissions', 'groupedPermissions', 'statistics', 'descriptions'));
    }

    public function export(Request $request)
    {
        $roles = Role::forWebAdmin()
            ->with('permissions')
            ->withCount('users')
            ->latest('id')
            ->get();

        return $this->exportTableData($request, $roles, [
            'Role' => 'name',
            'Permissions' => fn ($r) => $r->getValidPermissions()->pluck('name')->join(', '),
            'Users Count' => 'users_count',
            'Guard' => 'guard_name',
        ], 'roles', 'manage_roles');
    }
}
