<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Branch;
use App\Models\LabRequest;
use App\Models\RadiologyImage;
use App\Models\RadiologyRequest;
use App\Models\RadiologyStudy;
use App\Services\BranchAssignmentService;

trait ResolvesUserBranch
{
    /**
     * Resolve the authenticated user's branch ID.
     *
     * Fallback order (first match wins):
     * 1. staff_profiles.branch_id
     * 2. facility_users (first active branch via user->branches())
     * 3. users.current_branch_id (if column exists)
     * 4. session user_branch_id (branch switcher)
     * 5. Branch::getDefault() when $permissionForFallback is granted
     * 6. Branch::getPrimaryClinicalBranchId() for clinical roles with permission fallback
     *
     * @param  string|array|null  $permissionForFallback  Permission(s) required to use default branch when none assigned
     */
    protected function resolveUserBranchId(string|array|null $permissionForFallback = null): int
    {
        $user = auth()->user();
        $user->loadMissing('patient');

        $branchId = $user->staffProfile?->branch_id
            ?? $user->patient?->branch_id
            ?? $user->branches()->first()?->id
            ?? ($user->current_branch_id ?? null)
            ?? session('user_branch_id');

        if (!$branchId && $permissionForFallback) {
            $permissions = is_array($permissionForFallback) ? $permissionForFallback : [$permissionForFallback];
            $hasPermission = collect($permissions)->contains(fn ($perm) => $user->can($perm));

            if ($hasPermission) {
                $branchService = app(BranchAssignmentService::class);
                $roles = $user->roles->pluck('name')->all();
                try {
                    $branchId = $branchService->resolveBranchId(null, $user, $roles);
                } catch (\RuntimeException) {
                    $branchId = Branch::getDefault()?->id ?? Branch::getPrimaryClinicalBranchId();
                }

                \Log::warning('User accessing branch-scoped page without branch assignment', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'using_branch_id' => $branchId,
                    'required_permissions' => $permissions,
                ]);
            }
        }

        if (!$branchId) {
            abort(403, 'User not assigned to any branch');
        }

