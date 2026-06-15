<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class Role extends SpatieRole
{
    /**
     * System roles that cannot be deleted from the admin UI.
     */
    public const PROTECTED_ROLE_NAMES = [
        'super_admin',
        'admin',
        'doctor',
        'nurse',
        'pharmacist',
        'lab_technician',
        'lab_supervisor',
        'lab_manager',
        'lab_scientist',
        'receptionist',
        'accountant',
        'cashier',
        'patient',
        'emergency_staff',
        'surgery_staff',
        'radiologist',
        'radiology_technician',
        'store_manager',
        'dispatch_manager',
        'delivery_rider',
    ];

    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'is_system_role',
        'level',
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
    ];

    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }

    public function scopeCustomRoles($query)
    {
        return $query->where('is_system_role', false);
    }

    public function scopeForWebAdmin($query)
    {
        return $query->where('guard_name', 'web');
    }

    /**
     * Resolve users even when the role guard has no auth provider (e.g. legacy sanctum roles).
     */
    public function users(): BelongsToMany
    {
        $guardName = $this->attributes['guard_name'] ?? config('auth.defaults.guard');
        $modelClass = getModelForGuard($guardName)
            ?? config('auth.providers.users.model', User::class);

        return $this->morphedByMany(
            $modelClass,
            'model',
            config('permission.table_names.model_has_roles'),
            app(PermissionRegistrar::class)->pivotRole,
            config('permission.column_names.model_morph_key')
        );
    }

    public function isProtected(): bool
    {
        return in_array($this->name, self::PROTECTED_ROLE_NAMES, true);
    }

    public function displayName(): string
    {
        return ucwords(str_replace('_', ' ', $this->name));
    }

    public function getAssignedUsersCount(): int
    {
        return (int) DB::table(config('permission.table_names.model_has_roles'))
            ->where('role_id', $this->id)
            ->distinct()
            ->count('model_id');
    }

    public function getDeletionBlockReason(): ?string
    {
        if ($this->isProtected()) {
            return "Cannot delete protected system role \"{$this->displayName()}\".";
        }

        $usersCount = $this->getAssignedUsersCount();
        if ($usersCount > 0) {
            return "Cannot delete role. It is assigned to {$usersCount} user(s).";
        }

        return null;
    }

    public function getValidPermissions()
    {
        return $this->permissions->filter(fn ($permission) => $permission && filled($permission->name));
    }
}
