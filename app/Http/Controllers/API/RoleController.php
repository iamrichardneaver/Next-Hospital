<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        $query = Role::with('permissions');

        // Search by name
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $roles = $query->orderBy('name')->get();

        // Transform the data to include the counts in the expected format
        $transformedRoles = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description ?? '',
                'users_count' => $role->users()->count(),
                'permissions_count' => $role->permissions()->count(),
                'created_at' => $role->created_at,
                'permissions' => $role->permissions
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedRoles,
            'message' => 'Roles retrieved successfully'
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

            // Assign permissions
            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('name', $request->permissions)->where('guard_name', 'web')->get();
                $role->givePermissionTo($permissions);
            }

            DB::commit();
            
            // CRITICAL: Clear permission cache AFTER commit to ensure changes take effect for all users
            // This ensures cache is only cleared if transaction succeeded
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'success' => true,
                'data' => $role->load('permissions'),
                'message' => 'Role created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified role.
     */
    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $role,
            'message' => 'Role retrieved successfully'
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update role name
            if ($request->has('name')) {
                $role->update(['name' => $request->name]);
            }

            // Update permissions
            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('name', $request->permissions)->where('guard_name', 'web')->get();
                $role->syncPermissions($permissions);
            }

            DB::commit();
            
            // CRITICAL: Clear permission cache AFTER commit to ensure changes take effect for all users with this role
            // This ensures cache is only cleared if transaction succeeded
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'success' => true,
                'data' => $role->load('permissions'),
                'message' => 'Role updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error updating role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        
        // Prevent deletion of super admin role
        if ($role->name === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete super admin role'
            ], 403);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Get all permissions.
     */
    public function getPermissions()
    {
        $permissions = Permission::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $permissions,
            'message' => 'Permissions retrieved successfully'
        ]);
    }

    /**
     * Get permissions grouped by category.
     */
    public function getPermissionsGrouped()
    {
        $permissions = Permission::orderBy('name')->get();
        
        $grouped = $permissions->groupBy(function($permission) {
            $parts = explode('_', $permission->name);
            return ucfirst($parts[0]);
        });

        return response()->json([
            'success' => true,
            'data' => $grouped,
            'message' => 'Grouped permissions retrieved successfully'
        ]);
    }

    /**
     * Create a new permission.
     */
    public function createPermission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $permission = Permission::create(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'data' => $permission,
            'message' => 'Permission created successfully'
        ], 201);
    }

    /**
     * Delete a permission.
     */
    public function deletePermission($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully'
        ]);
    }
}