        return (int) $branchId;
    }

    /**
     * Linked patient record for portal users.
     */
    protected function portalPatient(): ?\App\Models\Patient
    {
        $user = auth()->user();

        if (!$user || !$user->isPatient()) {
            return null;
        }

        $patient = $user->patient;

        if (!$patient) {
            abort(404, 'Patient record not found for this user.');
        }

        return $patient;
    }

    /**
     * Scope a query to the authenticated patient's records when role is patient.
     */
    protected function scopeQueryToPortalPatient($query, string $patientIdColumn = 'patient_id')
    {
        $patient = $this->portalPatient();

        if ($patient) {
            $query->where($patientIdColumn, $patient->id);
        }

        return $query;
    }

    /**
     * Abort unless the resource belongs to the authenticated patient.
     */
    protected function assertPortalPatientOwns(?int $patientId): void
    {
        $patient = $this->portalPatient();

        if ($patient && (int) $patientId !== (int) $patient->id) {
            abort(403, 'You do not have access to this resource.');
        }
    }

    /**
     * Abort unless a branch-scoped resource belongs to the user's branch.
     */
    protected function assertResourceInUserBranch(int $resourceBranchId, string|array|null $permissionForFallback = null): int
    {
        $branchId = $this->resolveUserBranchId($permissionForFallback);

        if ((int) $resourceBranchId !== $branchId) {
            abort(403, 'You do not have access to this resource.');
        }

        return $branchId;
    }

    /**
     * Whether the user has lab-staff permissions (sees all branch lab requests, not just their own).
     */
    protected function userHasLabStaffPermissions(): bool
    {
        $user = auth()->user();

        return $user->can('process_lab_requests')
            || $user->can('enter_lab_results')
            || $user->can('verify_lab_results')
            || $user->can('approve_lab_results')
            || $user->hasRole(['lab_technician', 'lab_manager', 'lab_supervisor', 'lab_scientist']);
    }

    /**
     * Scope lab request queries for clinical doctors (own orders only).
     */
    protected function applyDoctorLabScope($query)
    {
        $user = auth()->user();

        if ($user->hasRole('doctor') && !$this->userHasLabStaffPermissions()) {
            $query->where(function ($q) use ($user) {
                $q->where('doctor_id', $user->id)
                    ->orWhereHas('consultation', fn ($c) => $c->where('doctor_id', $user->id));
            });
        }

        return $query;
    }

    /**
     * Enforce branch and role access for a lab request.
     */
    protected function assertLabRequestAccess(LabRequest $lab): void
    {
        $lab->loadMissing(['patient', 'consultation']);

        $branchId = $lab->branch_id
            ?? $lab->patient?->branch_id
            ?? $lab->consultation?->branch_id;

        if ($branchId) {
            $this->assertResourceInUserBranch((int) $branchId, 'view_lab_requests');
        } else {
            $this->resolveUserBranchId('view_lab_requests');
        }

        $user = auth()->user();

        if ($user->hasRole('doctor') && !$this->userHasLabStaffPermissions()) {
            $isOrderingDoctor = (int) $lab->doctor_id === (int) $user->id;
            $isConsultationDoctor = $lab->consultation && (int) $lab->consultation->doctor_id === (int) $user->id;

            if (!$isOrderingDoctor && !$isConsultationDoctor) {
                abort(403, 'You can only access lab requests for your own patients/consultations.');
            }
        }
    }

    /**
     * Whether the user has radiology-staff permissions (sees all branch radiology requests, not just their own).
     */
    protected function userHasRadiologyStaffPermissions(): bool
    {
        $user = auth()->user();

        return $user->can('process_radiology_requests')
            || $user->can('perform_radiology_studies')
            || $user->can('manage_radiology_setup')
            || $user->hasRole(['radiologist', 'radiology_technician']);
    }

    /**
     * Scope radiology request queries for clinical doctors (own orders only).
     */
    protected function applyDoctorRadiologyScope($query)
    {
        $user = auth()->user();

        if ($user->hasRole('doctor') && !$this->userHasRadiologyStaffPermissions()) {
            $query->where(function ($q) use ($user) {
                $q->where('doctor_id', $user->id)
                    ->orWhereHas('consultation', fn ($c) => $c->where('doctor_id', $user->id));
            });
        } elseif ($user->hasRole('radiologist') && !$user->can('manage_radiology_setup')) {
            $query->where(function ($q) use ($user) {
                $q->where('radiologist_id', $user->id)
                    ->orWhereNull('radiologist_id');
            });
        }

        return $query;
    }

    /**
     * Whether the user can upload radiology scan images.
     */
    protected function canUploadRadiologyImages(): bool
    {
        $user = auth()->user();

        return $user->can('upload_radiology_images')
            || $user->can('perform_radiology_studies')
            || $user->can('edit_radiology_studies')
            || $user->can('manage_radiology_setup')
            || $user->hasRole(['radiologist', 'radiology_technician', 'super_admin', 'admin']);
    }

    /**
     * Enforce branch and role access for a radiology study.
     */
    protected function assertRadiologyStudyAccess(RadiologyStudy $study): void
    {
        $study->loadMissing(['request.patient', 'patient']);

        if ($study->request) {
            $this->assertRadiologyRequestAccess($study->request);

            return;
        }

        $branchId = $study->patient?->branch_id;

        if ($branchId) {
            $this->assertResourceInUserBranch((int) $branchId, 'view_radiology_studies');
        } else {
            $this->resolveUserBranchId('view_radiology_studies');
        }
    }

    /**
     * Enforce branch and role access for a radiology request.
     */
    protected function resolveRadiologyViewPermission(): string
    {
        $user = auth()->user();

        if ($user->can('view_radiology_requests')) {
            return 'view_radiology_requests';
        }

        if ($user->can('view_radiology_results')) {
            return 'view_radiology_results';
        }

        abort(403, 'Insufficient permissions to view radiology data.');
    }

    protected function assertRadiologyRequestAccess(RadiologyRequest $radiology): void
    {
        $radiology->loadMissing(['patient', 'consultation']);

        $viewPermission = $this->resolveRadiologyViewPermission();

        $branchId = $radiology->branch_id
            ?? $radiology->patient?->branch_id
            ?? $radiology->consultation?->branch_id;

        if ($branchId) {
            $this->assertResourceInUserBranch((int) $branchId, $viewPermission);
        } else {
            $this->resolveUserBranchId($viewPermission);
        }

        $user = auth()->user();

        if ($user->hasRole('doctor') && !$this->userHasRadiologyStaffPermissions()) {
            $isOrderingDoctor = (int) $radiology->doctor_id === (int) $user->id;
            $isConsultationDoctor = $radiology->consultation && (int) $radiology->consultation->doctor_id === (int) $user->id;

            if (!$isOrderingDoctor && !$isConsultationDoctor) {
                abort(403, 'You can only access radiology requests for your own patients/consultations.');
            }
        }
    }

    /**
     * Enforce branch access for a radiology image.
     */
    protected function assertRadiologyImageAccess(RadiologyImage $image): void
    {
        $image->loadMissing('series.study.request', 'series.study.patient');

        $study = $image->series?->study;

        if (!$study) {
            abort(404, 'Radiology study not found for this image.');
        }

        $this->assertRadiologyStudyAccess($study);
    }
}
