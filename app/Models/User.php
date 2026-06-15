<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\HasIdPrefix;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasIdPrefix;


    protected $fillable = [
        'id',
        'staff_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'last_login_at',
        'profile_picture'
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        // Only generate staff IDs for users with staff roles
        if ($this->hasRole(['admin', 'doctor', 'nurse', 'pharmacist', 'lab_technician', 'receptionist', 'accountant', 'super_admin'])) {
            return 'staff';
        }
        
        // For patients or other roles, don't generate staff ID
        return null;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            // Only set staff_id for users with staff roles
            if ($user->hasRole(['admin', 'doctor', 'nurse', 'pharmacist', 'lab_technician', 'receptionist', 'accountant', 'super_admin'])) {
                if (empty($user->staff_id)) {
                    $user->staff_id = $user->id;
                }
            }
        });
    }

    /**
     * Check if user is a staff member
     */
    public function isStaff()
    {
        return $this->hasRole(['admin', 'doctor', 'nurse', 'pharmacist', 'lab_technician', 'receptionist', 'accountant', 'super_admin']);
    }

    /**
     * Check if user is a patient
     */
    public function isPatient()
    {
        return $this->hasRole('patient');
    }

    /**
     * Get the appropriate reference ID based on user type
     */
    public function getReferenceId()
    {
        if ($this->isStaff()) {
            return $this->staff_id;
        } elseif ($this->isPatient()) {
            // For patients, get the patient_number from the patients table
            $patient = $this->patient();
            return $patient ? $patient->patient_number : null;
        }
        
        return null;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime'
    ];

    public function staffProfile()
    {
        return $this->hasOne(StaffProfile::class);
    }

    /**
     * Get the patient record associated with this user (if user is a patient)
     */
    public function patient()
    {
        return $this->hasOne(Patient::class, 'user_id');
    }

    /**
     * Get the delivery rider record associated with this user
     */
    public function deliveryRider()
    {
        return $this->hasOne(DeliveryRider::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class, 'doctor_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function doctorReviews()
    {
        return $this->hasMany(DoctorReview::class, 'doctor_id');
    }

    public function labRequests()
    {
        return $this->hasMany(LabRequest::class, 'doctor_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'doctor_id');
    }

    public function emergencyVisits()
    {
        return $this->hasMany(EmergencyVisit::class, 'assigned_doctor_id');
    }

    public function surgerySchedules()
    {
        return $this->hasMany(SurgerySchedule::class, 'surgeon_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('last_read_at', 'joined_at')
            ->withTimestamps()
            ->orderBy('conversations.updated_at', 'desc');
    }

    public function fileUploads()
    {
        return $this->hasMany(FileUpload::class, 'uploaded_by');
    }

    public function createdPatients()
    {
        return $this->hasMany(Patient::class, 'created_by');
    }

    public function updatedPatients()
    {
        return $this->hasMany(Patient::class, 'updated_by');
    }

    public function createdAppointments()
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function updatedAppointments()
    {
        return $this->hasMany(Appointment::class, 'updated_by');
    }

    public function createdConsultations()
    {
        return $this->hasMany(Consultation::class, 'created_by');
    }

    public function updatedConsultations()
    {
        return $this->hasMany(Consultation::class, 'updated_by');
    }

    public function createdPrescriptions()
    {
        return $this->hasMany(Prescription::class, 'created_by');
    }

    public function createdLabRequests()
    {
        return $this->hasMany(LabRequest::class, 'created_by');
    }

    public function updatedLabRequests()
    {
        return $this->hasMany(LabRequest::class, 'updated_by');
    }

    public function performedLabResults()
    {
        return $this->hasMany(LabResult::class, 'performed_by');
    }

    public function verifiedLabResults()
    {
        return $this->hasMany(LabResult::class, 'verified_by');
    }

    public function approvedLabResults()
    {
        return $this->hasMany(LabResult::class, 'approved_by');
    }

    public function createdInvoices()
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function updatedInvoices()
    {
        return $this->hasMany(Invoice::class, 'updated_by');
    }

    public function processedPayments()
    {
        return $this->hasMany(Payment::class, 'processed_by');
    }

    public function createdPayments()
    {
        return $this->hasMany(Payment::class, 'created_by');
    }

    public function updatedPayments()
    {
        return $this->hasMany(Payment::class, 'updated_by');
    }

    public function createdDrugs()
    {
        return $this->hasMany(Drug::class, 'created_by');
    }

    public function updatedDrugs()
    {
        return $this->hasMany(Drug::class, 'updated_by');
    }

    public function createdDrugStocks()
    {
        return $this->hasMany(DrugStock::class, 'created_by');
    }

    public function updatedDrugStocks()
    {
        return $this->hasMany(DrugStock::class, 'updated_by');
    }

    public function dispensedDrugOrders()
    {
        return $this->hasMany(DrugOrder::class, 'dispensed_by');
    }

    public function createdDrugOrders()
    {
        return $this->hasMany(DrugOrder::class, 'created_by');
    }

    public function createdLabTestTypes()
    {
        return $this->hasMany(LabTestType::class, 'created_by');
    }

    public function updatedLabTestTypes()
    {
        return $this->hasMany(LabTestType::class, 'updated_by');
    }

    public function createdBranches()
    {
        return $this->hasMany(Branch::class, 'created_by');
    }

    public function updatedBranches()
    {
        return $this->hasMany(Branch::class, 'updated_by');
    }

    public function facilityUsers()
    {
        return $this->hasMany(FacilityUser::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'facility_users', 'user_id', 'branch_id')
            ->withPivot('is_active');
    }

    /**
     * Get the user's default branch (first assigned branch)
     */
    public function getDefaultBranch()
    {
        return $this->branches()->where('branches.is_active', true)->first();
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getDisplayNameAttribute()
    {
        return $this->name;
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function isDoctor()
    {
        return $this->hasRole('doctor');
    }

    public function isNurse()
    {
        return $this->hasRole('nurse');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    public function isAdminOrSuperAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function isPharmacist()
    {
        return $this->hasRole('pharmacist');
    }

    public function isLabTechnician()
    {
        return $this->hasRole('lab_technician');
    }

    public function isReceptionist()
    {
        return $this->hasRole('receptionist');
    }

    public function isAccountant()
    {
        return $this->hasRole('accountant');
    }

    /**
     * Get the user's notification preferences.
     */
    public function notificationPreference()
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    /**
     * Get or create notification preferences.
     */
    public function getOrCreateNotificationPreference()
    {
        if (!$this->notificationPreference) {
            return UserNotificationPreference::createDefault($this->id);
        }
        
        return $this->notificationPreference;
    }
    
    /**
     * Check if user has a specific permission directly assigned (not via role)
     * Useful for identifying temporary permission grants
     * 
     * @param string $permission
     * @return bool
     */
    public function hasDirectPermission($permission)
    {
        return $this->permissions()->where('name', $permission)->exists();
    }
    
    /**
     * Get count of direct permissions (not via roles)
     * 
     * @return int
     */
    public function getDirectPermissionsCount()
    {
        return $this->permissions()->count();
    }
    
    /**
     * Get all direct permissions (not via roles)
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getDirectPermissions()
    {
        return $this->permissions;
    }
    
    /**
     * Check if user has any direct permissions assigned
     * Useful for showing badges or indicators in UI
     * 
     * @return bool
     */
    public function hasAnyDirectPermissions()
    {
        return $this->getDirectPermissionsCount() > 0;
    }
    
    /**
     * Get human-readable permission summary
     * Returns array with role and direct permission counts
     * 
     * @return array
     */
    public function getPermissionsSummary()
    {
        return [
            'role_permissions_count' => $this->getPermissionsViaRoles()->count(),
            'direct_permissions_count' => $this->getDirectPermissionsCount(),
            'total_permissions_count' => $this->getAllPermissions()->count(),
            'has_temporary_grants' => $this->hasAnyDirectPermissions(),
        ];
    }
    
    /**
     * Refresh user permissions cache immediately
     * This ensures permission changes take effect right away
     * 
     * @return self
     */
    public function refreshPermissions()
    {
        // CRITICAL: Clear the user-specific permission cache first
        // Spatie Permission caches permissions per user, so we must clear the user's cache
        $this->forgetCachedPermissions();
        
        // Clear the global permission cache as well
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Reload user's relationships to get fresh permission data
        $this->loadMissing(['roles.permissions', 'permissions']);
        
        // Force refresh the user model to ensure fresh data
        $this->refresh();
        
        return $this;
    }
    
    /**
     * Static flag to prevent infinite recursion in can() method
     * 
     * @var array
     */
    private static $canCheckStack = [];
    
    /**
     * Override can() method to ensure direct permissions are properly checked
     * 
     * CRITICAL FIX: Spatie Permission's default can() method doesn't properly recognize
     * direct permissions in some cases. This override ensures direct permissions are
     * checked first, then role permissions, then falls back to parent for policies.
     * 
     * This method works for ALL permissions - both direct and role-based.
     * When a super admin assigns ANY direct permission to ANY user, this method
     * will properly recognize it and return true, making menu items appear.
     * 
     * CRITICAL: Uses raw DB queries to avoid relationship loading loops that cause
     * infinite recursion and memory exhaustion.
     * 
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        // CRITICAL: Only process string abilities to avoid infinite loops
        // If ability is not a string, fall through to parent immediately
        if (!is_string($ability) || empty($ability)) {
            return parent::can($ability, $arguments);
        }

        // Admin and super_admin bypass all web permission checks (menus, routes, middleware)
        if ($this->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // CRITICAL: Prevent infinite recursion by tracking active checks
        $checkKey = $this->id . ':' . $ability;
        if (isset(self::$canCheckStack[$checkKey])) {
            // We're already checking this permission for this user - prevent infinite loop
            return false;
        }
        
        try {
            // Mark this check as active
            self::$canCheckStack[$checkKey] = true;
            
            // CRITICAL: Use raw DB queries to avoid ANY relationship access
            // This prevents infinite loops caused by relationship lazy loading
            
            // Check direct permissions using raw query
            $hasDirectPermission = \DB::table('model_has_permissions')
                ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                ->where('model_has_permissions.model_id', $this->id)
                ->where('model_has_permissions.model_type', self::class)
                ->where('permissions.name', $ability)
                ->where('permissions.guard_name', 'web')
                ->exists();
            
            if ($hasDirectPermission) {
                return true;
            }
            
            // Check role permissions using raw query
            $hasRolePermission = \DB::table('role_has_permissions')
                ->join('model_has_roles', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->where('model_has_roles.model_id', $this->id)
                ->where('model_has_roles.model_type', self::class)
                ->where('permissions.name', $ability)
                ->where('permissions.guard_name', 'web')
                ->exists();
            
            if ($hasRolePermission) {
                return true;
            }
            
            // Fall back to parent can() method for policies and other abilities
            return parent::can($ability, $arguments);
        } finally {
            // Always remove from stack, even if exception occurs
            unset(self::$canCheckStack[$checkKey]);
        }
    }
}