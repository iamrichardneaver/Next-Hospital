<?php

namespace App\Policies;

use App\Models\Vital;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VitalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any vitals.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_vitals');
    }

    /**
     * Determine whether the user can view the vital.
     */
    public function view(User $user, Vital $vital): bool
    {
        return $user->can('view_vitals');
    }

    /**
     * Determine whether the user can create vitals.
     */
    public function create(User $user): bool
    {
        return $user->can('record_vitals');
    }

    /**
     * Determine whether the user can update the vital.
     */
    public function update(User $user, Vital $vital): bool
    {
        // Users can edit vitals if they have edit_vitals permission
        // AND either they recorded it OR they have admin permissions
        return $user->can('edit_vitals') && 
               ($vital->recorded_by == $user->id || $user->hasRole(['admin', 'super_admin']));
    }

    /**
     * Determine whether the user can delete the vital.
     */
    public function delete(User $user, Vital $vital): bool
    {
        // Only admins can delete vitals for audit trail protection
        return $user->can('delete_vitals') && $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can restore the vital.
     */
    public function restore(User $user, Vital $vital): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the vital.
     */
    public function forceDelete(User $user, Vital $vital): bool
    {
        return $user->hasRole('super_admin');
    }
}
