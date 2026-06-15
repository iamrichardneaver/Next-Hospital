<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\API\PatientController;
use App\Http\Controllers\API\AppointmentController;
use App\Http\Controllers\API\DoctorScheduleController;
use App\Http\Controllers\API\AppointmentSlotController;
use App\Http\Controllers\API\AppointmentFeeController;
use App\Http\Controllers\API\ConsultationController;
use App\Http\Controllers\API\WardController;
use App\Http\Controllers\API\BedController;
use App\Http\Controllers\API\BedAssignmentController;
use App\Http\Controllers\API\PharmacyController;
use App\Http\Controllers\API\LabController;
use App\Http\Controllers\API\LabInventoryController;
use App\Http\Controllers\API\BillingController;
use App\Http\Controllers\API\PaystackWebhookController;
use App\Http\Controllers\API\PricingController;
use App\Http\Controllers\API\InsuranceController;
use App\Http\Controllers\API\ECommerceController;
use App\Http\Controllers\API\EmergencyController;
use App\Http\Controllers\API\SurgeryController;
use App\Http\Controllers\API\RadiologyController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\PrescriptionController;
use App\Http\Controllers\API\FileUploadController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\SyncController;
use App\Http\Controllers\API\ReportingController;
use App\Http\Controllers\API\PatientProfileController;
use App\Http\Controllers\API\EmergencyAlertController;
use App\Http\Controllers\API\EyeServiceController;
use App\Http\Controllers\API\EyeTestRequestController;
use App\Http\Controllers\API\EyeTestResultController;
use App\Http\Controllers\API\VisitController;
use App\Http\Controllers\API\QueueController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BranchController;
use App\Http\Controllers\API\ComplaintController;
use App\Http\Controllers\API\PrintSettingsController;
use App\Http\Controllers\API\DebtorController;
use App\Http\Controllers\API\RealTimeDataController;
use App\Http\Controllers\API\GlobalSearchController;
use App\Http\Controllers\API\VitalsController;
use App\Http\Controllers\API\RevenueAnalyticsController;
use App\Http\Controllers\API\CashierController;
use App\Http\Controllers\API\ExpenseController;
use App\Http\Controllers\API\AccountingController;
use App\Http\Controllers\API\PermissionSyncController;
use App\Http\Controllers\API\LabPurchaseController;
use App\Http\Controllers\API\PharmacyPurchaseController;
use App\Http\Controllers\API\PharmacySupplierController;
use App\Http\Controllers\API\RadiologyPurchaseController;
use App\Http\Controllers\API\PatientDependentController;
use App\Http\Controllers\API\PatientPaymentMethodController;
use App\Http\Controllers\API\PatientCartController;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\BloodBankController;
use App\Http\Controllers\API\IcuController;
use App\Http\Controllers\API\GhsReportController;
use App\Http\Controllers\API\NhisClaimController;
use App\Http\Controllers\API\AppVersionController;
use App\Http\Controllers\API\WorkflowController;
use App\Http\Controllers\API\TeleconsultationController;
use App\Http\Controllers\API\TeleconsultationChatController;
use App\Http\Controllers\API\TeleconsultationFileController;
use App\Http\Controllers\API\ConsultationTemplateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Consultation Templates API - Full CRUD
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('consultation-templates', ConsultationTemplateController::class)->names([
        'index' => 'api.consultation-templates.index',
        'store' => 'api.consultation-templates.store',
        'show' => 'api.consultation-templates.show',
        'update' => 'api.consultation-templates.update',
        'destroy' => 'api.consultation-templates.destroy'
    ]);
    Route::get('consultation-templates/specialties/list', [ConsultationTemplateController::class, 'getSpecialties']);
});

// Authentication Routes (public) - Consolidated into prefix group below

// Global Search — authenticated staff only (PHI); rate limited
Route::middleware(['auth:sanctum', 'throttle:60,1'])->get('/search', [GlobalSearchController::class, 'search'])->name('api.search');

// Doctor Detail (public - for patient app to view doctor profiles)
Route::get('/doctors/{id}/detail', [\App\Http\Controllers\API\UserController::class, 'getDoctorDetail']);
Route::get('/doctors/{id}/reviews', [\App\Http\Controllers\API\DoctorReviewController::class, 'index']);

// Public Settings Configuration (no auth required - needed before login)
Route::get('/settings/mobile-app', [SettingsController::class, 'getMobileAppConfig']);

// App Version Check (public - no auth required - needed for forced update)
Route::get('/app-version/check', [AppVersionController::class, 'checkVersion']);

// Paystack Webhook & Callback (no auth required - Paystack needs direct access)
Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handleWebhook'])->name('paystack.webhook');
Route::get('/paystack/callback', [PaystackWebhookController::class, 'handleCallback'])->name('paystack.callback');
Route::get('/settings/api/mobile-config', [SettingsController::class, 'getMobileApiConfig']);
Route::get('/settings/api/frontend-config', [SettingsController::class, 'getFrontendApiConfig']);
Route::get('/settings/ecommerce', [SettingsController::class, 'getEcommerceSettings']);
Route::get('/settings/public', [SettingsController::class, 'public']);
Route::get('/settings/branding', [SettingsController::class, 'getBrandingSettings']);
Route::get('/settings/general', [SettingsController::class, 'getGeneralSettings']);
Route::get('/settings/all', [SettingsController::class, 'index']); // Alias for frontend compatibility

// Emergency Contact Information (public - no auth required for emergency access)
Route::get('/emergency-contacts', [App\Http\Controllers\API\EmergencyContactController::class, 'index']);
Route::get('/emergency-contacts/branch/{branchId}', [App\Http\Controllers\API\EmergencyContactController::class, 'getBranchEmergencyContacts']);

// Settings API Routes
Route::middleware('auth:sanctum')->group(function () {
    // General settings (admin only)
    Route::get('/settings', [SettingsController::class, 'index']);
    
    // Jitsi Meet settings
    Route::get('/jitsi/settings', [App\Http\Controllers\API\JitsiSettingsController::class, 'getSettings']);
    Route::put('/jitsi/settings', [App\Http\Controllers\API\JitsiSettingsController::class, 'updateSettings']);
    Route::get('/jitsi/test-connection', [App\Http\Controllers\API\JitsiSettingsController::class, 'testConnection']);
    
    // Branding settings (admin only - for updating)
    Route::post('/settings/branding', [SettingsController::class, 'updateBranding']);
    Route::post('/settings/general', [SettingsController::class, 'updateGeneralSettings']);
    
    // System settings
    Route::post('/settings/system', [SettingsController::class, 'updateSystem']);
    
    // Mobile app settings (admin only - for updating)
    Route::get('/settings/mobile-app-admin', [SettingsController::class, 'getMobileAppSettings']);
    Route::post('/settings/mobile-app', [SettingsController::class, 'updateMobileApp']);
    
    // Emergency Contact Management (admin only - for updating)
    Route::post('/emergency-contacts/branch/{branchId}', [App\Http\Controllers\API\EmergencyContactController::class, 'updateBranchEmergencyContacts']);
    
    // Email settings
    Route::get('/settings/email', [SettingsController::class, 'getEmailSettings']);
    Route::post('/settings/email', [SettingsController::class, 'updateEmail']);
    
    // SMS settings
    Route::get('/settings/sms', [SettingsController::class, 'getSmsSettings']);
    Route::post('/settings/sms', [SettingsController::class, 'updateSms']);
    Route::post('/settings/sms/test', [SettingsController::class, 'testSms']);
    
    // Payment settings
    Route::get('/settings/payment', [SettingsController::class, 'getPaymentSettings']);
    Route::post('/settings/payment', [SettingsController::class, 'updatePayment']);
    
    // ID Prefix settings
    Route::get('/settings/id-prefixes', [SettingsController::class, 'getIdPrefixSettings']);
    Route::post('/settings/id-prefixes', [SettingsController::class, 'createIdPrefix']);
    Route::put('/settings/id-prefixes/{entityType}', [SettingsController::class, 'updateIdPrefix']);
    Route::post('/settings/id-prefixes/test', [SettingsController::class, 'generateTestId']);
    Route::post('/settings/id-prefixes/validate', [SettingsController::class, 'validatePattern']);
    Route::post('/settings/id-prefixes/reset-sequence', [SettingsController::class, 'resetSequence']);
    Route::post('/settings/id-prefixes/lock', [SettingsController::class, 'lockSetting']);
    Route::post('/settings/id-prefixes/unlock', [SettingsController::class, 'unlockSetting']);
    
    // Document settings
    Route::get('/settings/documents', [SettingsController::class, 'getDocumentSettings']);
    Route::post('/settings/documents', [SettingsController::class, 'updateDocumentSettings']);
    
    // Sync settings
    Route::get('/settings/sync', [SettingsController::class, 'getSyncSettings']);
    Route::post('/settings/sync', [SettingsController::class, 'updateSyncSettings']);
    
    // API settings
    Route::get('/settings/api', [SettingsController::class, 'getApiSettings']);
    Route::post('/settings/api', [SettingsController::class, 'updateApiSettings']);
    
    // Mobile app configuration
    Route::get('/settings/mobile-config', [SettingsController::class, 'getMobileAppConfig']);
    Route::get('/settings/app-config', [SettingsController::class, 'getAppConfiguration']);
    
    // Settings validation and testing
    Route::post('/settings/test-email', [SettingsController::class, 'testEmail']);
    Route::post('/settings/test-payment', [SettingsController::class, 'testPayment']);
    Route::post('/settings/validate', [SettingsController::class, 'validateSettings']);
    
    // Settings backup and restore
    Route::get('/settings/backup', [SettingsController::class, 'backupSettings']);
    Route::post('/settings/restore', [SettingsController::class, 'restoreSettings']);
    Route::get('/settings/export', [SettingsController::class, 'exportSettings']);
    Route::post('/settings/import', [SettingsController::class, 'importSettings']);
    
    // Settings audit
    Route::get('/settings/audit', [SettingsController::class, 'getSettingsAudit']);
    
    // Maintenance mode
    Route::get('/settings/maintenance', [SettingsController::class, 'maintenanceStatus']);
});

// Authentication routes (public)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('register-patient', [AuthController::class, 'registerPatient']); // Mobile app patient registration
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum'); // Alias for 'me'
});

// Public branches endpoint for branch switcher
Route::get('branches/public', [BranchController::class, 'getActiveBranches']);

