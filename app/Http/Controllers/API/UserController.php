<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Services\BranchAssignmentService;
use App\Services\DoctorReviewService;
use App\Services\UserDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct(protected DoctorReviewService $doctorReviewService)
    {
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'staffProfile', 'branches']);

        // Filter by role
        if ($request->has('role') && $request->role !== '') {
            $query->role($request->role);
        }

        // Filter by status
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id !== '') {
            $query->whereHas('branches', function($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        // Search by name or email
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 20);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total()
            ],
            'message' => 'Users retrieved successfully'
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'is_active' => 'boolean',
            'department' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female,Other',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
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
            // Create user
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => $request->is_active ?? true
            ]);

            // Assign roles
            $user->assignRole($request->roles);
            $user->load('roles');

            $branchId = $request->branch_id
                ?? ($request->branch_ids[0] ?? null);

            $branchService = app(BranchAssignmentService::class);
            if ($branchService->isStaffRole($request->roles)) {
                $branchService->assignUserToBranch($user, $branchId, [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'contact' => $request->email,
                    'phone' => $request->phone,
                    'department' => $request->department,
                    'specialization' => $request->specialization,
                    'license_number' => $request->license_number,
                    'emergency_contact' => $request->emergency_contact,
                    'address' => $request->address,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender,
                    'is_active' => $request->is_active ?? true,
                ]);

                if ($request->has('branch_ids') && count($request->branch_ids) > 1) {
                    foreach (array_slice($request->branch_ids, 1) as $extraBranchId) {
                        \App\Models\FacilityUser::updateOrCreate(
                            ['user_id' => $user->id, 'branch_id' => $extraBranchId],
                            ['is_active' => true]
                        );
                    }
                }
            }

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                $file->storeAs('uploads/profiles', $filename, 'public');
                $user->staffProfile()->update(['profile_picture' => $filename]);
            }

            DB::commit();
            
            // CRITICAL: Clear permission cache AFTER commit to ensure changes take effect
            // This ensures cache is only cleared if transaction succeeded
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Refresh the user's permission cache
            $user->load('roles.permissions', 'permissions');

            return response()->json([
                'success' => true,
                'data' => $user->load(['roles', 'staffProfile', 'branches']),
                'message' => 'User created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with(['roles', 'staffProfile', 'branches'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User retrieved successfully'
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8|confirmed',
            'password_confirmation' => 'required_with:password|string|min:8',
            'phone' => 'nullable|string|max:20',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'is_active' => 'boolean',
            'department' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female,Other',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
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
            $updateData = $request->only(['first_name', 'last_name', 'email', 'phone', 'is_active']);
            
            // Update name field if first_name or last_name changed
            if ($request->has('first_name') || $request->has('last_name')) {
                $updateData['name'] = ($request->first_name ?? $user->first_name) . ' ' . ($request->last_name ?? $user->last_name);
            }
            
            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Update staff profile
            if ($user->staffProfile) {
                $profileData = $request->only([
                    'department', 'specialization', 'license_number', 
                    'emergency_contact', 'address', 'date_of_birth', 'gender'
                ]);
                $profileData['first_name'] = $request->first_name ?? $user->first_name;
                $profileData['last_name'] = $request->last_name ?? $user->last_name;
                $profileData['phone'] = $request->phone ?? $user->phone;
                $profileData['is_active'] = $request->is_active ?? $user->is_active;
                
                $user->staffProfile()->update($profileData);
            }

            // Update roles
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }

            // Update branches
            if ($request->has('branch_ids')) {
                $user->branches()->sync($request->branch_ids);
            }

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                $file->storeAs('uploads/profiles', $filename, 'public');
                $user->staffProfile()->update(['profile_picture' => $filename]);
            }

            DB::commit();
            
            // CRITICAL: Clear permission cache AFTER commit to ensure changes take effect
            // This ensures cache is only cleared if transaction succeeded
            if ($request->has('roles')) {
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                
                // Refresh the user's permission cache
                $user->load('roles.permissions', 'permissions');
            }

            return response()->json([
                'success' => true,
                'data' => $user->load(['roles', 'staffProfile', 'branches']),
                'message' => 'User updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id, UserDeletionService $deletionService)
    {
        try {
            $user = User::findOrFail($id);
            $actor = auth()->user();
            $blockReason = $deletionService->getBlockReason($user, $actor);

            if ($blockReason) {
                return response()->json([
                    'success' => false,
                    'message' => $blockReason,
                ], 403);
            }

            DB::beginTransaction();
            $summary = $deletionService->deleteUser($user, $actor);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $deletionService->buildSuccessMessage($user, $summary),
                'data' => $summary,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $deletionService->describeDeletionFailure($e),
            ], 500);
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
                DB::beginTransaction();
                $deletionService->deleteUser($user, $actor);
                DB::commit();
                $deletedCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $skippedCount++;
                $errors[] = "{$user->email}: " . $deletionService->describeDeletionFailure($e);
            }
        }

        $message = "Successfully deleted {$deletedCount} user(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} user(s) were skipped.";
        }

        return response()->json([
            'success' => $deletedCount > 0,
            'message' => $message,
            'data' => [
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
            ],
        ]);
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deactivating super admin
        if ($user->hasRole('super_admin') && $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate super admin user'
            ], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User status updated successfully'
        ]);
    }

    /**
     * Get doctors.
     */
    public function doctors(Request $request)
    {
        $query = User::role('doctor')->with(['staffProfile', 'branches']);

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('department') && $request->department !== '') {
            $query->whereHas('staffProfile', function($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        $doctors = $query->paginate($request->get('per_page', 20));

        // Enhance doctor data with availability information
        $doctorsData = $doctors->items();
        foreach ($doctorsData as $doctor) {
            // Calculate availability
            $hasUpcomingSlots = \App\Models\AppointmentSlot::where('doctor_id', $doctor->id)
                ->where('slot_date', '>=', now()->toDateString())
                ->where('status', 'available')
                ->whereColumn('booked_appointments', '<', 'max_appointments')
                ->exists();
            
            $isActive = $doctor->is_active && ($doctor->staffProfile?->is_active ?? true);
            $onlineStatus = $doctor->staffProfile?->online_status ?? 'offline';
            $isAvailable = $isActive && ($hasUpcomingSlots || $onlineStatus === 'online');
            
            // Add availability fields to doctor object
            $doctor->is_available = $isAvailable;
            $doctor->has_upcoming_slots = $hasUpcomingSlots;
            if ($doctor->staffProfile) {
                $doctor->staffProfile->online_status = $onlineStatus;
            }

            $stats = $this->doctorReviewService->ratingStatsForDoctor($doctor->id);
            $doctor->rating = $stats['rating'];
            $doctor->review_count = $stats['review_count'];
        }

        return response()->json([
            'success' => true,
            'data' => $doctorsData,
            'meta' => [
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
                'per_page' => $doctors->perPage(),
                'total' => $doctors->total()
            ],
            'message' => 'Doctors retrieved successfully'
        ]);
    }

    /**
     * Get doctor detail by ID (public - for patient app)
     */
    public function getDoctorDetail($id): JsonResponse
    {
        try {
            $doctor = User::role('doctor')
                ->with(['staffProfile', 'branches', 'department'])
                ->findOrFail($id);

            // Calculate real-time availability based on appointment slots
            $hasUpcomingSlots = \App\Models\AppointmentSlot::where('doctor_id', $doctor->id)
                ->where('slot_date', '>=', now()->toDateString())
                ->where('status', 'available')
                ->whereColumn('booked_appointments', '<', 'max_appointments')
                ->exists();
            
            // Doctor is available if:
            // 1. User account is active
            // 2. Staff profile is active (if exists)
            // 3. Has upcoming appointment slots OR online_status is 'online'
            $isActive = $doctor->is_active && ($doctor->staffProfile?->is_active ?? true);
            $onlineStatus = $doctor->staffProfile?->online_status ?? 'offline';
            $isAvailable = $isActive && ($hasUpcomingSlots || $onlineStatus === 'online');
            $stats = $this->doctorReviewService->ratingStatsForDoctor($doctor->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'first_name' => $doctor->staffProfile?->first_name ?? $doctor->first_name,
                    'last_name' => $doctor->staffProfile?->last_name ?? $doctor->last_name,
                    'email' => $doctor->email,
                    'phone' => $doctor->phone,
                    'specialization' => $doctor->staffProfile?->specialization,
                    'department' => $doctor->staffProfile?->department,
                    'license_number' => $doctor->staffProfile?->license_number,
                    'qualifications' => $doctor->staffProfile?->qualifications,
                    'experience_years' => $doctor->staffProfile?->experience_years,
                    'consultation_fee' => $doctor->staffProfile?->consultation_fee,
                    'bio' => $doctor->staffProfile?->bio,
                    'profile_image' => $doctor->staffProfile?->photo,
                    'branches' => $doctor->branches->map(function($branch) {
                        return [
                            'id' => $branch->id,
                            'name' => $branch->name,
                            'location' => $branch->location,
                        ];
                    }),
                    'staff_profile' => $doctor->staffProfile ? [
                        'online_status' => $onlineStatus,
                        'is_active' => $doctor->staffProfile->is_active,
                        'department' => $doctor->staffProfile->department,
                        'specialization' => $doctor->staffProfile->specialization,
                    ] : null,
                    'is_available' => $isAvailable,
                    'has_upcoming_slots' => $hasUpcomingSlots,
                    'rating' => $stats['rating'],
                    'review_count' => $stats['review_count'],
                    'total_consultations' => $doctor->consultations()->count(),
                ],
                'message' => 'Doctor details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving doctor details: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get nurses.
     */
    public function nurses(Request $request)
    {
        $query = User::role('nurse')->with(['staffProfile', 'branches']);

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('department') && $request->department !== '') {
            $query->whereHas('staffProfile', function($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        $nurses = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $nurses->items(),
            'meta' => [
                'current_page' => $nurses->currentPage(),
                'last_page' => $nurses->lastPage(),
                'per_page' => $nurses->perPage(),
                'total' => $nurses->total()
            ],
            'message' => 'Nurses retrieved successfully'
        ]);
    }

    /**
     * Get users by role.
     */
    public function getUsersByRole($role, Request $request)
    {
        $query = User::role($role)->with(['staffProfile', 'branches']);

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('department') && $request->department !== '') {
            $query->whereHas('staffProfile', function($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        $users = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total()
            ],
            'message' => ucfirst($role) . 's retrieved successfully'
        ]);
    }

    /**
     * Search users.
     */
    public function searchUsers(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No search query provided'
            ]);
        }

        $users = User::with(['roles', 'staffProfile', 'branches'])
            ->where(function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Search results retrieved successfully'
        ]);
    }

    /**
     * Get all roles.
     */
    public function getRoles()
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
            'message' => 'Roles retrieved successfully'
        ]);
    }

    /**
     * Get all permissions.
     */
    public function getPermissions()
    {
        $permissions = Permission::all();

        return response()->json([
            'success' => true,
            'data' => $permissions,
            'message' => 'Permissions retrieved successfully'
        ]);
    }

    /**
     * Get user statistics.
     */
    public function getStatistics()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'users_by_role' => User::with('roles')
                ->get()
                ->groupBy(function($user) {
                    return $user->roles->first()->name ?? 'No Role';
                })
                ->map->count(),
            'recent_users' => User::with(['roles', 'staffProfile'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'User statistics retrieved successfully'
        ]);
    }
}
