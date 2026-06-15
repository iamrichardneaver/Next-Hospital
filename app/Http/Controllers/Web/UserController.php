<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Models\User;
use App\Models\Branch;
use App\Services\BranchAssignmentService;
use App\Services\UserDeletionService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ExportsListData;
    public function index()
    {
        $users = User::with(['roles', 'staffProfile'])->latest('id')->paginate(20);
        
        $statistics = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'doctors' => User::role('doctor')->count(),
            'nurses' => User::role('nurse')->count(),
        ];
        
        return view('users.index', compact('users', 'statistics'));
    }
    
    public function create()
    {
        $roles = Role::all();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $defaultBranch = Branch::find(session('user_branch_id'))
            ?? auth()->user()?->staffProfile?->branch
            ?? Branch::find(Branch::getPrimaryClinicalBranchId())
            ?? Branch::getDefault();
        
        return view('users.create', compact('roles', 'branches', 'defaultBranch'));
    }
    
    /**
     * Store a newly created user in storage
     * Enforces strong password policy and proper error handling
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'password' => ['required', 'confirmed', new StrongPassword],
                'phone' => 'nullable|string|max:20',
                'role' => 'required|exists:roles,name',
                'branch_id' => 'nullable|exists:branches,id',
            ]);
            
            \DB::beginTransaction();
            
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);
            
            $user->assignRole($validated['role']);
            $user->load('roles');

            $branchService = app(BranchAssignmentService::class);
            if ($branchService->isStaffRole($validated['role'])) {
                $branchService->assignUserToBranch($user, $validated['branch_id'] ?? null, [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'contact' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                ]);
            }
            
            \DB::commit();
            
            // CRITICAL: Clear permission cache AFTER commit to ensure changes take effect
            // This ensures cache is only cleared if transaction succeeded
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Refresh the user's permission cache
            $user->load('roles.permissions', 'permissions');
            
            \Log::info('New user created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $validated['role'],
                'created_by' => auth()->id(),
            ]);
            
            return redirect()->route('users.index')
                ->with('success', 'User created successfully! An initial password has been set.');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            \Log::error('Error creating user: ' . $e->getMessage(), [
                'request_data' => $request->except(['password', 'password_confirmation', '_token']),
                'created_by' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $message = config('app.debug')
                ? 'Unable to create user: ' . $e->getMessage()
                : 'Unable to create user. Please try again or contact support.';
            
            return back()->with('error', $message)->withInput();
        }
    }
    
    public function show(User $user)
    {
        $user->load(['roles.permissions', 'staffProfile']);
        
        return view('users.show', compact('user'));
    }
    
    public function edit(User $user)
    {
        $roles = Role::all();
        
        return view('users.edit', compact('user', 'roles'));
    }
    
    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'password' => 'nullable|min:8|confirmed',
                'role' => 'nullable|exists:roles,name',
            ]);
            
            \DB::beginTransaction();
            
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];
            
            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($validated['password']);
            }
            
            $user->update($updateData);
            
            // Update user role and automatically assign role permissions
            if ($request->filled('role')) {
                $oldRoles = $user->roles->pluck('name')->toArray();
                
                // Sync roles (removes old roles, adds new role)
                $user->syncRoles([$validated['role']]);
                $user->load('roles');

                $branchService = app(BranchAssignmentService::class);
                if ($branchService->isStaffRole($validated['role'])) {
                    $branchService->syncStaffBranch($user, $request->input('branch_id'));
                }
                
                // Get the new role with permissions
                $newRole = Role::where('name', $validated['role'])->with('permissions')->first();
            }
            
            \DB::commit();
            
            // CRITICAL: Clear permission cache AFTER commit to ensure changes take effect
            // This ensures cache is only cleared if transaction succeeded
            if ($request->filled('role')) {
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                
                // Also refresh the user's permission cache
                $user->load('roles.permissions', 'permissions');
                
                // CRITICAL: If the updated user is the currently logged-in user, refresh their
                // session user object to ensure permissions are immediately reflected in the UI
                if (auth()->check() && auth()->id() === $user->id) {
                    auth()->user()->loadMissing(['roles.permissions', 'permissions']);
                    auth()->user()->refreshPermissions();
                }
                
                \Log::info('User role updated', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'old_roles' => $oldRoles ?? [],
                    'new_role' => $validated['role'],
                    'permissions_count' => $newRole ? $newRole->permissions->count() : 0,
                    'total_permissions_count' => $user->getAllPermissions()->count(),
                    'updated_by' => auth()->id(),
                    'session_refreshed' => auth()->check() && auth()->id() === $user->id,
                ]);
            }
            
            // Build success message with role change info
            $successMessage = 'User updated successfully!';
            if ($request->filled('role')) {
                $newRole = Role::where('name', $validated['role'])->with('permissions')->first();
                $permissionsCount = $newRole ? $newRole->permissions->count() : 0;
                $successMessage .= ' Role changed to ' . ucwords(str_replace('_', ' ', $validated['role'])) . 
                                  ' with ' . $permissionsCount . ' permissions.';
            }
            
            return redirect()->route('users.show', $user)
                ->with('success', $successMessage);
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            \Log::error('Error updating user: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'request_data' => $request->except(['password', 'password_confirmation', '_token']),
                'updated_by' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Error updating user: ' . $e->getMessage())
                        ->withInput();
        }
    }
    
    public function destroy(User $user, UserDeletionService $deletionService)
    {
        $blockReason = $deletionService->getBlockReason($user, auth()->user());

        if ($blockReason) {
            return back()->with('error', $blockReason);
        }

        try {
            \DB::beginTransaction();

            $summary = $deletionService->deleteUser($user, auth()->user());

            \DB::commit();

            return redirect()->route('users.index')
                ->with('success', $deletionService->buildSuccessMessage($user, $summary));
        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Error deleting user: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'deleted_user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', $deletionService->describeDeletionFailure($e));
        }
    }

    public function bulkDestroy(Request $request, UserDeletionService $deletionService)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $users = User::with(['roles', 'staffProfile', 'patient'])
            ->whereIn('id', $validated['user_ids'])
            ->get()
            ->keyBy('id');

        $deletedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $totalSuperAdmins = User::role('super_admin')->count();
        $superAdminDeleteSlots = max(0, $totalSuperAdmins - 1);
        $actor = auth()->user();

        foreach ($validated['user_ids'] as $userId) {
            $user = $users->get((int) $userId);

            if (!$user) {
                $skippedCount++;
                continue;
            }

            $blockReason = $deletionService->getBlockReason($user, $actor);

            if ($blockReason) {
                $skippedCount++;
                $errors[] = "{$user->email}: {$blockReason}";
                continue;
            }

            if ($user->hasRole('super_admin')) {
                if ($superAdminDeleteSlots <= 0) {
                    $skippedCount++;
                    $errors[] = "{$user->email}: Cannot delete the last super admin account.";
                    continue;
                }

                $superAdminDeleteSlots--;
            }

            try {
                \DB::beginTransaction();

                $summary = $deletionService->deleteUser($user, $actor);

                \DB::commit();

                $deletedCount++;

                \Log::info('User deleted (bulk)', [
                    'deleted_user_id' => $user->id,
                    'deleted_user_email' => $user->email,
                    'deleted_patient' => $summary['deleted_patient'],
                    'deleted_staff_profile' => $summary['deleted_staff_profile'],
                    'deleted_by' => auth()->id(),
                ]);
            } catch (\Exception $e) {
                \DB::rollBack();

                $skippedCount++;
                $errors[] = "{$user->email}: " . $deletionService->describeDeletionFailure($e);

                \Log::error('Error bulk deleting user: ' . $e->getMessage(), [
                    'user_id' => auth()->id(),
                    'deleted_user_id' => $user->id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $message = "Successfully deleted {$deletedCount} user(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} user(s) were skipped.";
            if (!empty($errors)) {
                $message .= ' ' . implode(' ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= ' (and ' . (count($errors) - 3) . ' more)';
                }
            }
        }

        if ($deletedCount === 0) {
            return redirect()->route('users.index')->with('error', $message);
        }

        return redirect()->route('users.index')->with('success', $message);
    }
    
    /**
     * Show user profile
     */
    public function profile()
    {
        $user = auth()->user();
        $user->load(['roles.permissions', 'staffProfile']);
        
        // Load patient record if user is a patient
        $patient = null;
        if ($user->hasRole('patient')) {
            $patient = \App\Models\Patient::where('user_id', $user->id)->first();
        }
        
        return view('users.profile', compact('user', 'patient'));
    }
    
    /**
     * Update user profile
     * 
     * Handles both staff and patient profile updates.
     * For patients, also updates patient-specific fields like nhis_number, ghana_card_number, and emergency contact details.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        // Base validation rules
        $validationRules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female',
            'emergency_contact' => 'nullable|string|max:255',
        ];
        
        // Add patient-specific fields if user is a patient
        if ($user->hasRole('patient')) {
            $validationRules = array_merge($validationRules, [
                'other_names' => 'nullable|string|max:255',
                'nhis_number' => 'nullable|string|max:255',
                'ghana_card_number' => 'nullable|string|max:255',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:255',
                'emergency_contact_relationship' => 'nullable|string|max:255',
            ]);
        }
        
        $validated = $request->validate($validationRules);
        
        // Update user basic information
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'] . ' ' . $validated['last_name'], // Keep name field in sync
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);
        
        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile_pictures', $filename, 'public');
            $user->update(['profile_picture' => $path]);
        }
        
        // Update or create staff profile if user is staff
        if ($user->isStaff()) {
            $staffProfile = $user->staffProfile ?: new \App\Models\StaffProfile();
            $staffProfile->user_id = $user->id;
            $staffProfile->address = $validated['address'] ?? null;
            $staffProfile->date_of_birth = $validated['date_of_birth'] ?? null;
            $staffProfile->gender = $validated['gender'] ?? null;
            $staffProfile->emergency_contact = $validated['emergency_contact'] ?? null;
            $staffProfile->save();
        }
        
        // Update patient record if user is a patient
        if ($user->hasRole('patient')) {
            $patient = \App\Models\Patient::where('user_id', $user->id)->first();
            
            if ($patient) {
                // Always update these required fields
                $patientData = [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'updated_by' => $user->id,
                ];
                
                // Update optional fields if they exist in validated array
                // This allows users to clear fields by submitting empty values (which become null)
                if (array_key_exists('other_names', $validated)) {
                    $patientData['other_names'] = $validated['other_names'];
                }
                if (array_key_exists('gender', $validated)) {
                    $patientData['gender'] = $validated['gender'];
                }
                if (array_key_exists('date_of_birth', $validated)) {
                    $patientData['date_of_birth'] = $validated['date_of_birth'];
                }
                if (array_key_exists('phone', $validated)) {
                    $patientData['phone'] = $validated['phone'];
                }
                if (array_key_exists('address', $validated)) {
                    $patientData['address'] = $validated['address'];
                }
                
                // Patient-specific optional fields - allow clearing by checking if key exists in validated
                // Empty form fields will be null in validated array, allowing users to clear them
                if (array_key_exists('nhis_number', $validated)) {
                    $patientData['nhis_number'] = $validated['nhis_number'];
                }
                if (array_key_exists('ghana_card_number', $validated)) {
                    $patientData['ghana_card_number'] = $validated['ghana_card_number'];
                }
                if (array_key_exists('emergency_contact_name', $validated)) {
                    $patientData['emergency_contact_name'] = $validated['emergency_contact_name'];
                }
                if (array_key_exists('emergency_contact_phone', $validated)) {
                    $patientData['emergency_contact_phone'] = $validated['emergency_contact_phone'];
                }
                if (array_key_exists('emergency_contact_relationship', $validated)) {
                    $patientData['emergency_contact_relationship'] = $validated['emergency_contact_relationship'];
                }
                
                $patient->update($patientData);
            }
        }
        
        return redirect()->route('profile.show')
            ->with('success', 'Profile updated successfully!');
    }
    
    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);
        
        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'Current password is incorrect']);
        }
        
        // Update password
        $user->update([
            'password' => Hash::make($validated['password'])
        ]);
        
        return redirect()->route('profile.show')
            ->with('success', 'Password changed successfully!');
    }
    
    /**
     * Show user permissions management page
     * Allows admins to assign/remove individual permissions to users
     * Useful for temporary permission grants (e.g., lab tech covering receptionist duties)
     */
    public function managePermissions(User $user)
    {
        // Load user with roles and all permissions
        $user->load(['roles.permissions', 'permissions']);
        
        // Get all available permissions grouped by module
        $allPermissions = Permission::orderBy('name')->get();
        $groupedPermissions = $allPermissions->groupBy(function($permission) {
            $parts = explode('_', $permission->name);
            $module = implode('_', array_slice($parts, 1));
            return ucwords(str_replace('_', ' ', $module));
        });
        
        // Get permissions user has through roles
        $rolePermissions = $user->getPermissionsViaRoles()->pluck('id')->toArray();
        
        // Get permissions assigned directly to user
        $directPermissions = $user->permissions->pluck('id')->toArray();
        
        // Get user's role names
        $userRoles = $user->roles->pluck('name')->toArray();
        
        return view('users.manage-permissions', compact(
            'user',
            'groupedPermissions',
            'rolePermissions',
            'directPermissions',
            'userRoles'
        ));
    }
    
    /**
     * Update user's direct permissions
     * These permissions are added ON TOP of role permissions
     */
    public function updatePermissions(Request $request, User $user)
    {
        $validated = $request->validate([
            'direct_permissions' => 'nullable|array',
            'direct_permissions.*' => 'exists:permissions,id'
        ]);
        
        try {
            \DB::beginTransaction();
            
            // Sync direct permissions (permissions assigned directly to the user)
            // These are separate from role permissions
            if ($request->has('direct_permissions') && is_array($request->direct_permissions)) {
                $permissions = Permission::whereIn('id', $request->direct_permissions)->get();
                $user->syncPermissions($permissions);
            } else {
                // Remove all direct permissions (user keeps role permissions)
                $user->syncPermissions([]);
            }
            
            \DB::commit();
            
            // CRITICAL: Clear permission cache AFTER commit to ensure changes take effect
            // This ensures cache is only cleared if transaction succeeded
            
            // CRITICAL: Clear the user-specific permission cache first
            // Spatie Permission caches permissions per user, so we must clear the user's cache
            $user->forgetCachedPermissions();
            
            // Clear the global permission cache as well
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Also refresh the user's permission cache
            $user->loadMissing(['roles.permissions', 'permissions']);
            
            // CRITICAL: If the updated user is the currently logged-in user, refresh their
            // session user object to ensure permissions are immediately reflected in the UI
            // This is especially important for the sidebar menu which uses @can() directives
            if (auth()->check() && auth()->id() === $user->id) {
                // Clear the session user's cache
                auth()->user()->forgetCachedPermissions();
                // Reload the authenticated user's permissions in the session
                auth()->user()->loadMissing(['roles.permissions', 'permissions']);
            }
            
            \Log::info('User permissions updated', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'direct_permissions_count' => count($request->direct_permissions ?? []),
                'role_permissions_count' => $user->getPermissionsViaRoles()->count(),
                'total_permissions_count' => $user->getAllPermissions()->count(),
                'updated_by' => auth()->id(),
                'session_refreshed' => auth()->check() && auth()->id() === $user->id,
            ]);
            
            return redirect()->route('users.show', $user)
                ->with('success', 'User permissions updated successfully! Changes take effect immediately.');
                
        } catch (\Exception $e) {
            \DB::rollback();
            
            \Log::error('Error updating user permissions: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'updated_by' => auth()->id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Error updating permissions: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Quick grant specific permission to user
     * Useful for temporary grants via API/AJAX
     */
    public function grantPermission(Request $request, User $user)
    {
        $validated = $request->validate([
            'permission' => 'required|exists:permissions,name'
        ]);
        
        try {
            $user->givePermissionTo($validated['permission']);
            
            // CRITICAL: Clear permission cache immediately to ensure changes take effect
            
            // CRITICAL: Clear the user-specific permission cache first
            // Spatie Permission caches permissions per user, so we must clear the user's cache
            $user->forgetCachedPermissions();
            
            // Clear the global permission cache as well
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Refresh the user's permission cache
            $user->loadMissing(['roles.permissions', 'permissions']);
            
            // CRITICAL: If the updated user is the currently logged-in user, refresh their
            // session user object to ensure permissions are immediately reflected in the UI
            if (auth()->check() && auth()->id() === $user->id) {
                // Clear the session user's cache
                auth()->user()->forgetCachedPermissions();
                // Reload the authenticated user's permissions in the session
                auth()->user()->loadMissing(['roles.permissions', 'permissions']);
            }
            
            \Log::info('Permission granted to user', [
                'user_id' => $user->id,
                'permission' => $validated['permission'],
                'total_permissions_count' => $user->getAllPermissions()->count(),
                'granted_by' => auth()->id(),
                'session_refreshed' => auth()->check() && auth()->id() === $user->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Permission granted successfully! Changes take effect immediately.',
                'permission' => $validated['permission'],
                'total_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error granting permission: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Quick revoke specific permission from user
     * Useful for removing temporary grants via API/AJAX
     */
    public function revokePermission(Request $request, User $user)
    {
        $validated = $request->validate([
            'permission' => 'required|exists:permissions,name'
        ]);
        
        try {
            $user->revokePermissionTo($validated['permission']);
            
            // CRITICAL: Clear permission cache immediately to ensure changes take effect
            
            // CRITICAL: Clear the user-specific permission cache first
            // Spatie Permission caches permissions per user, so we must clear the user's cache
            $user->forgetCachedPermissions();
            
            // Clear the global permission cache as well
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Refresh the user's permission cache
            $user->loadMissing(['roles.permissions', 'permissions']);
            
            // CRITICAL: If the updated user is the currently logged-in user, refresh their
            // session user object to ensure permissions are immediately reflected in the UI
            if (auth()->check() && auth()->id() === $user->id) {
                // Clear the session user's cache
                auth()->user()->forgetCachedPermissions();
                // Reload the authenticated user's permissions in the session
                auth()->user()->loadMissing(['roles.permissions', 'permissions']);
            }
            
            \Log::info('Permission revoked from user', [
                'user_id' => $user->id,
                'permission' => $validated['permission'],
                'total_permissions_count' => $user->getAllPermissions()->count(),
                'revoked_by' => auth()->id(),
                'session_refreshed' => auth()->check() && auth()->id() === $user->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Permission revoked successfully! Changes take effect immediately.',
                'permission' => $validated['permission'],
                'total_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error revoking permission: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $query = User::with(['roles', 'staffProfile.branch'])->latest('id');

        return $this->exportFromQuery($request, $query, [
            'First Name' => 'first_name',
            'Last Name' => 'last_name',
            'Email' => 'email',
            'Phone' => 'phone',
            'Role' => fn ($u) => $u->roles->pluck('name')->join(', '),
            'Branch' => fn ($u) => $u->staffProfile?->branch?->name ?? '',
            'Active' => fn ($u) => $u->is_active ? 'Yes' : 'No',
        ], 'users', 'view_users');
    }
}