// Public appointment routes (MUST be before auth middleware to avoid conflicts and allow guest access)
Route::get('appointments/available-dates', [AppointmentController::class, 'getAvailableDates']);
Route::get('appointments/available-time-slots', [AppointmentController::class, 'getAvailableTimeSlots']);
Route::get('appointments/available-slots', [AppointmentController::class, 'getAvailableTimeSlots']); // Alias

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::post('devices/register', [DeviceController::class, 'register']);
    Route::post('devices/unregister', [DeviceController::class, 'unregister']);

    // Patient Dependents Management (authenticated patients only)
    Route::prefix('patient/dependents')->group(function () {
        Route::get('/', [PatientDependentController::class, 'index']);
        Route::post('/', [PatientDependentController::class, 'store']);
        Route::put('/{id}', [PatientDependentController::class, 'update']);
        Route::delete('/{id}', [PatientDependentController::class, 'destroy']);
    });
    
    // Patient Payment Methods (authenticated patients only)
    Route::prefix('patient/payment-methods')->group(function () {
        Route::get('/', [PatientPaymentMethodController::class, 'index']);
        Route::post('/', [PatientPaymentMethodController::class, 'store']);
        Route::post('/{id}/set-default', [PatientPaymentMethodController::class, 'setDefault']);
        Route::delete('/{id}', [PatientPaymentMethodController::class, 'destroy']);
    });
    
    // Patient Cart Management (authenticated patients only)
    Route::prefix('patient/cart')->group(function () {
        Route::get('/', [PatientCartController::class, 'index']);
        Route::get('/summary', [PatientCartController::class, 'getCartSummary']);
        Route::post('/add', [PatientCartController::class, 'addItem']);
        Route::put('/{id}/quantity', [PatientCartController::class, 'updateQuantity']);
        Route::delete('/{id}', [PatientCartController::class, 'removeItem']);
        Route::delete('/', [PatientCartController::class, 'clearCart']);
    });

    // Patient Complaints (authenticated patients only — scoped by patient_id in controller)
    Route::prefix('patient/complaints')->group(function () {
        Route::get('/', [ComplaintController::class, 'getMyComplaints']);
        Route::post('/', [ComplaintController::class, 'storeMyComplaint']);
        Route::get('/{complaint}', [ComplaintController::class, 'showMyComplaint']);
    });

    // Patient insurance self-service (authenticated patients)
    Route::prefix('patient/insurance')->group(function () {
        Route::get('providers', [InsuranceController::class, 'listActiveProviders']);
        Route::get('policies', [InsuranceController::class, 'getMyPolicies']);
        Route::post('policies', [InsuranceController::class, 'registerMyPolicy']);
    });
    
    // Visit Management
    Route::middleware(['permission:view_visits'])->group(function () {
        Route::apiResource('visits', VisitController::class)->names([
            'index' => 'api.visits.index',
            'store' => 'api.visits.store',
            'show' => 'api.visits.show',
            'update' => 'api.visits.update',
            'destroy' => 'api.visits.destroy'
        ]);
        Route::get('visits/statistics', [VisitController::class, 'statistics']);
    });
    
    Route::middleware(['permission:manage_visits'])->group(function () {
        Route::post('visits/{id}/complete', [VisitController::class, 'complete']);
        Route::post('visits/{id}/cancel', [VisitController::class, 'cancel']);
    });
    
    // Queue Management
    Route::middleware(['permission:view_queues'])->group(function () {
        Route::apiResource('queues', QueueController::class)->names([
            'index' => 'api.queues.index',
            'store' => 'api.queues.store',
            'show' => 'api.queues.show',
            'update' => 'api.queues.update',
            'destroy' => 'api.queues.destroy'
        ]);
        Route::get('queues/status/{queueType}', [QueueController::class, 'getQueueStatus']);
        Route::get('queues/statistics', [QueueController::class, 'statistics']);
    });
    
    Route::middleware(['permission:manage_queues'])->group(function () {
        Route::post('queues/call-next', [QueueController::class, 'callNext']);
        Route::post('queues/{id}/start-serving', [QueueController::class, 'startServing']);
        Route::post('queues/{id}/complete-serving', [QueueController::class, 'completeServing']);
        Route::post('queues/{id}/no-show', [QueueController::class, 'markNoShow']);
        Route::post('queues/{id}/cancel', [QueueController::class, 'cancel']);
        Route::post('queues/reorder', [QueueController::class, 'reorder']);
    });
    
    // Lab-specific queue routes
    Route::middleware(['permission:view_lab_queue'])->group(function () {
        Route::get('queues/{id}/lab-details', [QueueController::class, 'labDetails']);
    });
    
    // Print ticket routes
    Route::middleware(['permission:view_queues'])->group(function () {
        Route::get('queues/{id}/print-ticket', [QueueController::class, 'printTicket']);
        Route::get('queues/{id}/reprint-ticket', [QueueController::class, 'reprintTicket']);
    });
    
    // OPD Queue Management
    Route::middleware(['permission:view_queues'])->group(function () {
        Route::get('opd-queue/status', [QueueController::class, 'getOPDQueueStatus']);
    });
    
    Route::middleware(['permission:call_patients|serve_patients|manage_opd_queue|manage_queues'])->group(function () {
        Route::post('opd-queue/call-next', [QueueController::class, 'callNextOPDPatient']);
        Route::post('opd-queue/{queueId}/start-serving', [QueueController::class, 'startOPDServing']);
        Route::post('opd-queue/{queueId}/complete-serving', [QueueController::class, 'completeOPDServing']);
    });
    
    // Consultation Management
    Route::post('visits/{visitId}/consultation', [App\Http\Controllers\API\ConsultationController::class, 'createFromOPDVisit']);
    Route::post('consultations/{id}/complete', [App\Http\Controllers\API\ConsultationController::class, 'completeOPDConsultation']);
    Route::get('consultations/{id}', [App\Http\Controllers\API\ConsultationController::class, 'show']);
    Route::get('consultations/{id}/lab-requests', [App\Http\Controllers\API\ConsultationController::class, 'getLabRequests']);
    Route::get('patients/{patientId}/consultations', [App\Http\Controllers\API\ConsultationController::class, 'getByPatient']);
    Route::get('consultations/doctor/queue', [App\Http\Controllers\API\ConsultationController::class, 'getDoctorQueue']);
    Route::get('consultations/doctor/completed', [App\Http\Controllers\API\ConsultationController::class, 'getCompletedConsultations']);
    Route::post('consultations/call-next', [App\Http\Controllers\API\ConsultationController::class, 'callNextConsultation']);
    
    // Workflow Management (Mobile API)
    Route::prefix('workflow')->name('workflow.')->group(function () {
        Route::get('/instance/{workflowInstance}/status', [\App\Http\Controllers\API\WorkflowController::class, 'getStatus'])->name('status');
        Route::get('/instance/{workflowInstance}/progress', [\App\Http\Controllers\API\WorkflowController::class, 'getProgress'])->name('progress');
        Route::get('/instance/{workflowInstance}/next-step', [\App\Http\Controllers\API\WorkflowController::class, 'getNextStep'])->name('next-step');
        Route::post('/instance/{workflowInstance}/complete-step', [\App\Http\Controllers\API\WorkflowController::class, 'completeStep'])->name('complete-step');
        Route::post('/log-action', [\App\Http\Controllers\API\WorkflowController::class, 'logAction'])->name('log-action');
    });

    // Allow patients to access and update their own profile via /patients/me (must be before patients/{patient} to avoid "me" being bound as id)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('patients/me', [PatientController::class, 'me'])->name('api.patients.me');
        Route::put('patients/me', [PatientController::class, 'updateMe'])->name('api.patients.updateMe');
    });

    // Patient Management
    Route::middleware(['permission:view_patients'])->group(function () {
        Route::apiResource('patients', PatientController::class)->names([
            'index' => 'api.patients.index',
            'store' => 'api.patients.store',
            'show' => 'api.patients.show',
            'update' => 'api.patients.update',
            'destroy' => 'api.patients.destroy'
        ]);
        Route::get('patients/search', [PatientController::class, 'search'])->name('api.patients.search');
        Route::get('patients/statistics', [PatientController::class, 'statistics'])->name('api.patients.statistics');
    });
    
    // Comprehensive Patient Profile - Allow patients to access their own data
    // Authorization is handled in the controller
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('patients/{patient}/comprehensive-profile', [PatientProfileController::class, 'getComprehensiveProfile']);
        // Debug-only profile test endpoint (requires auth + ownership check in controller)
        if (config('app.debug')) {
            Route::get('patients/{patient}/test-profile', [PatientProfileController::class, 'testComprehensiveProfile']);
            Route::get('test-patient/{patient}', [PatientProfileController::class, 'testComprehensiveProfile']);
        }
        Route::get('patients/{patient}/financial-summary', [PatientProfileController::class, 'getFinancialSummary']);
        Route::get('patients/{patient}/timeline', [PatientProfileController::class, 'getPatientTimeline']);
    });
    
    // Patient search and ensure - require view_patients permission
    Route::middleware(['permission:view_patients'])->group(function () {
        Route::get('patients-search', [PatientProfileController::class, 'searchPatients']);
        Route::post('patients/ensure', [PatientProfileController::class, 'ensurePatientRecord']);
    });
    
    // Patient & doctor self-service appointments (controllers scope by authenticated user)
    Route::get('appointments/today', [AppointmentController::class, 'today']);
    Route::get('appointments/upcoming', [AppointmentController::class, 'upcoming']);
    Route::get('appointments/user', [AppointmentController::class, 'getUserAppointments']);
    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('appointments/{appointment}/join-virtual', [AppointmentController::class, 'joinVirtualAppointment']);
    Route::get('appointments/{appointment}/can-cancel', [AppointmentController::class, 'canCancelAppointment']);
    Route::post('patient/appointments/{id}/reschedule', [AppointmentController::class, 'reschedule']);
    Route::post('doctors/{id}/reviews', [\App\Http\Controllers\API\DoctorReviewController::class, 'store']);

    // Staff appointment management (requires view_appointments permission)
    Route::middleware(['permission:view_appointments'])->group(function () {
        Route::get('appointments/cost-estimate', [AppointmentController::class, 'getCostEstimate']);
        Route::get('appointments/reminders', [AppointmentController::class, 'getReminders']);
        Route::get('appointments/statistics', [AppointmentController::class, 'getStatistics']);
        Route::get('doctors/{doctor}/availability', [AppointmentController::class, 'getDoctorAvailability']);

        Route::apiResource('appointments', AppointmentController::class)->only(['index', 'show', 'update', 'destroy'])->names([
            'index' => 'api.appointments.index',
            'show' => 'api.appointments.show',
            'update' => 'api.appointments.update',
            'destroy' => 'api.appointments.destroy',
        ]);

        Route::post('appointments/{appointment}/complete', [AppointmentController::class, 'complete']);
        Route::post('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule']);
    });
    Route::post('appointments/{appointment}/send-reminder', [AppointmentController::class, 'sendReminder']);
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);

    // Doctor Schedule Management
    // Allow doctors to manage their own schedules (self-service) and admins with permission
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('doctor-schedules', DoctorScheduleController::class);
        Route::get('doctor-schedules/available-slots', [DoctorScheduleController::class, 'getAvailableSlots']);
        Route::post('doctor-schedules/generate-slots', [DoctorScheduleController::class, 'generateSlots']);
        Route::get('doctor-schedules/weekly-schedule', [DoctorScheduleController::class, 'getWeeklySchedule']);
    });

    // Appointment Slot Management
    Route::middleware(['permission:view_appointment_slots'])->group(function () {
        Route::apiResource('appointment-slots', AppointmentSlotController::class);
        Route::get('appointment-slots/available', [AppointmentSlotController::class, 'getAvailableSlots']);
        Route::post('appointment-slots/bulk-create', [AppointmentSlotController::class, 'bulkCreate']);
    });
    Route::post('appointment-slots/{slot}/block', [AppointmentSlotController::class, 'blockSlot']);
    Route::post('appointment-slots/{slot}/unblock', [AppointmentSlotController::class, 'unblockSlot']);
    Route::get('appointment-slots/statistics', [AppointmentSlotController::class, 'getStatistics']);

    // Appointment Fee Management
    Route::middleware(['permission:view_appointment_fees'])->group(function () {
        Route::apiResource('appointment-fees', AppointmentFeeController::class);
        Route::post('appointment-fees/calculate', [AppointmentFeeController::class, 'calculateFee']);
        Route::get('doctors/{doctor}/fees', [AppointmentFeeController::class, 'getDoctorFees']);
    });
    Route::get('branches/{branch}/fees', [AppointmentFeeController::class, 'getBranchFees']);
    Route::get('appointment-fees/statistics', [AppointmentFeeController::class, 'getStatistics']);
    
    // Consultation Management
    Route::middleware(['permission:view_consultations'])->group(function () {
        Route::get('consultations/today', [ConsultationController::class, 'today']);
        Route::get('consultations/ongoing', [ConsultationController::class, 'ongoing']);
        Route::get('consultations/doctor/{doctorId}', [ConsultationController::class, 'getDoctorConsultations']);
        Route::get('consultations/statistics', [ConsultationController::class, 'getStatistics']);
        Route::post('consultations/{consultation}/vitals', [ConsultationController::class, 'addVitals']);
        Route::post('consultations/{consultation}/diagnosis', [ConsultationController::class, 'addDiagnosis']);
        Route::post('consultations/{consultation}/intervention', [ConsultationController::class, 'addIntervention']);
        Route::get('consultations/{consultation}/lab-requests', [ConsultationController::class, 'getLabRequests']);
        Route::get('consultations/{consultation}/scans', [ConsultationController::class, 'getScans']);
        Route::post('consultations/{consultation}/scans', [ConsultationController::class, 'createScan']);
        Route::put('consultations/{consultation}/scans/{scan}', [ConsultationController::class, 'updateScan']);
        Route::delete('consultations/{consultation}/scans/{scan}', [ConsultationController::class, 'deleteScan']);
        Route::apiResource('consultations', ConsultationController::class)->names([
            'index' => 'api.consultations.index',
            'store' => 'api.consultations.store',
            'show' => 'api.consultations.show',
            'update' => 'api.consultations.update',
            'destroy' => 'api.consultations.destroy'
        ]);
    });
    
    // Consultation Diagnoses Management
    Route::middleware(['permission:view_consultations'])->group(function () {
        Route::get('consultations/{consultation}/diagnoses', [App\Http\Controllers\API\DiagnosisController::class, 'index']);
        Route::post('consultations/{consultation}/diagnoses', [App\Http\Controllers\API\DiagnosisController::class, 'store']);
        Route::get('consultations/{consultation}/diagnoses/{diagnosis}', [App\Http\Controllers\API\DiagnosisController::class, 'show']);
        Route::put('consultations/{consultation}/diagnoses/{diagnosis}', [App\Http\Controllers\API\DiagnosisController::class, 'update']);
        Route::delete('consultations/{consultation}/diagnoses/{diagnosis}', [App\Http\Controllers\API\DiagnosisController::class, 'destroy']);
        Route::post('consultations/{consultation}/diagnoses/{diagnosis}/mark-primary', [App\Http\Controllers\API\DiagnosisController::class, 'markAsPrimary']);
        Route::get('patients/{patient}/diagnoses', [App\Http\Controllers\API\DiagnosisController::class, 'getByPatient']);
    });
    
    // Consultation Follow-ups Management
    Route::middleware(['permission:view_consultations'])->group(function () {
        Route::get('consultations/{consultation}/follow-ups', [App\Http\Controllers\API\FollowUpController::class, 'index']);
        Route::post('consultations/{consultation}/follow-ups', [App\Http\Controllers\API\FollowUpController::class, 'store']);
        Route::get('consultations/{consultation}/follow-ups/{followUp}', [App\Http\Controllers\API\FollowUpController::class, 'show']);
        Route::put('consultations/{consultation}/follow-ups/{followUp}', [App\Http\Controllers\API\FollowUpController::class, 'update']);
        Route::delete('consultations/{consultation}/follow-ups/{followUp}', [App\Http\Controllers\API\FollowUpController::class, 'destroy']);
        Route::post('consultations/{consultation}/follow-ups/{followUp}/complete', [App\Http\Controllers\API\FollowUpController::class, 'markAsCompleted']);
        Route::post('consultations/{consultation}/follow-ups/{followUp}/reschedule', [App\Http\Controllers\API\FollowUpController::class, 'reschedule']);
        Route::get('follow-ups/overdue', [App\Http\Controllers\API\FollowUpController::class, 'getOverdue']);
    });
    
    // Consultation Referrals Management
    Route::middleware(['permission:view_consultations'])->group(function () {
        Route::get('consultations/{consultation}/referrals', [App\Http\Controllers\API\ReferralController::class, 'index']);
        Route::post('consultations/{consultation}/referrals', [App\Http\Controllers\API\ReferralController::class, 'store']);
        Route::get('consultations/{consultation}/referrals/{referral}', [App\Http\Controllers\API\ReferralController::class, 'show']);
        Route::put('consultations/{consultation}/referrals/{referral}', [App\Http\Controllers\API\ReferralController::class, 'update']);
        Route::delete('consultations/{consultation}/referrals/{referral}', [App\Http\Controllers\API\ReferralController::class, 'destroy']);
        Route::post('consultations/{consultation}/referrals/{referral}/accept', [App\Http\Controllers\API\ReferralController::class, 'accept']);
        Route::post('consultations/{consultation}/referrals/{referral}/complete', [App\Http\Controllers\API\ReferralController::class, 'complete']);
        Route::get('referrals/pending', [App\Http\Controllers\API\ReferralController::class, 'getPending']);
    });
    
    // Consultation Interventions Management
    Route::middleware(['permission:view_consultations'])->group(function () {
        Route::get('consultations/{consultation}/interventions', [App\Http\Controllers\API\ConsultationInterventionController::class, 'index']);
        Route::post('consultations/{consultation}/interventions', [App\Http\Controllers\API\ConsultationInterventionController::class, 'store']);
        Route::get('consultations/{consultation}/interventions/{intervention}', [App\Http\Controllers\API\ConsultationInterventionController::class, 'show']);
        Route::put('consultations/{consultation}/interventions/{intervention}', [App\Http\Controllers\API\ConsultationInterventionController::class, 'update']);
        Route::delete('consultations/{consultation}/interventions/{intervention}', [App\Http\Controllers\API\ConsultationInterventionController::class, 'destroy']);
        Route::post('consultations/{consultation}/interventions/{intervention}/complete', [App\Http\Controllers\API\ConsultationInterventionController::class, 'markAsCompleted']);
        Route::get('consultations/{consultation}/interventions/by-type', [App\Http\Controllers\API\ConsultationInterventionController::class, 'getByType']);
    });
    
    // Consultation Notes Management
    Route::middleware(['permission:view_consultations'])->group(function () {
        Route::get('consultations/{consultation}/notes', [App\Http\Controllers\API\NoteController::class, 'index']);
        Route::post('consultations/{consultation}/notes', [App\Http\Controllers\API\NoteController::class, 'store']);
        Route::get('consultations/{consultation}/notes/{note}', [App\Http\Controllers\API\NoteController::class, 'show']);
        Route::put('consultations/{consultation}/notes/{note}', [App\Http\Controllers\API\NoteController::class, 'update']);
        Route::delete('consultations/{consultation}/notes/{note}', [App\Http\Controllers\API\NoteController::class, 'destroy']);
        Route::get('consultations/{consultation}/notes/urgent', [App\Http\Controllers\API\NoteController::class, 'getUrgent']);
    });
    
    // Patient Allergies Management
    Route::middleware(['permission:view_patients'])->group(function () {
        Route::get('patients/{patient}/allergies', [App\Http\Controllers\API\PatientAllergyController::class, 'index']);
        Route::post('patients/{patient}/allergies', [App\Http\Controllers\API\PatientAllergyController::class, 'store']);
        Route::get('patients/{patient}/allergies/{allergy}', [App\Http\Controllers\API\PatientAllergyController::class, 'show']);
        Route::put('patients/{patient}/allergies/{allergy}', [App\Http\Controllers\API\PatientAllergyController::class, 'update']);
        Route::delete('patients/{patient}/allergies/{allergy}', [App\Http\Controllers\API\PatientAllergyController::class, 'destroy']);
    });
    
    // Patient Medical History Management
    Route::middleware(['permission:view_patients'])->group(function () {
        Route::get('patients/{patient}/medical-history', [App\Http\Controllers\API\PatientMedicalHistoryController::class, 'index']);
        Route::post('patients/{patient}/medical-history', [App\Http\Controllers\API\PatientMedicalHistoryController::class, 'store']);
        Route::get('patients/{patient}/medical-history/{history}', [App\Http\Controllers\API\PatientMedicalHistoryController::class, 'show']);
        Route::put('patients/{patient}/medical-history/{history}', [App\Http\Controllers\API\PatientMedicalHistoryController::class, 'update']);
        Route::delete('patients/{patient}/medical-history/{history}', [App\Http\Controllers\API\PatientMedicalHistoryController::class, 'destroy']);
        Route::get('patients/{patient}/medical-history/active-conditions', [App\Http\Controllers\API\PatientMedicalHistoryController::class, 'getActiveConditions']);
    });
    
    // Mobile App Patient Endpoints (for patients to access their own data)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('patient/allergies', [App\Http\Controllers\API\PatientAllergyController::class, 'getMyAllergies']);
        Route::get('patient/medical-history', [App\Http\Controllers\API\PatientMedicalHistoryController::class, 'getMyMedicalHistory']);
    });
    
    // Notification Preferences Management
    Route::prefix('notification-preferences')->group(function () {
        Route::get('/', [App\Http\Controllers\API\NotificationPreferenceController::class, 'index']);
        Route::put('/', [App\Http\Controllers\API\NotificationPreferenceController::class, 'update']);
        Route::post('/check-new-work', [App\Http\Controllers\API\NotificationPreferenceController::class, 'checkNewWork']);
    });
    
    // Vitals Management (Standalone)
    Route::middleware(['permission:record_vitals'])->group(function () {
        Route::apiResource('vitals', VitalsController::class)->names([
            'index' => 'api.vitals.index',
            'store' => 'api.vitals.store',
            'show' => 'api.vitals.show',
            'update' => 'api.vitals.update',
            'destroy' => 'api.vitals.destroy'
        ]);
        Route::get('patients/{patient}/vitals', [VitalsController::class, 'getPatientVitals']);
        Route::get('vitals/statistics', [VitalsController::class, 'getStatistics']);
    });
    
    // Patient-specific vitals endpoint (controller scopes to authenticated patient)
    Route::get('vitals/me', [VitalsController::class, 'getMyVitals']);
    
    // Ward & Bed Management
    Route::middleware(['permission:view_wards'])->group(function () {
        Route::get('wards/statistics', [WardController::class, 'getStatistics']);
        Route::apiResource('wards', WardController::class)->names([
            'index' => 'api.wards.index',
            'store' => 'api.wards.store',
            'show' => 'api.wards.show',
            'update' => 'api.wards.update',
            'destroy' => 'api.wards.destroy'
        ]);
        Route::apiResource('beds', BedController::class);
        Route::get('beds/availability', [BedController::class, 'getAvailability']);
        Route::get('beds/{id}/active-assignment', [BedController::class, 'getActiveAssignment']);
        Route::apiResource('bed-assignments', BedAssignmentController::class);
        Route::post('visits/{visitId}/admit', [BedAssignmentController::class, 'admitFromVisit']);
        Route::get('bed-assignments/available-beds', [BedAssignmentController::class, 'getAvailableBeds']);
    });
    Route::get('bed-assignments/statistics', [BedAssignmentController::class, 'getAdmissionStatistics']);
    Route::post('bed-assignments/{assignment}/transfer', [BedAssignmentController::class, 'transfer']);
    Route::post('bed-assignments/{assignment}/discharge', [BedAssignmentController::class, 'discharge']);
    
    // Pharmacy Management - Specific routes first to avoid conflicts
    
    // Public drug browsing (for patients/ecommerce) - authenticated users only
    Route::get('drugs/categories', [PharmacyController::class, 'getCategories']);
    Route::get('drugs/search', [PharmacyController::class, 'searchDrugs']);
    Route::get('drugs', [PharmacyController::class, 'index']); // Browse available drugs
    Route::get('drugs/{drug}', [PharmacyController::class, 'show']); // View drug details
    
    // Protected drug management (for staff only)
    Route::middleware(['permission:view_drugs'])->group(function () {
        Route::get('drugs/stock', [PharmacyController::class, 'stock']);
    });
    
    Route::middleware(['permission:create_drugs|edit_drugs|manage_pharmacy_inventory'])->group(function () {
        Route::post('drugs', [PharmacyController::class, 'store']);
        Route::put('drugs/{drug}', [PharmacyController::class, 'update']);
        Route::delete('drugs/{drug}', [PharmacyController::class, 'destroy']);
        Route::post('drugs/{drug}/stock', [PharmacyController::class, 'updateStock']);
    });
    
    // ========== MOBILE APP PRESCRIPTION ROUTES (MUST COME FIRST) ==========
    // Specific routes first to avoid conflicts with generic routes
    Route::get('prescriptions/active', [PrescriptionController::class, 'getActive']);
    Route::get('prescriptions/history', [PrescriptionController::class, 'getHistory']);
    Route::get('prescriptions/medication-reminders', [PrescriptionController::class, 'getMedicationReminders']);
    Route::get('prescriptions/medication-adherence', [PrescriptionController::class, 'getMedicationAdherence']);
    Route::get('prescriptions/statistics', [PrescriptionController::class, 'getStatistics']);
    Route::post('prescriptions/medications/mark-taken', [PrescriptionController::class, 'markMedicationAsTaken']);
    Route::post('prescriptions/medications/side-effects', [PrescriptionController::class, 'reportSideEffects']);
    Route::post('prescriptions/drug-interactions', [PrescriptionController::class, 'getDrugInteractions']);
    Route::post('prescriptions/refill-request', [PrescriptionController::class, 'requestRefill']);
    
    // Generic prescription routes (must come after specific routes)
    Route::get('prescriptions/{prescription}/medications', [PrescriptionController::class, 'getMedications']);
    Route::get('prescriptions/{prescription}/pdf', [PrescriptionController::class, 'downloadPdf']);
    Route::get('prescriptions/{prescription}', [PrescriptionController::class, 'show']);
    Route::get('prescriptions', [PrescriptionController::class, 'getPatientPrescriptions']);
    // ========== END MOBILE APP PRESCRIPTION ROUTES ==========
    
    // Dispensing Workflow (pharmacist staff)
    Route::middleware(['permission:dispense_drugs|manage_pharmacy_inventory'])->group(function () {
        Route::post('prescriptions/add-to-cart', [PharmacyController::class, 'addToCart']);
        Route::post('prescriptions/process-dispensing', [PharmacyController::class, 'processDispensing']);
        Route::get('pharmacy/dispensing-history', [PharmacyController::class, 'getDispensingHistory']);
        Route::post('drug-orders/{order}/dispense', [PharmacyController::class, 'dispenseMedication']);
    });
    
    // Pharmacy Orders Management
    Route::middleware(['permission:view_prescriptions|dispense_drugs'])->group(function () {
        Route::get('pharmacy/orders', [PharmacyController::class, 'getPharmacyOrders']);
        Route::get('pharmacy/orders/{id}', [PharmacyController::class, 'getPharmacyOrderDetails']);
        Route::put('pharmacy/orders/{id}/status', [PharmacyController::class, 'updatePharmacyOrderStatus']);
    });
    
    // Pharmacy Queue Management
    Route::middleware(['permission:view_pharmacy_queue|manage_pharmacy_queue|view_queues'])->group(function () {
        Route::get('pharmacy-queue/status', [PharmacyController::class, 'getPharmacyQueueStatus']);
    });
    Route::middleware(['permission:manage_pharmacy_queue|manage_queues'])->group(function () {
        Route::post('pharmacy-queue/call-next', [PharmacyController::class, 'callNextPharmacyPatient']);
    });
    Route::middleware(['permission:dispense_drugs|manage_pharmacy_inventory'])->group(function () {
        Route::post('visits/{visitId}/prescription', [PharmacyController::class, 'createFromVisit']);
        Route::post('visits/{visitId}/complete-pharmacy', [PharmacyController::class, 'completePharmacyService']);
        Route::post('pharmacy-visits', [PharmacyController::class, 'createPharmacyVisit']);
    });
    
    // WEB ADMIN Prescription Management (Permission-protected)
    Route::middleware(['permission:view_prescriptions'])->group(function () {
        Route::post('prescriptions/create', [PharmacyController::class, 'createPrescription']);
        Route::get('prescriptions/list', [PharmacyController::class, 'getPrescriptions']);
        Route::get('prescriptions/pending', [PharmacyController::class, 'getPendingPrescriptions']);
        Route::get('prescriptions/{id}/details', [PharmacyController::class, 'getPrescriptionDetails']);
        Route::post('prescriptions/{id}/generate-billing', [PharmacyController::class, 'generatePrescriptionBilling']);
        // Note: apiResource routes moved below to avoid conflicts
    });
    
    // Pharmacy Statistics
    Route::get('pharmacy/statistics', [PharmacyController::class, 'getStatistics']);
    Route::get('pharmacy/analytics', [PharmacyController::class, 'analytics']);
    Route::get('pharmacy/stock-alerts', [PharmacyController::class, 'getStockAlerts']);

    // Pharmacy suppliers (parity with web /pharmacy/suppliers)
    Route::middleware(['permission:view_pharmacy_suppliers|manage_pharmacy_suppliers'])->prefix('pharmacy/suppliers')->group(function () {
        Route::get('/', [PharmacySupplierController::class, 'index']);
        Route::get('{supplier}', [PharmacySupplierController::class, 'show']);
        Route::middleware(['permission:manage_pharmacy_suppliers'])->group(function () {
            Route::post('/', [PharmacySupplierController::class, 'store']);
            Route::put('{supplier}', [PharmacySupplierController::class, 'update']);
        });
    });

    // Pharmacy purchase orders (parity with web /pharmacy/purchases)
    Route::middleware(['permission:view_pharmacy_purchases|create_pharmacy_purchases|receive_pharmacy_purchases|manage_pharmacy_inventory'])->prefix('pharmacy/purchases')->group(function () {
        Route::get('form-data', [PharmacyPurchaseController::class, 'formData']);
        Route::get('/', [PharmacyPurchaseController::class, 'index']);
        Route::middleware(['permission:create_pharmacy_purchases'])->group(function () {
            Route::post('/', [PharmacyPurchaseController::class, 'store']);
            Route::post('{purchase}/order', [PharmacyPurchaseController::class, 'markOrdered']);
            Route::post('{purchase}/cancel', [PharmacyPurchaseController::class, 'cancel']);
        });
        Route::get('{purchase}', [PharmacyPurchaseController::class, 'show']);
        Route::middleware(['permission:receive_pharmacy_purchases'])->group(function () {
            Route::post('{purchase}/receive', [PharmacyPurchaseController::class, 'receive']);
        });
    });
    Route::get('prescriptions/{prescriptionId}/check-interactions', [PharmacyController::class, 'checkDrugInteractions']);
    Route::get('patients/{patientId}/prescription-notifications', [PharmacyController::class, 'getPatientNotifications']);
    Route::post('prescription-notifications/{notificationId}/mark-read', [PharmacyController::class, 'markNotificationAsRead']);
    
    // Lab Management - Consolidated Routes
    Route::prefix('lab')->group(function () {
        // Test Templates - Define these first to avoid conflicts
        Route::post('templates', [LabController::class, 'createTemplate']);
        Route::get('templates', [LabController::class, 'getTemplates']);
        Route::get('templates/{id}', [LabController::class, 'getTemplate']);
        Route::get('templates/{id}/parameters', [LabController::class, 'getTemplateParameters']);
        Route::put('templates/{id}', [LabController::class, 'updateTemplate']);
        Route::delete('templates/{id}', [LabController::class, 'deleteTemplate']);
        
        // Template Assignments
        Route::get('template-assignments', [LabController::class, 'getTemplateAssignments']);
        Route::post('template-assignments', [LabController::class, 'createTemplateAssignment']);
        Route::delete('template-assignments/{id}', [LabController::class, 'deleteTemplateAssignment']);
        
        // Lab Requests - Specific routes first to avoid conflicts
        Route::get('requests/{requestId}/results', [LabController::class, 'getTestResults']);
        
        // Template-based Results Entry
        Route::get('templates/{templateId}/results-form', [LabController::class, 'getTemplateResultsForm']);
        Route::post('requests/{requestId}/templates/{templateId}/results', [LabController::class, 'enterTemplateResults']);
        Route::get('requests/{requestId}/templates/{templateId}/dynamic-pdf', [LabController::class, 'generateDynamicTemplateResultPdf']);
        Route::post('requests/{requestId}/results', [LabController::class, 'enterTestResults']);
        Route::post('requests/{requestId}/verify', [LabController::class, 'verifyTestResults']);
        Route::post('requests/{requestId}/approve', [LabController::class, 'approveTestResults']);
        
        // Multi-template workflow
        Route::get('requests/{requestId}/templates', [LabController::class, 'getRequestTemplates']);
        Route::post('requests/{requestId}/templates/{templateId}/assign-technician', [LabController::class, 'assignTechnicianToTemplate']);
        
        // Verification workflow
        Route::get('verification/pending', [LabController::class, 'getPendingVerification']);
        Route::put('results/verify', [LabController::class, 'verifyTestResults']);
        
        Route::apiResource('requests', LabController::class);
        
        // PDF Generation
        Route::get('requests/{requestId}/pdf', [LabController::class, 'generateTestResultsPdf']);
        Route::get('requests/{requestId}/templates/{templateId}/pdf', [LabController::class, 'generateTemplateResultPdf']);
        Route::get('requests/{requestId}/critical-pdf', [LabController::class, 'generateCriticalResultsPdf']);
        Route::get('requests/{requestId}/diagnostic-pdf', [LabController::class, 'generateDiagnosticReportPdf']);
        Route::get('quality-control/pdf', [LabController::class, 'generateQualityControlPdf']);
        
        // Patient-specific endpoints (for mobile app)
        Route::get('patient-results', [LabController::class, 'getPatientResults']);
        Route::get('patient-requests', [LabController::class, 'getPatientRequests']);
        Route::get('patient-statistics', [LabController::class, 'getPatientLabStatistics']);
        Route::get('results/{id}', [LabController::class, 'getLabRequestDetails']);
        
        // Filtering Options
        Route::get('categories', [LabController::class, 'getCategories']);
        Route::get('test-types', [LabController::class, 'getTestTypes']);
        
        // Quality Control
        Route::get('quality-control', [LabController::class, 'getQualityControlRecords']);
        Route::post('quality-control', [LabController::class, 'createQualityControlRecord']);
        Route::put('quality-control/{id}', [LabController::class, 'updateQualityControlRecord']);
        Route::delete('quality-control/{id}', [LabController::class, 'deleteQualityControlRecord']);
        Route::get('equipment-calibrations', [LabController::class, 'getEquipmentCalibrations']);
        Route::post('equipment-calibrations', [LabController::class, 'createEquipmentCalibration']);
        Route::put('equipment-calibrations/{id}', [LabController::class, 'updateEquipmentCalibration']);
        Route::delete('equipment-calibrations/{id}', [LabController::class, 'deleteEquipmentCalibration']);
        
        // Lab Reports
        Route::get('reports', [LabController::class, 'getLabReports']);
        Route::get('report-templates', [LabController::class, 'getReportTemplates']);
        Route::post('reports/generate', [LabController::class, 'generateReport']);
        Route::get('reports/{id}/download', [LabController::class, 'downloadReport']);
        
        // Lab Archive
        Route::get('archive', [LabController::class, 'getArchive']);
        Route::get('archive/patient/{patientId}', [LabController::class, 'getPatientArchive']);
        Route::get('archive/statistics', [LabController::class, 'getArchiveStatistics']);
        
        // Lab Categories Management (CRUD)
        Route::get('categories-management', [LabController::class, 'getLabCategories']);
        Route::post('categories-management', [LabController::class, 'createCategory']);
        Route::get('categories-management/{id}', [LabController::class, 'getLabCategory']);
        Route::put('categories-management/{id}', [LabController::class, 'updateLabCategory']);
        Route::delete('categories-management/{id}', [LabController::class, 'deleteLabCategory']);
        
        // Lab Tests Management (CRUD)
        Route::get('tests-management', [LabController::class, 'getTests']);
        Route::post('tests-management', [LabController::class, 'createTest']);
        Route::get('tests-management/{id}', [LabController::class, 'getLabTest']);
        Route::put('tests-management/{id}', [LabController::class, 'updateLabTest']);
        Route::delete('tests-management/{id}', [LabController::class, 'deleteLabTest']);
        
        // Statistics
        Route::get('statistics', [LabController::class, 'getStatistics']);
        Route::get('results/critical', [LabController::class, 'getCriticalResults']);
        Route::get('results/abnormal', [LabController::class, 'getAbnormalResults']);
        Route::get('results', [LabController::class, 'getPatientResults']);
        
        // Activity
        Route::get('activity/recent', [LabController::class, 'getRecentActivity']);
        
        // Lab Inventory Management
        Route::prefix('inventory')->group(function () {
            // Equipment CRUD
            Route::get('equipment', [LabInventoryController::class, 'getEquipment']);
            Route::get('equipment/stats', [LabInventoryController::class, 'getEquipmentStats']);
            Route::post('equipment', [LabInventoryController::class, 'createEquipment']);
            Route::put('equipment/{id}', [LabInventoryController::class, 'updateEquipment']);
            Route::delete('equipment/{id}', [LabInventoryController::class, 'deleteEquipment']);
            
            // Reagents CRUD
            Route::get('reagents', [LabInventoryController::class, 'getReagents']);
            Route::get('reagents/stats', [LabInventoryController::class, 'getReagentStats']);
            Route::post('reagents', [LabInventoryController::class, 'createReagent']);
            Route::put('reagents/{id}', [LabInventoryController::class, 'updateReagent']);
            Route::delete('reagents/{id}', [LabInventoryController::class, 'deleteReagent']);
            
            // Consumables CRUD
            Route::get('consumables', [LabInventoryController::class, 'getConsumables']);
            Route::get('consumables/stats', [LabInventoryController::class, 'getConsumableStats']);
            Route::post('consumables', [LabInventoryController::class, 'createConsumable']);
            Route::put('consumables/{id}', [LabInventoryController::class, 'updateConsumable']);
            Route::delete('consumables/{id}', [LabInventoryController::class, 'deleteConsumable']);
            
            // Suppliers CRUD
            Route::get('suppliers', [LabInventoryController::class, 'getSuppliers']);
            Route::post('suppliers', [LabInventoryController::class, 'createSupplier']);
            Route::get('suppliers/{id}', [LabInventoryController::class, 'getSupplier']);
            Route::put('suppliers/{id}', [LabInventoryController::class, 'updateSupplier']);
            Route::delete('suppliers/{id}', [LabInventoryController::class, 'deleteSupplier']);
            
            // Transactions
            Route::get('transactions', [LabInventoryController::class, 'getTransactions']);
            
            // Overall Statistics
            Route::get('stats', [LabInventoryController::class, 'getInventoryStats']);
        });

        // Lab purchase orders (parity with web /lab/purchases)
        Route::middleware(['permission:view_lab_purchases|create_lab_purchases|receive_lab_purchases|view_lab_inventory'])->prefix('purchases')->group(function () {
            Route::get('form-data', [LabPurchaseController::class, 'formData']);
            Route::get('/', [LabPurchaseController::class, 'index']);
            Route::middleware(['permission:create_lab_purchases'])->group(function () {
                Route::post('/', [LabPurchaseController::class, 'store']);
                Route::post('{purchase}/order', [LabPurchaseController::class, 'markOrdered']);
                Route::post('{purchase}/cancel', [LabPurchaseController::class, 'cancel']);
            });
            Route::get('{purchase}', [LabPurchaseController::class, 'show']);
            Route::middleware(['permission:receive_lab_purchases'])->group(function () {
                Route::post('{purchase}/receive', [LabPurchaseController::class, 'receive']);
            });
        });
        
        // Legacy Compatibility Routes
        Route::get('lab-test-types', [LabController::class, 'getTestTypesLegacy']);
        Route::get('lab-test-types/categories', [LabController::class, 'getTestCategories']);
        Route::post('lab-requests/{labRequest}/results', [LabController::class, 'storeResults']);
        Route::get('lab-requests/{labRequest}/results', [LabController::class, 'getResults']);
        
        // Walk-in Lab Workflow
        Route::post('visits/{visitId}/lab-request', [LabController::class, 'createFromWalkInVisit']);
        Route::post('visits/{visitId}/complete-lab-service', [LabController::class, 'completeWalkInService']);
        Route::get('lab-queue/status', [LabController::class, 'getLabQueueStatus']);
        Route::post('lab-queue/call-next', [LabController::class, 'callNextLabPatient']);
        Route::post('lab-queue/{queueId}/start-serving', [LabController::class, 'startServingLabPatient']);
    });
    
    // Billing Management
    Route::middleware(['permission:view_invoices|manage_billing|process_payments'])->group(function () {
        Route::apiResource('invoices', BillingController::class)->names([
            'index' => 'api.invoices.index',
            'store' => 'api.invoices.store',
            'show' => 'api.invoices.show',
            'update' => 'api.invoices.update',
            'destroy' => 'api.invoices.destroy'
        ]);
        Route::get('invoices/{invoice}/pdf', [BillingController::class, 'generatePDF']);
        Route::get('invoices/{invoice}/receipt-pdf', [BillingController::class, 'generateReceiptPDF']);
        Route::get('patients/{patient}/billing-statement-pdf', [BillingController::class, 'generateBillingStatementPDF']);
        Route::post('billing/generate-standalone-bill', [BillingController::class, 'generateStandaloneBill']);
        Route::get('billing/available-services', [BillingController::class, 'getAvailableServices']);
        Route::post('invoices/generate-from-consultation/{consultation}', [BillingController::class, 'generateFromConsultation']);
        Route::get('invoices/overdue', [BillingController::class, 'getOverdueInvoices']);
        Route::get('billing/statistics', [BillingController::class, 'getStatistics']);
        Route::get('billing/payment-methods', [BillingController::class, 'getPaymentMethodStats']);
        Route::get('billing/monthly-revenue', [BillingController::class, 'getMonthlyRevenue']);
        Route::get('payments', [BillingController::class, 'getPayments']);
        Route::patch('invoices/{invoice}/status', [BillingController::class, 'updateStatus']);
    });

    Route::middleware(['permission:process_payments|manage_billing|create_payments'])->group(function () {
        Route::post('invoices/{invoice}/payments', [BillingController::class, 'addPayment']);
        Route::post('billing/payments', [BillingController::class, 'createPayment']);
        Route::post('billing/payments/mobile-money', [BillingController::class, 'processMobileMoneyPayment']);
        Route::post('billing/payments/card', [BillingController::class, 'processCardPayment']);
        Route::post('billing/payments/paystack/initialize', [BillingController::class, 'initializePaystackPayment']);
        Route::post('billing/payments/paystack', [BillingController::class, 'processPaystackPayment']);
        Route::post('billing/payments/paystack/verify', [BillingController::class, 'verifyPaystackPayment']);
        Route::post('payments/{payment}/refund', [BillingController::class, 'refundPayment']);
    });

    // Mobile App Billing Routes (patient self-service + staff with billing view)
    Route::middleware(['permission:view_invoices|view_own_invoices|manage_billing|process_payments'])->group(function () {
        Route::get('billing/summary', [BillingController::class, 'getBillingSummary']);
        Route::get('billing/pending-charges', [BillingController::class, 'getPatientPendingCharges']);
        Route::get('billing/invoices', [BillingController::class, 'getPatientInvoices']);
        Route::get('billing/payments', [BillingController::class, 'getPatientPayments']);
        Route::get('billing/insurance-claims', [BillingController::class, 'getInsuranceClaims']);
        Route::post('billing/insurance-claims', [BillingController::class, 'submitInsuranceClaim']);
    });
    
    // Accounting & financial reports (mobile parity with web /accounting/*)
    Route::middleware(['permission:view_financial_dashboard|view_financial_reports|view_revenue_analytics'])->prefix('accounting')->group(function () {
        Route::get('dashboard', [AccountingController::class, 'dashboard']);
    });
    Route::middleware(['permission:view_revenue_reports|view_revenue_analytics'])->prefix('accounting')->group(function () {
        Route::get('revenue', [AccountingController::class, 'revenue']);
        Route::get('revenue/drill-down/{serviceType}', [AccountingController::class, 'revenueDrillDown']);
        Route::get('revenue-vs-expenses', [AccountingController::class, 'revenueVsExpenses']);
    });
    Route::middleware(['permission:view_balance_sheet'])->prefix('accounting')->group(function () {
        Route::get('balance-sheet', [AccountingController::class, 'balanceSheet']);
    });
    Route::middleware(['permission:view_cash_flow'])->prefix('accounting')->group(function () {
        Route::get('cash-flow', [AccountingController::class, 'cashFlow']);
    });

    // Expense management (staff submit + accountant approval)
    Route::middleware(['permission:create_expenses'])->group(function () {
        Route::post('expenses', [ExpenseController::class, 'store']);
        Route::get('expenses/my', [ExpenseController::class, 'myExpenses'])->middleware('permission:view_own_expenses');
    });
    Route::middleware(['permission:view_expenses|manage_expenses|approve_expenses|view_own_expenses'])->group(function () {
        Route::get('expenses/categories', [ExpenseController::class, 'categories']);
        Route::get('expenses', [ExpenseController::class, 'index'])->middleware('permission:view_expenses|manage_expenses|approve_expenses');
        Route::get('expenses/{expense}', [ExpenseController::class, 'show']);
        Route::put('expenses/{expense}', [ExpenseController::class, 'update']);
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);
        Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->middleware('permission:approve_expenses|manage_expenses');
        Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->middleware('permission:approve_expenses|manage_expenses');
        Route::post('expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->middleware('permission:manage_expenses');
    });

    Route::middleware(['permission:view_revenue_analytics'])->group(function () {
        Route::get('revenue/summary', [RevenueAnalyticsController::class, 'getSummary']);
        Route::get('revenue/by-department', [RevenueAnalyticsController::class, 'getRevenueByDepartment']);
        Route::get('revenue/by-payment-method', [RevenueAnalyticsController::class, 'getRevenueByPaymentMethod']);
        Route::get('revenue/daily-trend', [RevenueAnalyticsController::class, 'getDailyTrend']);
        Route::get('revenue/top-services', [RevenueAnalyticsController::class, 'getTopServices']);
        Route::get('revenue/top-drugs', [RevenueAnalyticsController::class, 'getTopDrugs']);
        Route::get('revenue/branch-comparison', [RevenueAnalyticsController::class, 'getBranchComparison']);
        Route::get('revenue/outstanding', [RevenueAnalyticsController::class, 'getOutstandingPayments']);
    });
    
    Route::middleware(['permission:process_payments|view_cashier_reports'])->group(function () {
        Route::get('cashier/daily-report', [CashierController::class, 'getDailyReport']);
    });

    // Cashier Mobile API
    Route::middleware(['permission:process_payments'])->group(function () {
        Route::get('cashier/dashboard', [CashierController::class, 'getDashboard']);
        Route::get('cashier/pending-payments', [CashierController::class, 'getPendingPayments']);
        Route::get('cashier/invoices', [CashierController::class, 'getInvoices']);
        Route::post('cashier/process-payment', [CashierController::class, 'processPayment']);
        Route::post('cashier/process-payment-for-charges', [CashierController::class, 'processPaymentForCharges']);
        Route::get('cashier/payment-history', [CashierController::class, 'getPaymentHistory']);
        Route::get('cashier/daily-summary', [CashierController::class, 'getDailySummary']);
        Route::post('cashier/shift-handover', [CashierController::class, 'shiftHandover']);
        Route::get('cashier/statistics', [CashierController::class, 'getStatistics']);
        Route::post('cashier/search-patient', [CashierController::class, 'searchPatient']);
        Route::get('cashier/patient/{patient}/charges', [CashierController::class, 'getPatientCharges']);
        Route::get('cashier/patient/{patient}/debt-info', [CashierController::class, 'getPatientDebtInfo']);
        Route::get('cashier/pending-payments/all', [CashierController::class, 'getAllPendingPayments']);
        Route::get('cashier/outstanding-debts', [CashierController::class, 'getOutstandingDebts']);
    });
    
    // Mobile App Chat Routes
    Route::get('chat/conversations', [ChatController::class, 'getConversations']);
    Route::post('chat/conversations/start', [ChatController::class, 'startConversation']);
    Route::post('chat/conversations/support', [ChatController::class, 'startSupportConversation']);
    Route::get('chat/conversations/{conversation}/messages', [ChatController::class, 'getMessages']);
    Route::post('chat/messages', [ChatController::class, 'sendMessage']);
    Route::post('chat/messages/mark-read', [ChatController::class, 'markMessagesAsRead']);
    Route::post('chat/upload-attachment', [ChatController::class, 'uploadAttachment']);
    
    // Mobile App Notification Routes
    Route::get('notifications', [NotificationController::class, 'getNotifications']);
    Route::put('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'delete']);
    
    // Mobile App Prescription Routes - MOVED TO LINE 460 (before permission middleware)
    
    // Debtors Management
    Route::middleware(['permission:view_debtors'])->group(function () {
        Route::apiResource('debtors', DebtorController::class)->names([
            'index' => 'api.debtors.index',
            'store' => 'api.debtors.store',
            'show' => 'api.debtors.show',
            'update' => 'api.debtors.update',
            'destroy' => 'api.debtors.destroy'
        ]);
        Route::get('debtors/{debtor}/payment-history', [DebtorController::class, 'paymentHistory']);
        Route::get('debtors/{debtor}/outstanding-invoices', [DebtorController::class, 'outstandingInvoices']);
        Route::get('debtors/report', [DebtorController::class, 'report']);
        Route::post('debtors/send-reminders', [DebtorController::class, 'sendReminders']);
        Route::post('debtors/{debtor}/update-status', [DebtorController::class, 'updateStatus']);
        Route::post('debtors/bulk-update', [DebtorController::class, 'bulkUpdate']);
        Route::get('debtors/statistics', [DebtorController::class, 'getStatistics']);
        Route::post('debtors/record-payment', [DebtorController::class, 'recordPayment']);
    });
    
    // Pricing Management
    Route::post('pricing/calculate/service', [PricingController::class, 'calculateServicePrice']);
    Route::post('pricing/calculate/consultation', [PricingController::class, 'calculateConsultationFee']);
    Route::post('pricing/calculate/lab-test', [PricingController::class, 'calculateLabTestPrice']);
    Route::post('pricing/calculate/drug', [PricingController::class, 'calculateDrugPrice']);
    Route::get('pricing/services', [PricingController::class, 'getServicePricing']);
    Route::post('pricing/services', [PricingController::class, 'storeServicePricing']);
    Route::get('pricing/services/{serviceId}/rules', [PricingController::class, 'getPricingRules']);
    Route::post('pricing/rules', [PricingController::class, 'storePricingRule']);
    Route::get('pricing/discounts', [PricingController::class, 'getDiscountSchemes']);
    Route::post('pricing/discounts', [PricingController::class, 'storeDiscountScheme']);
    
    // Ward Management - Additional routes
    // Bed assignment routes - use BedAssignmentController
    Route::post('beds/assign', [BedAssignmentController::class, 'store']); // Use store method for bed assignment
    Route::post('beds/discharge/{assignment}', [BedAssignmentController::class, 'discharge']);
    Route::post('beds/transfer/{assignment}', [BedAssignmentController::class, 'transfer']);
    Route::get('wards/occupancy', [WardController::class, 'getStatistics']); // Use existing getStatistics method
    Route::get('beds/available', [BedAssignmentController::class, 'getAvailableBeds']);
    // Note: getPatientBedHistory route removed - method doesn't exist, use bed-assignments API resource with patient filter
    
    // Insurance Management
    Route::apiResource('insurance-providers', InsuranceController::class);
    Route::post('insurance-policies', [InsuranceController::class, 'createPolicy']);
    Route::get('patients/{patient}/policies', [InsuranceController::class, 'getPatientPolicies']);
    
    // Insurance Coverage & Validation
    Route::post('insurance/calculate-coverage', [InsuranceController::class, 'calculateCoverage']);
    Route::post('insurance/validate-coverage', [InsuranceController::class, 'validateCoverage']);
    
    // Service Categories
    Route::get('insurance/service-categories', [InsuranceController::class, 'getServiceCategories']);
    
    // Coverage Policies
    Route::get('insurance-providers/{provider}/coverage-policies', [InsuranceController::class, 'getCoveragePolicies']);
    Route::post('insurance/coverage-policies', [InsuranceController::class, 'createCoveragePolicy']);
    
    // Pre-authorizations
    Route::get('pre-authorizations', [InsuranceController::class, 'getPreAuthorizations']);
    Route::post('pre-authorizations', [InsuranceController::class, 'createPreAuthorization']);
    Route::put('pre-authorizations/{preAuth}/status', [InsuranceController::class, 'updatePreAuthorizationStatus']);
    
    // Claims
    Route::post('insurance-claims', [InsuranceController::class, 'submitClaim']);
    Route::post('insurance/process-claim', [InsuranceController::class, 'processClaim']);
    Route::get('insurance-claims', [InsuranceController::class, 'getClaims']);
    Route::put('insurance-claims/{claim}/status', [InsuranceController::class, 'updateClaimStatus']);
    
    // Statistics & Reports
    Route::get('insurance/statistics', [InsuranceController::class, 'getStatistics']);
    Route::get('insurance/analytics', [InsuranceController::class, 'getAnalytics']);
    Route::get('insurance/dashboard', [InsuranceController::class, 'getDashboardData']);
    Route::post('insurance/reports', [InsuranceController::class, 'generateReport']);
    Route::get('patients/{patient}/insurance-stats', [InsuranceController::class, 'getPatientInsuranceStats']);
    
    // E-Commerce Management - Specific routes first to avoid conflicts
    Route::get('store-items/search', [ECommerceController::class, 'searchItems']);
    Route::get('store-items/categories', [ECommerceController::class, 'getCategories']);
    Route::apiResource('store-items', ECommerceController::class);
    Route::post('store-orders', [ECommerceController::class, 'createOrder']);
    Route::get('store-orders', [ECommerceController::class, 'getOrders']);
    Route::get('store-orders/{order}', [ECommerceController::class, 'getOrderDetails']);
    Route::put('store-orders/{order}/status', [ECommerceController::class, 'updateOrderStatus']);
    Route::get('store-orders/{order}/tracking', [ECommerceController::class, 'getDeliveryTracking']);
    Route::put('store-orders/{order}/delivery', [ECommerceController::class, 'updateDeliveryStatus']);
    Route::get('ecommerce/statistics', [ECommerceController::class, 'getStatistics']);
    
    // E-commerce Paystack Payment Routes
    Route::post('ecommerce/orders/paystack/initialize', [ECommerceController::class, 'initializeOrderPayment']);
    Route::post('ecommerce/orders/paystack/process', [ECommerceController::class, 'processOrderPayment']);
    Route::get('ecommerce/orders/{order}/payment/verify', [ECommerceController::class, 'verifyOrderPayment']);
    
    // Appointment Paystack Payment Routes
    Route::post('appointments/paystack/initialize', [AppointmentController::class, 'initializeAppointmentPayment']);
    Route::post('appointments/paystack/process', [AppointmentController::class, 'processAppointmentPayment']);
    
    // E-commerce Integration with Visit System
    Route::post('ecommerce/pharmacy-visit', [ECommerceController::class, 'createPharmacyVisit']);
    Route::post('ecommerce/orders/{orderId}/complete', [ECommerceController::class, 'completeOrder']);
    
    // Branch Management
    Route::apiResource('branches', BranchController::class);
    
    // User Management
    Route::get('users/doctors', [App\Http\Controllers\API\UserController::class, 'doctors']);
    Route::get('users/nurses', [App\Http\Controllers\API\UserController::class, 'nurses']);
    Route::get('users/role/{role}', [App\Http\Controllers\API\UserController::class, 'getUsersByRole']);
    Route::get('users/search', [App\Http\Controllers\API\UserController::class, 'searchUsers']);
    Route::get('users/statistics', [App\Http\Controllers\API\UserController::class, 'getStatistics']);
    Route::post('users/bulk-delete', [App\Http\Controllers\API\UserController::class, 'bulkDestroy']);
    Route::apiResource('users', App\Http\Controllers\API\UserController::class)->names([
        'index' => 'api.users.index',
        'store' => 'api.users.store',
        'show' => 'api.users.show',
        'update' => 'api.users.update',
        'destroy' => 'api.users.destroy'
    ]);
    Route::post('users/{user}/toggle-status', [App\Http\Controllers\API\UserController::class, 'toggleStatus']);
    
    // Role Management
    Route::apiResource('roles', App\Http\Controllers\API\RoleController::class)->names([
        'index' => 'api.roles.index',
        'store' => 'api.roles.store',
        'show' => 'api.roles.show',
        'update' => 'api.roles.update',
        'destroy' => 'api.roles.destroy'
    ]);
    Route::get('roles/{role}/permissions', [App\Http\Controllers\API\RoleController::class, 'show']);
    Route::get('permissions', [App\Http\Controllers\API\RoleController::class, 'getPermissions']);
    Route::get('permissions/grouped', [App\Http\Controllers\API\RoleController::class, 'getPermissionsGrouped']);
    Route::post('permissions', [App\Http\Controllers\API\RoleController::class, 'createPermission']);
    Route::delete('permissions/{permission}', [App\Http\Controllers\API\RoleController::class, 'deletePermission']);
    Route::get('permissions/sync/status', [PermissionSyncController::class, 'status']);
    Route::post('permissions/sync', [PermissionSyncController::class, 'sync']);
    
    // Emergency Alerts - Specific routes FIRST (before general routes to ensure proper matching)
    // These routes are accessible from web interface (web middleware handles session auth)
    Route::get('emergency-alerts/active', [EmergencyAlertController::class, 'getActiveAlerts'])->name('emergency-alerts.active');
    Route::get('emergency-alerts/statistics', [EmergencyAlertController::class, 'getAlertStatistics'])->name('emergency-alerts.statistics');
    Route::get('patients/{patient}/emergency-alerts', [EmergencyAlertController::class, 'getPatientAlerts'])->name('emergency-alerts.patient');
    
    // Emergency Department
    Route::middleware(['permission:view_emergency_visits'])->group(function () {
        Route::get('emergency-visits', [EmergencyController::class, 'index']);
        Route::get('emergency-visits/{emergency_visit}', [EmergencyController::class, 'show']);
        Route::get('emergency-alerts', [EmergencyController::class, 'getActiveAlerts']);
        Route::get('emergency/statistics', [EmergencyController::class, 'getStatistics']);
        Route::get('crash-cart/inventory', [EmergencyController::class, 'getCrashCartInventory']);
        Route::get('emergency-queue/status', [EmergencyController::class, 'getEmergencyQueueStatus']);
    });
    
    Route::middleware(['permission:create_emergency_visits|edit_emergency_visits|delete_emergency_visits'])->group(function () {
        Route::post('emergency-visits', [EmergencyController::class, 'store']);
        Route::put('emergency-visits/{emergency_visit}', [EmergencyController::class, 'update']);
        Route::delete('emergency-visits/{emergency_visit}', [EmergencyController::class, 'destroy']);
        Route::post('emergency-visits/{visit}/triage', [EmergencyController::class, 'updateTriage']);
        Route::post('emergency-visits/{visit}/intervention', [EmergencyController::class, 'addIntervention']);
        Route::post('visits/{visitId}/emergency', [EmergencyController::class, 'createFromVisit']);
        Route::post('visits/{visitId}/complete-emergency', [EmergencyController::class, 'completeEmergencyVisit']);
        Route::post('crash-cart/{cart}/update-item', [EmergencyController::class, 'updateCrashCartItem']);
    });
    
    Route::middleware(['permission:triage_patients|call_patients|serve_patients'])->group(function () {
        Route::post('emergency-queue/call-next', [EmergencyController::class, 'callNextEmergencyPatient']);
    });
    
    Route::middleware(['permission:acknowledge_alerts|resolve_alerts'])->group(function () {
        Route::post('emergency-alerts/{alert}/acknowledge', [EmergencyController::class, 'acknowledgeAlert']);
    });
    
    // Emergency Alerts - General routes (after specific routes)
    Route::get('emergency-alerts', [App\Http\Controllers\API\EmergencyAlertController::class, 'index']);
    Route::post('emergency-alerts', [App\Http\Controllers\API\EmergencyAlertController::class, 'store']);
    Route::get('emergency-alerts/{id}', [App\Http\Controllers\API\EmergencyAlertController::class, 'show']);
    Route::post('emergency-alerts/{id}/acknowledge', [App\Http\Controllers\API\EmergencyAlertController::class, 'acknowledge']);
    Route::post('emergency-alerts/{id}/resolve', [App\Http\Controllers\API\EmergencyAlertController::class, 'resolve']);
    
    // Surgery & Theatre Management
    Route::apiResource('surgery-schedules', SurgeryController::class);
    Route::post('surgery-schedules/{surgery}/start', [SurgeryController::class, 'startSurgery']);
    Route::post('surgery-schedules/{surgery}/complete', [SurgeryController::class, 'completeSurgery']);
    Route::get('theatres/{theatre}/availability', [SurgeryController::class, 'getTheatreAvailability']);
    Route::get('surgery/statistics', [SurgeryController::class, 'getStatistics']);
    
    // Radiology & Imaging Management
    Route::get('radiology/requests', [RadiologyController::class, 'getRequests']);
    Route::get('radiology/requests/{id}', [RadiologyController::class, 'showRequest']);
    Route::post('radiology/requests', [RadiologyController::class, 'createRequest']);
    Route::put('radiology/requests/{id}', [RadiologyController::class, 'updateRequest']);
    Route::delete('radiology/requests/{id}', [RadiologyController::class, 'deleteRequest']);
    Route::post('radiology/requests/bulk-update-status', [RadiologyController::class, 'bulkUpdateStatus']);
    Route::post('radiology/requests/bulk-delete', [RadiologyController::class, 'bulkDelete']);
    Route::post('radiology/requests/{id}/schedule', [RadiologyController::class, 'scheduleRequest']);
    Route::post('radiology/requests/{id}/start-study', [RadiologyController::class, 'startStudy']);
    Route::get('radiology/studies', [RadiologyController::class, 'getStudies']);
    Route::get('radiology/studies/{id}', [RadiologyController::class, 'showStudy']);
    Route::post('radiology/studies/{id}/complete', [RadiologyController::class, 'completeStudy']);
    Route::post('radiology/studies/{id}/upload-images', [RadiologyController::class, 'uploadImages']);
    Route::get('radiology/series/{seriesId}/images', [RadiologyController::class, 'getSeriesImages']);
    Route::get('radiology/images/{image}/serve', [RadiologyController::class, 'serveImage'])->name('api.radiology.images.serve');
    Route::get('radiology/images/{image}/file', [RadiologyController::class, 'serveImageFile'])->name('api.radiology.images.file');
    Route::get('radiology/reports', [RadiologyController::class, 'getReports']);
    Route::get('radiology/reports/{id}', [RadiologyController::class, 'showReport']);
    Route::post('radiology/studies/{id}/report', [RadiologyController::class, 'createReport']);
    Route::put('radiology/reports/{id}', [RadiologyController::class, 'updateReport']);
    Route::get('radiology/reports/{id}/pdf', [RadiologyController::class, 'generateReportPdf']);
    Route::get('radiology/studies/{id}/pdf', [RadiologyController::class, 'generateStudySummaryPdf']);
    Route::get('radiology/patients/{id}/history-pdf', [RadiologyController::class, 'generatePatientHistoryPdf']);
    Route::get('radiology/modalities', [RadiologyController::class, 'getModalities']);
    Route::get('radiology/departments', [RadiologyController::class, 'getDepartments']);
    Route::get('radiology/equipment/available', [RadiologyController::class, 'getAvailableEquipment']);
    Route::get('radiology/contrast-agents', [RadiologyController::class, 'getContrastAgents']);
    Route::get('radiology/protocols', [RadiologyController::class, 'getProtocols']);
    Route::get('radiology/technicians', [RadiologyController::class, 'getTechnicians']);
    Route::get('radiology/radiologists', [RadiologyController::class, 'getRadiologists']);

    // Radiology inventory & purchases (parity with web /radiology/inventory, /radiology/purchases)
    Route::middleware(['permission:view_radiology_inventory|view_radiology_purchases|create_radiology_purchases|receive_radiology_purchases'])->prefix('radiology')->group(function () {
        Route::get('inventory', [RadiologyPurchaseController::class, 'inventory']);
        Route::prefix('purchases')->group(function () {
            Route::get('form-data', [RadiologyPurchaseController::class, 'formData']);
            Route::get('/', [RadiologyPurchaseController::class, 'index']);
            Route::middleware(['permission:create_radiology_purchases'])->group(function () {
                Route::post('/', [RadiologyPurchaseController::class, 'store']);
                Route::post('{purchase}/order', [RadiologyPurchaseController::class, 'markOrdered']);
                Route::post('{purchase}/cancel', [RadiologyPurchaseController::class, 'cancel']);
            });
            Route::get('{purchase}', [RadiologyPurchaseController::class, 'show']);
            Route::middleware(['permission:receive_radiology_purchases'])->group(function () {
                Route::post('{purchase}/receive', [RadiologyPurchaseController::class, 'receive']);
            });
        });
    });
    
    // Notifications
    Route::apiResource('notifications', NotificationController::class);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::post('notifications/bulk', [NotificationController::class, 'sendBulk']);
    Route::post('notifications/send-to-role', [NotificationController::class, 'sendToRole']);
    Route::post('notifications/appointment-reminder', [NotificationController::class, 'createAppointmentReminder']);
    Route::post('notifications/lab-result', [NotificationController::class, 'createLabResultNotification']);
    Route::get('notifications/statistics', [NotificationController::class, 'getStatistics']);
    
    // File Upload
    Route::post('files/upload', [FileUploadController::class, 'upload']);
    Route::post('files/upload-multiple', [FileUploadController::class, 'uploadMultiple']);
    Route::get('files', [FileUploadController::class, 'getFiles']);
    Route::get('files/{file}/download', [FileUploadController::class, 'download']);
    Route::get('files/{file}/url', [FileUploadController::class, 'getFileUrl']);
    Route::delete('files/{file}', [FileUploadController::class, 'destroy']);
    Route::get('files/statistics', [FileUploadController::class, 'getStatistics']);
    
    // Sync Engine
    Route::post('sync/to-server', [SyncController::class, 'syncToServer']);
    Route::post('sync/from-server', [SyncController::class, 'syncFromServer']);
    Route::post('sync/resolve-conflicts', [SyncController::class, 'resolveConflicts']);
    Route::get('sync/status', [SyncController::class, 'getSyncStatus']);
    
    // Reporting & Analytics
    Route::get('reports/patient/{patient}', [ReportingController::class, 'generatePatientReport']);
    Route::get('reports/lab', [ReportingController::class, 'generateLabReport']);
    Route::get('reports/financial', [ReportingController::class, 'generateFinancialReport']);
    Route::get('reports/nhis', [ReportingController::class, 'generateNHISReport']);
    Route::get('reports/ghs', [ReportingController::class, 'generateGHSReport']);
    Route::get('reports/emergency', [ReportingController::class, 'generateEmergencyReport']);
    Route::get('reports/surgery', [ReportingController::class, 'generateSurgeryReport']);
    Route::get('reports/dashboard-statistics', [ReportingController::class, 'getDashboardStatistics']);
    
    // Dashboard Statistics
    Route::get('dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('dashboard/patient-statistics', [DashboardController::class, 'getPatientStatistics']);
    Route::get('dashboard/revenue-analytics', [DashboardController::class, 'getRevenueAnalytics']);
    Route::get('dashboard/department-performance', [DashboardController::class, 'getDepartmentPerformance']);
    Route::get('dashboard/recent-appointments', [DashboardController::class, 'getRecentAppointments']);
    Route::get('dashboard/data', [DashboardController::class, 'getDashboardData']);
    
    // Role-specific Dashboards
    Route::get('dashboard/realtime-data', [DashboardController::class, 'getRealtimeData']);
    Route::get('dashboard/doctor', [DashboardController::class, 'getDoctorDashboard']);
    Route::get('dashboard/nurse', [DashboardController::class, 'getNurseDashboard']);
    Route::get('dashboard/pharmacist', [DashboardController::class, 'getPharmacistDashboard']);
    Route::get('dashboard/lab-technician', [DashboardController::class, 'getLabTechnicianDashboard']);
    Route::get('dashboard/receptionist', [DashboardController::class, 'getReceptionistDashboard']);
    Route::get('dashboard/accountant', [DashboardController::class, 'getAccountantDashboard']);
    
    // Teleconsultation Management
    Route::apiResource('teleconsultations', TeleconsultationController::class)->names([
        'index' => 'api.teleconsultations.index',
        'store' => 'api.teleconsultations.store',
        'show' => 'api.teleconsultations.show',
        'update' => 'api.teleconsultations.update',
        'destroy' => 'api.teleconsultations.destroy'
    ]);
    Route::post('teleconsultations/{teleconsultation}/start', [TeleconsultationController::class, 'start']);
    Route::post('teleconsultations/{teleconsultation}/end', [TeleconsultationController::class, 'end']);
    Route::post('teleconsultations/{teleconsultation}/cancel', [TeleconsultationController::class, 'cancel']);
    Route::get('teleconsultations/{teleconsultation}/consent', [TeleconsultationController::class, 'getConsent']);
    Route::post('teleconsultations/{teleconsultation}/consent', [TeleconsultationController::class, 'giveConsent']);
    Route::get('teleconsultations/statistics', [TeleconsultationController::class, 'statistics']);
    
    // Jitsi Meet integration for teleconsultations
    Route::get('teleconsultations/{teleconsultation}/jitsi-config', [TeleconsultationController::class, 'getJitsiConfig']);
    Route::post('teleconsultations/{teleconsultation}/patient-token', [TeleconsultationController::class, 'generatePatientToken']);
    
    // Teleconsultation Chat
    Route::get('teleconsultations/{teleconsultation}/chat', [TeleconsultationChatController::class, 'index']);
    Route::post('teleconsultations/{teleconsultation}/chat', [TeleconsultationChatController::class, 'store']);
    Route::put('teleconsultation-chat/{chat}', [TeleconsultationChatController::class, 'update']);
    Route::delete('teleconsultation-chat/{chat}', [TeleconsultationChatController::class, 'destroy']);
    Route::post('teleconsultations/{teleconsultation}/chat/mark-read', [TeleconsultationChatController::class, 'markAsRead']);
    Route::get('teleconsultations/{teleconsultation}/chat/unread-count', [TeleconsultationChatController::class, 'unreadCount']);
    
    // Teleconsultation Files
    Route::get('teleconsultations/{teleconsultation}/files', [TeleconsultationFileController::class, 'index']);
    Route::post('teleconsultations/{teleconsultation}/files', [TeleconsultationFileController::class, 'store']);
    Route::get('teleconsultation-files/{file}/download', [TeleconsultationFileController::class, 'download']);
    Route::put('teleconsultation-files/{file}', [TeleconsultationFileController::class, 'update']);
    Route::post('teleconsultation-files/{file}/consent', [TeleconsultationFileController::class, 'giveConsent']);
    Route::delete('teleconsultation-files/{file}/consent', [TeleconsultationFileController::class, 'revokeConsent']);
    Route::delete('teleconsultation-files/{file}', [TeleconsultationFileController::class, 'destroy']);
    Route::get('teleconsultations/{teleconsultation}/files/statistics', [TeleconsultationFileController::class, 'statistics']);
    
    // Eye Services Management
    Route::apiResource('eye-services', EyeServiceController::class);
    Route::get('eye-services/categories', [EyeServiceController::class, 'categories']);
    Route::get('eye-services/service-types', [EyeServiceController::class, 'serviceTypes']);
    Route::get('eye-services/nhis-covered', [EyeServiceController::class, 'nhisCovered']);
    Route::post('eye-services/{eyeService}/toggle-status', [EyeServiceController::class, 'toggleStatus']);
    Route::get('eye-services/statistics', [EyeServiceController::class, 'statistics']);
    
    // Eye Test Requests (Appointment-based)
    Route::apiResource('eye-test-requests', EyeTestRequestController::class);
    Route::post('eye-test-requests/create-from-appointment', [EyeTestRequestController::class, 'createFromAppointment']);
    Route::post('eye-test-requests/{eyeTestRequest}/start', [EyeTestRequestController::class, 'start']);
    Route::post('eye-test-requests/{eyeTestRequest}/complete', [EyeTestRequestController::class, 'complete']);
    Route::post('eye-test-requests/{eyeTestRequest}/cancel', [EyeTestRequestController::class, 'cancel']);
    Route::post('eye-test-requests/{eyeTestRequest}/mark-results-entered', [EyeTestRequestController::class, 'markResultsEntered']);
    Route::post('eye-test-requests/{eyeTestRequest}/mark-results-verified', [EyeTestRequestController::class, 'markResultsVerified']);
    Route::post('eye-test-requests/{eyeTestRequest}/mark-quality-control-passed', [EyeTestRequestController::class, 'markQualityControlPassed']);
    Route::get('eye-test-requests/statistics', [EyeTestRequestController::class, 'statistics']);
    Route::get('eye-test-requests/dashboard', [EyeTestRequestController::class, 'dashboard']);
    
    // PDF Generation
    Route::post('eye-test-requests/{eyeTestRequest}/generate-pdf', [EyeTestRequestController::class, 'generatePdfReport']);
    Route::post('eye-test-requests/{eyeTestRequest}/generate-custom-pdf', [EyeTestRequestController::class, 'generateCustomPdfReport']);
    Route::post('eye-test-requests/generate-summary-pdf', [EyeTestRequestController::class, 'generateSummaryPdfReport']);
    Route::get('eye-test-requests/download-pdf', [EyeTestRequestController::class, 'downloadPdfReport']);
    
    // Eye Test Results
    Route::apiResource('eye-test-results', EyeTestResultController::class);
    Route::post('eye-test-results/{eyeTestResult}/verify', [EyeTestResultController::class, 'verify']);
    Route::post('eye-test-results/{eyeTestResult}/mark-repeat', [EyeTestResultController::class, 'markForRepeat']);
    Route::get('eye-test-requests/{eyeTestRequest}/results', [EyeTestResultController::class, 'getByTestRequest']);
    Route::get('eye-test-results/abnormal', [EyeTestResultController::class, 'getAbnormalResults']);
    Route::get('eye-test-results/critical', [EyeTestResultController::class, 'getCriticalResults']);
    Route::get('eye-test-results/statistics', [EyeTestResultController::class, 'statistics']);
    
    // Customer Complaints Management
    Route::middleware(['permission:view_complaints'])->group(function () {
        Route::apiResource('complaints', ComplaintController::class)->names([
            'index' => 'api.complaints.index',
            'store' => 'api.complaints.store',
            'show' => 'api.complaints.show',
            'update' => 'api.complaints.update',
            'destroy' => 'api.complaints.destroy'
        ]);
        Route::get('complaints/statistics', [ComplaintController::class, 'statistics']);
    });
    
    // Walk-ins Management
    Route::middleware(['permission:view_walk_ins'])->group(function () {
        Route::apiResource('walk-ins', App\Http\Controllers\API\WalkInsController::class)->names([
            'index' => 'api.walk-ins.index',
            'store' => 'api.walk-ins.store',
            'show' => 'api.walk-ins.show',
            'update' => 'api.walk-ins.update',
            'destroy' => 'api.walk-ins.destroy'
        ]);
        Route::get('walk-ins/statistics', [App\Http\Controllers\API\WalkInsController::class, 'statistics']);
    });
    
    // Print Settings API Routes
    Route::prefix('print-settings')->group(function () {
        Route::get('/formats', [PrintSettingsController::class, 'getFormats']);
        Route::get('/', [PrintSettingsController::class, 'getSettings']);
        Route::post('/', [PrintSettingsController::class, 'store']);
        
        // Print generation routes
        Route::get('/invoices/{id}/print', [PrintSettingsController::class, 'generateInvoicePdf']);
        Route::get('/receipts/{id}/print', [PrintSettingsController::class, 'generateReceiptPdf']);
        Route::get('/invoices/{id}/preview', [PrintSettingsController::class, 'previewInvoice']);
        Route::get('/receipts/{id}/preview', [PrintSettingsController::class, 'previewReceipt']);
        
        // Quick print routes
        Route::get('/invoices/{id}/quick-print', [PrintSettingsController::class, 'quickPrintInvoice']);
        Route::get('/receipts/{id}/quick-print', [PrintSettingsController::class, 'quickPrintReceipt']);
    });
    
    // ========== BLOOD BANK MANAGEMENT ==========
    Route::prefix('blood-bank')->group(function () {
        // Blood Donations
        Route::get('/donations', [BloodBankController::class, 'indexDonations']);
        Route::post('/donations', [BloodBankController::class, 'storeDonation']);
        Route::get('/donations/{id}', [BloodBankController::class, 'showDonation']);
        Route::put('/donations/{id}', [BloodBankController::class, 'updateDonation']);
        Route::delete('/donations/{id}', [BloodBankController::class, 'destroyDonation']);
        
        // Blood Inventory
        Route::get('/inventory', [BloodBankController::class, 'indexInventory']);
        Route::get('/inventory/{id}', [BloodBankController::class, 'showInventory']);
        Route::put('/inventory/{id}', [BloodBankController::class, 'updateInventory']);
        Route::get('/inventory/alerts/low-stock', [BloodBankController::class, 'getLowStockAlerts']);
        
        // Transfusions
        Route::get('/transfusions', [BloodBankController::class, 'indexTransfusions']);
        Route::post('/transfusions', [BloodBankController::class, 'storeTransfusion']);
        Route::get('/transfusions/{id}', [BloodBankController::class, 'showTransfusion']);
        Route::put('/transfusions/{id}', [BloodBankController::class, 'updateTransfusion']);
    });
    
    // ========== ICU MANAGEMENT ==========
    Route::prefix('icu')->group(function () {
        Route::get('/', [IcuController::class, 'index']);
        Route::post('/', [IcuController::class, 'store']);
        Route::get('/active-patients', [IcuController::class, 'getActivePatients']);
        Route::get('/critical-patients', [IcuController::class, 'getCriticalPatients']);
        Route::get('/{id}', [IcuController::class, 'show']);
        Route::put('/{id}', [IcuController::class, 'update']);
        Route::put('/{id}/vitals', [IcuController::class, 'updateVitals']);
        Route::post('/{id}/discharge', [IcuController::class, 'discharge']);
        Route::delete('/{id}', [IcuController::class, 'destroy']);
    });

    // ========== AUDIT TRAIL ==========
    Route::middleware(['permission:view_audit_logs'])->prefix('audit')->group(function () {
        Route::get('/activity-logs', [App\Http\Controllers\API\AuditController::class, 'activityLogs']);
        Route::get('/login-audits', [App\Http\Controllers\API\AuditController::class, 'loginAudits']);
    });

    // ========== STOCK COUNT / CYCLE COUNT ==========
    Route::middleware(['permission:view_pharmacy|manage_pharmacy_inventory'])->prefix('stock-counts')->group(function () {
        Route::get('/', [App\Http\Controllers\API\StockCountController::class, 'index']);
        Route::get('/{stockCount}', [App\Http\Controllers\API\StockCountController::class, 'show']);
        Route::middleware(['permission:manage_pharmacy_inventory'])->group(function () {
            Route::post('/', [App\Http\Controllers\API\StockCountController::class, 'store']);
            Route::put('/{stockCount}/counts', [App\Http\Controllers\API\StockCountController::class, 'updateCounts']);
            Route::post('/{stockCount}/complete', [App\Http\Controllers\API\StockCountController::class, 'complete']);
        });
    });
    
    // ========== GHS REPORTS ==========
    Route::prefix('ghs-reports')->group(function () {
        Route::get('/', [GhsReportController::class, 'index']);
        Route::post('/', [GhsReportController::class, 'store']);
        Route::get('/{id}', [GhsReportController::class, 'show']);
        Route::put('/{id}', [GhsReportController::class, 'update']);
        Route::delete('/{id}', [GhsReportController::class, 'destroy']);
        Route::post('/{id}/submit', [GhsReportController::class, 'submitReport']);
        Route::post('/{id}/review', [GhsReportController::class, 'reviewReport']);
        Route::post('/generate-auto', [GhsReportController::class, 'generateAutoReport']);
    });
    
    // ========== NHIS CLAIMS ==========
    Route::prefix('nhis-claims')->group(function () {
        Route::get('/', [NhisClaimController::class, 'index']);
        Route::post('/', [NhisClaimController::class, 'store']);
        Route::get('/pending', [NhisClaimController::class, 'getPendingClaims']);
        Route::get('/queried', [NhisClaimController::class, 'getQueriedClaims']);
        Route::get('/{id}', [NhisClaimController::class, 'show']);
        Route::put('/{id}', [NhisClaimController::class, 'update']);
        Route::delete('/{id}', [NhisClaimController::class, 'destroy']);
        Route::post('/{id}/submit', [NhisClaimController::class, 'submitClaim']);
        Route::post('/{id}/vet', [NhisClaimController::class, 'vetClaim']);
        Route::post('/{id}/respond-query', [NhisClaimController::class, 'respondToQuery']);
        Route::post('/{id}/record-payment', [NhisClaimController::class, 'recordPayment']);
    });
    
});

// Public routes (if needed)
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'environment' => app()->environment(),
        'timestamp' => now()
    ]);
});

// Simple test route (debug only)
if (config('app.debug')) {
    Route::get('test', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is working',
            'timestamp' => now(),
            'database' => 'connected',
        ]);
    });
}

// Public appointment routes moved to line ~211 (before auth middleware) for guest access

// Real-Time Data Routes (for web interface AJAX calls) - no CSRF required
Route::prefix('realtime')->group(function () {
    Route::post('/module-data', [App\Http\Controllers\API\RealTimeDataController::class, 'getModuleData']);
    Route::post('/data-change-summary', [App\Http\Controllers\API\RealTimeDataController::class, 'getDataChangeSummary']);
    Route::post('/polling-interval', [App\Http\Controllers\API\RealTimeDataController::class, 'getPollingInterval']);
    Route::post('/invalidate-cache', [App\Http\Controllers\API\RealTimeDataController::class, 'invalidateCache']);
    Route::get('/active-modules', [App\Http\Controllers\API\RealTimeDataController::class, 'getActiveModules']);
    Route::post('/update-activity', [App\Http\Controllers\API\RealTimeDataController::class, 'updateActivity']);
});

