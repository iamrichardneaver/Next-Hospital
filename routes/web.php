<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PatientController;
use App\Http\Controllers\Web\AppointmentController;
use App\Http\Controllers\Web\BillingController;
use App\Http\Controllers\Web\ConsultationController;
use App\Http\Controllers\Web\VitalsController;
use App\Http\Controllers\Web\PharmacyController;
use App\Http\Controllers\Web\PharmacyPurchaseController;
use App\Http\Controllers\Web\PharmacySupplierController;
use App\Http\Controllers\Web\LabController;
use App\Http\Controllers\Web\LabPurchaseController;
use App\Http\Controllers\Web\LabSupplierController;
use App\Http\Controllers\Web\LabInventoryCatalogController;
use App\Http\Controllers\Web\LabManagementController;
use App\Http\Controllers\Web\LabResultsController;
use App\Http\Controllers\Web\LabArchiveController;
use App\Http\Controllers\Web\WardController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\PermissionSyncController;
use App\Http\Controllers\Web\BrandingFileController;
use App\Http\Controllers\Web\IdPrefixController;
use App\Http\Controllers\Web\VisitController;
use App\Http\Controllers\Web\InsuranceController;
use App\Http\Controllers\Web\ServicePricingController;
use App\Http\Controllers\Web\RevenueAnalyticsController;
use App\Http\Controllers\Web\AccountingController;
use App\Http\Controllers\Web\ExpenseController;
use App\Http\Controllers\Web\ComplaintController;
use App\Http\Controllers\Web\NotificationPreferenceController;
use App\Http\Controllers\Web\QueueController;
use App\Http\Controllers\Web\PrintSettingsController;
use App\Http\Controllers\Web\TeleconsultationController;
use App\Http\Controllers\Web\JitsiSettingsController;
use App\Http\Controllers\Web\RadiologyController;
use App\Http\Controllers\Web\RadiologyPurchaseController;
use App\Http\Controllers\Web\RadiologySupplierController;
use App\Http\Controllers\Web\RadiologyInventoryController;
use App\Http\Controllers\Web\DebtorController;
use App\Http\Controllers\Web\CashierController;
use App\Http\Controllers\Web\DownloadController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\MessageController;
use App\Http\Controllers\Web\ShopController;
use App\Http\Controllers\Web\AppointmentSlotController;
use App\Http\Controllers\Web\AppointmentFeeController;
use App\Http\Controllers\Web\DoctorScheduleController;
use App\Http\Controllers\Web\ReportsHubController;
use App\Http\Controllers\Web\FinancialReportWebController;
use App\Http\Controllers\Web\GhsReportWebController;
use App\Http\Controllers\Web\NhisClaimWebController;
use App\Http\Controllers\API\GlobalSearchController;

/*
|--------------------------------------------------------------------------
| Web Routes - Hybrid Blade Application
|--------------------------------------------------------------------------
|
| These routes return Blade views with server-side rendered data.
| Authentication uses Laravel's session-based auth.
| Dynamic features use AJAX calls to API endpoints.
|
*/

// Branding assets — Laravel fallback when Plesk/Apache blocks public/storage symlink
Route::get('/storage/branding/{filename}', [BrandingFileController::class, 'show'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('branding.file');

// Public Download Page
Route::get('/download', [DownloadController::class, 'index'])->name('download');

// Debug route for APK testing — only available when APP_DEBUG=true
if (config('app.debug')) {
    Route::get('/debug-apk', function () {
        $apkPath = public_path('nexthospital-app.apk');
        return response()->json([
            'apk_path' => $apkPath,
            'file_exists' => file_exists($apkPath),
            'is_readable' => is_readable($apkPath),
            'file_size' => file_exists($apkPath) ? filesize($apkPath) : 0,
            'public_path' => public_path(),
            'asset_url' => asset('nexthospital-app.apk'),
        ]);
    });
}

// Public Shop (no authentication required for browsing)
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/product/{id}', [ShopController::class, 'show'])->name('shop.show');

// Shop Cart & Checkout (requires authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/shop/cart', [ShopController::class, 'cart'])->name('shop.cart');
    Route::post('/shop/cart/add', [ShopController::class, 'addToCart'])->name('shop.cart.add');
    Route::put('/shop/cart/{id}/update', [ShopController::class, 'updateCartItem'])->name('shop.cart.update');
    Route::delete('/shop/cart/{id}/remove', [ShopController::class, 'removeFromCart'])->name('shop.cart.remove');
    Route::get('/shop/checkout', [ShopController::class, 'checkout'])->name('shop.checkout');
    Route::post('/shop/checkout', [ShopController::class, 'processCheckout'])->name('shop.checkout.process');
    Route::get('/shop/payment/{order}', [ShopController::class, 'payment'])->name('shop.payment');
    Route::post('/shop/payment/{order}/process', [ShopController::class, 'processPayment'])->name('shop.payment.process');
    Route::get('/shop/order/{order}/success', [ShopController::class, 'orderSuccess'])->name('shop.order-success');
});

// Redirect root to dashboard or login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Test route for real-time system (debug only)
if (config('app.debug')) {
    Route::get('/test-realtime', function () {
        return view('test-realtime');
    })->name('test.realtime');
}

// Public route for loading billing services (used by billing create page)
Route::get('billing/services/public', [BillingController::class, 'getServices'])->name('billing.services.public');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    
    
    // Test pages (debug only)
    if (config('app.debug')) {
        Route::get('/test-search', function () {
            return view('test-search');
        })->name('test-search');

        Route::get('/test-queue', [QueueController::class, 'index'])->name('test-queue');
    }
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/realtime-data', [DashboardController::class, 'getRealtimeData'])->name('dashboard.realtime');
    
    // Patient Portal - Lab Results (for patients to view their own results)
    Route::middleware(['auth'])->group(function () {
        Route::get('/my-lab-results', [LabController::class, 'myResults'])->name('lab.my-results');
        Route::get('/my-lab-results/{labRequest}', [LabController::class, 'myResultDetails'])->name('lab.my-result-details');
        Route::get('/my-lab-results/{labRequest}/pdf', [LabResultsController::class, 'generatePdfForPatient'])->name('lab.my-result-pdf');
        Route::get('/my-prescriptions', [PharmacyController::class, 'myPrescriptions'])->name('pharmacy.my-prescriptions');
        Route::get('/my-prescriptions/{prescription}', [PharmacyController::class, 'myShowPrescription'])->name('pharmacy.my-prescriptions.show');
    });
    
    // Patient Management
    Route::middleware(['permission:view_patients'])->group(function () {
        // Create patients (requires edit_patients permission) - MUST come before {patient} routes
        Route::middleware(['permission:edit_patients'])->group(function () {
            Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
            Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
        });
        
        // View and search patients (available to all with view_patients permission) - Specific routes before dynamic
        Route::get('/patients/export', [PatientController::class, 'export'])->name('patients.export');
        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/pending-registrations', [PatientController::class, 'pendingRegistrations'])->name('patients.pending-registrations');
        Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
        Route::get('/patients/check-duplicates', [PatientController::class, 'checkDuplicates'])->name('patients.check-duplicates');
        
        // Edit/Update patients (requires edit_patients permission) - Specific routes before dynamic
        Route::middleware(['permission:edit_patients'])->group(function () {
            Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
            Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
            Route::patch('/patients/{patient}', [PatientController::class, 'update']);
            Route::post('/patients/{patient}/portal-access', [PatientController::class, 'generatePortalAccess'])->name('patients.portal-access');
            Route::post('/patients/{patient}/portal-reset-password', [PatientController::class, 'resetPortalPassword'])->name('patients.portal-reset-password');
        });
        
        // Approve/Reject patient registrations (requires edit_patients permission)
        Route::middleware(['permission:edit_patients'])->group(function () {
            Route::post('/patients/{patient}/approve', [PatientController::class, 'approveRegistration'])->name('patients.approve');
            Route::post('/patients/{patient}/reject', [PatientController::class, 'rejectRegistration'])->name('patients.reject');
        });
        
        // Delete patients (requires delete_patients permission)
        Route::middleware(['permission:delete_patients'])->group(function () {
            Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
            Route::post('/patients/bulk-delete', [PatientController::class, 'bulkDelete'])->name('patients.bulk-delete');
        });
        
        // Dynamic routes with {patient} parameter MUST come LAST
        Route::get('/patients/{patient}/details', [PatientController::class, 'getPatientDetails'])->name('patients.details');
        Route::get('/patients/{patient}/financial-summary', [PatientController::class, 'financialSummary'])->name('patients.financial-summary');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    });
    
    // Doctor Schedule Management (for doctors to manage their own availability)
    // Allow doctors to access their schedules (self-service) and admins with permission
    Route::middleware(['auth'])->group(function () {
        Route::resource('doctor-schedules', DoctorScheduleController::class);
    });
    
    // Appointment Management
    Route::middleware(['permission:view_appointments'])->group(function () {
        // IMPORTANT: Appointment Slots routes MUST come BEFORE Route::resource to avoid route conflicts
        // (otherwise appointments/{appointment} catches appointments/slots)
        Route::prefix('appointments/slots')->name('appointments.slots.')->group(function () {
            Route::get('/', [AppointmentSlotController::class, 'index'])->name('index');
            Route::get('/create', [AppointmentSlotController::class, 'create'])->name('create');
            Route::post('/', [AppointmentSlotController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AppointmentSlotController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AppointmentSlotController::class, 'update'])->name('update');
            Route::delete('/{id}', [AppointmentSlotController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/block', [AppointmentSlotController::class, 'block'])->name('block');
            Route::post('/{id}/unblock', [AppointmentSlotController::class, 'unblock'])->name('unblock');
            Route::post('/bulk-delete', [AppointmentSlotController::class, 'bulkDelete'])->name('bulk-delete');
        });
        
        Route::get('appointments/export', [AppointmentController::class, 'export'])->name('appointments.export');
        // Appointment resource routes (must come AFTER specific routes like slots)
        Route::resource('appointments', AppointmentController::class);
        
        // Appointment payment routes
        Route::get('appointments/{appointment}/calculate-fee', [AppointmentController::class, 'calculateFee'])->name('appointments.calculate-fee');
        Route::post('appointments/{appointment}/initialize-payment', [AppointmentController::class, 'initializePayment'])->name('appointments.initialize-payment');
        Route::post('appointments/{appointment}/record-staff-payment', [AppointmentController::class, 'recordStaffPayment'])->name('appointments.record-staff-payment');
        Route::post('appointments/{appointment}/review', [AppointmentController::class, 'storeReview'])->name('appointments.review');
        Route::get('appointments/payment/process', [AppointmentController::class, 'processPayment'])->name('appointments.process-payment');
    });
    
    // Billing & Invoices
    // CRITICAL FIX: Accept both view_invoices OR manage_billing to support direct permission assignments
    Route::middleware(['permission:view_invoices|manage_billing'])->group(function () {
        // Specific routes MUST come before resource routes
        Route::get('billing/export', [BillingController::class, 'export'])->name('billing.export');
        Route::get('billing/services', [BillingController::class, 'getServices'])->name('billing.services');
        Route::get('billing/{billing}/print', [BillingController::class, 'print'])->name('billing.print');
        Route::get('billing/{billing}/download', [BillingController::class, 'download'])->name('billing.download');
        Route::post('billing/{billing}/record-payment', [BillingController::class, 'recordPayment'])->name('billing.record-payment');
        // Resource route comes last
        Route::resource('billing', BillingController::class);
    });
    
    // Cashier & Payment Center
    Route::middleware(['permission:process_payments'])->group(function () {
        Route::get('cashier', [CashierController::class, 'index'])->name('cashier.index');
        Route::post('cashier/search-patient', [CashierController::class, 'searchPatient'])->name('cashier.search-patient');
        Route::get('cashier/patient/{patient}/charges', [CashierController::class, 'getPatientCharges'])->name('cashier.get-patient-charges');
        Route::get('cashier/patient/{patient}/debt-info', [CashierController::class, 'getPatientDebtInfo'])->name('cashier.get-patient-debt-info');
        Route::post('cashier/process-payment', [CashierController::class, 'processPayment'])->name('cashier.process-payment');
        Route::post('cashier/pay-invoice', [CashierController::class, 'payInvoice'])->name('cashier.pay-invoice');
        Route::get('cashier/payment/{payment}/receipt', [CashierController::class, 'generateReceipt'])->name('cashier.generate-receipt');
        Route::get('cashier/history', [CashierController::class, 'branchPaymentHistory'])->name('cashier.history');
        Route::get('cashier/patient/{patient}/payment-history', [CashierController::class, 'getPaymentHistory'])->name('cashier.payment-history');
        
        // Individual module receipts and payment history
        Route::get('cashier/{module}/{id}/receipt', [CashierController::class, 'generateModuleReceipt'])->name('cashier.module-receipt');
        Route::get('cashier/{module}/patient/{patient}/payments', [CashierController::class, 'getModulePaymentHistory'])->name('cashier.module-payment-history');
        Route::get('cashier/pending-payments', [CashierController::class, 'getAllPendingPayments'])->name('cashier.pending-payments');
        Route::get('cashier/outstanding-debts', [CashierController::class, 'getOutstandingDebts'])->name('cashier.outstanding-debts');
        Route::post('cashier/payments/{payment}/refund', [CashierController::class, 'refundPayment'])->name('cashier.refund-payment');
    });

    // Cashier daily report — counter staff OR accountant oversight (read-only)
    Route::middleware(['permission:process_payments|view_cashier_reports'])->group(function () {
        Route::get('cashier/daily-report', [CashierController::class, 'generateDailyReport'])->name('cashier.daily-report');
    });

    // Accounting hub — financial oversight dashboard & reports
    Route::middleware(['permission:view_financial_dashboard|view_financial_reports|view_revenue_analytics'])->group(function () {
        Route::get('accounting', [AccountingController::class, 'index'])->name('accounting.index');
    });
    Route::middleware(['permission:view_revenue_reports|view_revenue_analytics'])->group(function () {
        Route::get('accounting/revenue', [AccountingController::class, 'revenue'])->name('accounting.revenue');
        Route::get('accounting/revenue/drill-down/{serviceType}', [AccountingController::class, 'revenueDrillDown'])->name('accounting.revenue.drill-down');
        Route::get('accounting/revenue-vs-expenses', [AccountingController::class, 'revenueVsExpenses'])->name('accounting.revenue-vs-expenses');
    });
    Route::middleware(['permission:view_balance_sheet'])->group(function () {
        Route::get('accounting/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('accounting.balance-sheet');
    });
    Route::middleware(['permission:view_cash_flow'])->group(function () {
        Route::get('accounting/cash-flow', [AccountingController::class, 'cashFlow'])->name('accounting.cash-flow');
    });
    // Department staff — submit operational expenses (pending until accountant approves)
    Route::middleware(['permission:create_expenses'])->group(function () {
        Route::get('expenses/my', [ExpenseController::class, 'myExpenses'])
            ->name('expenses.my')
            ->middleware('permission:view_own_expenses');
        Route::get('expenses/submit/{department?}', [ExpenseController::class, 'submitCreate'])->name('expenses.submit.create');
        Route::post('expenses/submit', [ExpenseController::class, 'submitStore'])->name('expenses.submit.store');

        Route::get('pharmacy/expenses/create', fn () => redirect()->route('expenses.submit.create', 'pharmacy'))->name('pharmacy.expenses.create');
        Route::get('lab/expenses/create', fn () => redirect()->route('expenses.submit.create', 'lab'))->name('lab.expenses.create');
        Route::get('radiology/expenses/create', fn () => redirect()->route('expenses.submit.create', 'radiology'))->name('radiology.expenses.create');
        Route::get('reception/expenses/create', fn () => redirect()->route('expenses.submit.create', 'reception'))->name('reception.expenses.create');
        Route::get('nursing/expenses/create', fn () => redirect()->route('expenses.submit.create', 'nursing'))->name('nursing.expenses.create');
        Route::get('cashier/expenses/create', fn () => redirect()->route('expenses.submit.create', 'cashier'))->name('cashier.expenses.create');
    });

    Route::middleware(['permission:view_expenses|manage_expenses|approve_expenses|view_own_expenses'])->group(function () {
        Route::get('accounting/expenses', [ExpenseController::class, 'index'])
            ->name('accounting.expenses.index')
            ->middleware('permission:view_expenses|manage_expenses|approve_expenses');
        Route::get('accounting/expenses/create', [ExpenseController::class, 'create'])->name('accounting.expenses.create')->middleware('permission:manage_expenses');
        Route::post('accounting/expenses', [ExpenseController::class, 'store'])->name('accounting.expenses.store')->middleware('permission:manage_expenses');
        Route::get('accounting/expenses/{expense}', [ExpenseController::class, 'show'])->name('accounting.expenses.show');
        Route::get('accounting/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('accounting.expenses.edit')->middleware('permission:manage_expenses');
        Route::put('accounting/expenses/{expense}', [ExpenseController::class, 'update'])->name('accounting.expenses.update')->middleware('permission:manage_expenses');
        Route::delete('accounting/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('accounting.expenses.destroy')->middleware('permission:manage_expenses|create_expenses');
        Route::post('accounting/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('accounting.expenses.approve')->middleware('permission:approve_expenses|manage_expenses');
        Route::post('accounting/expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('accounting.expenses.reject')->middleware('permission:approve_expenses|manage_expenses');
        Route::post('accounting/expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('accounting.expenses.mark-paid')->middleware('permission:manage_expenses');
    });
    
    // Debtors Management
    // CRITICAL FIX: Accept both view_debtors OR manage_debtors to support direct permission assignments
    Route::middleware(['permission:view_debtors|manage_debtors'])->group(function () {
        Route::get('debtors/export', [DebtorController::class, 'export'])->name('debtors.export');
        Route::resource('debtors', DebtorController::class);
        Route::get('debtors/{debtor}/payment-history', [DebtorController::class, 'paymentHistory'])->name('debtors.payment-history');
        Route::get('debtors/{debtor}/outstanding-invoices', [DebtorController::class, 'outstandingInvoices'])->name('debtors.outstanding-invoices');
        Route::get('debtors/report', [DebtorController::class, 'report'])->name('debtors.report');
        Route::post('debtors/send-reminders', [DebtorController::class, 'sendReminders'])->name('debtors.send-reminders');
        Route::post('debtors/{debtor}/update-status', [DebtorController::class, 'updateStatus'])->name('debtors.update-status');
        Route::post('debtors/bulk-update', [DebtorController::class, 'bulkUpdate'])->name('debtors.bulk-update');
        Route::get('debtors/statistics', [DebtorController::class, 'getStatistics'])->name('debtors.statistics');
    });
    
    // Consultation requests — literal paths must come before consultations/{consultation}
    Route::middleware(['permission:create_consultations'])->group(function () {
        Route::get('/consultations/create-request', [ConsultationController::class, 'createRequest'])->name('consultations.create-request');
        Route::post('/consultations/store-request', [ConsultationController::class, 'storeRequest'])->name('consultations.store-request');
        Route::get('/consultations/create-for-patient/{patient}', [ConsultationController::class, 'createForPatient'])->name('consultations.create-for-patient');
        Route::get('/consultations/create-from-queue/{consultation}', [ConsultationController::class, 'createFromQueue'])->name('consultations.create-from-queue');
        Route::get('/consultations/{consultation}/radiology/create', [ConsultationController::class, 'createRadiologyRequest'])->name('consultations.radiology.create');
    });

    // Consultations
    Route::middleware(['permission:view_consultations'])->group(function () {
        Route::get('/consultations/export', [ConsultationController::class, 'export'])->name('consultations.export');
        Route::get('/consultations/completed/export', [ConsultationController::class, 'exportCompleted'])->name('consultations.completed.export');
        Route::get('/consultations/doctor-queue', [ConsultationController::class, 'doctorQueue'])->name('consultations.doctor-queue');
        Route::get('/consultations/completed', [ConsultationController::class, 'completedConsultations'])->name('consultations.completed');
        Route::post('/consultations/call-next', [ConsultationController::class, 'callNextConsultation'])->name('consultations.call-next');
        Route::resource('consultations', ConsultationController::class);
        Route::post('/consultations/{consultation}/no-show', [ConsultationController::class, 'markNoShow'])->name('consultations.no-show');
        Route::middleware(['permission:manage_consultations'])->group(function () {
            Route::post('/consultations/bulk-delete', [ConsultationController::class, 'bulkDelete'])->name('consultations.bulk-delete');
        });
    });
    
    // Vitals - specific routes must come before parameterized routes
    Route::middleware(['permission:record_vitals'])->group(function () {
        Route::get('/vitals/record', [VitalsController::class, 'create'])->name('vitals.create');
    });
    
    // Vitals - index and show (for users who can view)
    Route::middleware(['permission:view_vitals'])->group(function () {
        Route::get('/vitals/export', [VitalsController::class, 'export'])->name('vitals.export');
        Route::get('/vitals', [VitalsController::class, 'index'])->name('vitals.index');
        Route::get('/vitals/{vital}', [VitalsController::class, 'show'])->name('vitals.show');
    });
    
    // Vitals recording and management (nurse-specific - requires record_vitals permission)
    Route::middleware(['permission:record_vitals'])->group(function () {
        Route::post('/vitals', [VitalsController::class, 'store'])->name('vitals.store');
        Route::get('/vitals/{vital}/edit', [VitalsController::class, 'edit'])->name('vitals.edit');
        Route::put('/vitals/{vital}', [VitalsController::class, 'update'])->name('vitals.update');
        Route::delete('/vitals/{vital}', [VitalsController::class, 'destroy'])->name('vitals.destroy');
    });

    // Pharmacy — pharmacist/admin only (doctors use consultation pages for prescribing)
    Route::middleware(['permission:manage_pharmacy_inventory|dispense_drugs|view_pharmacy_analytics|create_drugs|edit_drugs|manage_inventory'])->group(function () {
        // Direct prescription routes for search compatibility
        Route::get('prescriptions/{prescription}', [PharmacyController::class, 'showPrescription'])->name('prescriptions.show');
        
        Route::get('pharmacy/export', [PharmacyController::class, 'export'])->name('pharmacy.export');
        Route::get('pharmacy/prescriptions/export', [PharmacyController::class, 'exportPrescriptions'])->name('pharmacy.prescriptions.export');
        Route::get('pharmacy/dispensing/export', [PharmacyController::class, 'exportDispensing'])->name('pharmacy.dispensing.export');
        Route::get('pharmacy/stock/export', [PharmacyController::class, 'exportStock'])->name('pharmacy.stock.export');
        Route::get('pharmacy/history/export', [PharmacyController::class, 'exportHistory'])->name('pharmacy.history.export');
        // Prescription Management - Define these first to avoid conflicts
        Route::get('pharmacy/prescriptions', [PharmacyController::class, 'prescriptions'])->name('pharmacy.prescriptions');
        Route::get('pharmacy/prescriptions/{prescription}', [PharmacyController::class, 'showPrescription'])->name('pharmacy.prescriptions.show');
        Route::post('pharmacy/prescriptions/{prescription}/dispense', [PharmacyController::class, 'dispensePrescription'])->name('pharmacy.prescriptions.dispense');
        Route::get('pharmacy/prescriptions/{prescription}/print', [PharmacyController::class, 'printPrescription'])->name('pharmacy.prescriptions.print');
        Route::get('pharmacy/prescriptions/{prescription}/edit', [PharmacyController::class, 'editPrescription'])->name('pharmacy.prescriptions.edit');
        Route::put('pharmacy/prescriptions/{prescription}', [PharmacyController::class, 'updatePrescription'])->name('pharmacy.prescriptions.update');
        Route::post('pharmacy/prescriptions/{prescription}/cancel', [PharmacyController::class, 'cancelPrescription'])->name('pharmacy.prescriptions.cancel');
        Route::post('pharmacy/prescriptions/{prescription}/complete', [PharmacyController::class, 'completePrescription'])->name('pharmacy.prescriptions.complete');
        Route::get('pharmacy/prescriptions/{prescription}/history', [PharmacyController::class, 'prescriptionHistory'])->name('pharmacy.prescriptions.history');
        
        // Dispensing Workflow
        Route::get('pharmacy/dispensing', [PharmacyController::class, 'dispensing'])->name('pharmacy.dispensing');
        Route::post('pharmacy/dispensing/process', [PharmacyController::class, 'processDispensing'])->name('pharmacy.dispensing.process');
        
        // Stock Management
        Route::get('pharmacy/stock', [PharmacyController::class, 'stock'])->name('pharmacy.stock');
        Route::post('pharmacy/stock/add', [PharmacyController::class, 'addStock'])->name('pharmacy.stock.add');
        Route::post('pharmacy/stock/update', [PharmacyController::class, 'updateStock'])->name('pharmacy.stock.update');
        
        // Dispensing History
        Route::get('pharmacy/history', [PharmacyController::class, 'history'])->name('pharmacy.history');
        
        // Analytics and Reporting
        Route::get('pharmacy/analytics', [PharmacyController::class, 'analytics'])->name('pharmacy.analytics');
        
        // Billing Integration
        Route::post('pharmacy/prescriptions/{prescription}/generate-billing', [PharmacyController::class, 'generateBilling'])->name('pharmacy.prescriptions.generate-billing');
        
        // Drug Interaction Checking
        Route::get('pharmacy/prescriptions/{prescription}/interactions', [PharmacyController::class, 'checkDrugInteractions'])->name('pharmacy.prescriptions.interactions');
        
        // Stock Alerts
        Route::get('pharmacy/stock-alerts', [PharmacyController::class, 'getStockAlerts'])->name('pharmacy.stock-alerts');
        
        // Prescription Notifications
        Route::get('pharmacy/notifications/{patientId}', [PharmacyController::class, 'getPatientNotifications'])->name('pharmacy.notifications');
        Route::post('pharmacy/notifications/{notification}/read', [PharmacyController::class, 'markNotificationAsRead'])->name('pharmacy.notifications.read');
    });

    // Pharmacy Suppliers — before pharmacy/{pharmacy} resource
    Route::middleware(['permission:view_pharmacy_suppliers|manage_pharmacy_suppliers'])->prefix('pharmacy/suppliers')->name('pharmacy.suppliers.')->group(function () {
        Route::get('/', [PharmacySupplierController::class, 'index'])->name('index');
        Route::middleware(['permission:manage_pharmacy_suppliers'])->group(function () {
            Route::get('/create', [PharmacySupplierController::class, 'create'])->name('create');
            Route::post('/', [PharmacySupplierController::class, 'store'])->name('store');
            Route::get('/{supplier}/edit', [PharmacySupplierController::class, 'edit'])->name('edit');
            Route::put('/{supplier}', [PharmacySupplierController::class, 'update'])->name('update');
            Route::post('/{supplier}/deactivate', [PharmacySupplierController::class, 'deactivate'])->name('deactivate');
            Route::post('/{supplier}/activate', [PharmacySupplierController::class, 'activate'])->name('activate');
        });
    });

    // Pharmacy Purchases — MUST register before pharmacy/{pharmacy} resource or "purchases" is treated as a drug ID
    Route::middleware(['permission:view_pharmacy_purchases|create_pharmacy_purchases|receive_pharmacy_purchases'])->prefix('pharmacy/purchases')->name('pharmacy.purchases.')->group(function () {
        Route::get('/', [PharmacyPurchaseController::class, 'index'])->name('index');
        Route::middleware(['permission:create_pharmacy_purchases'])->group(function () {
            Route::get('/create', [PharmacyPurchaseController::class, 'create'])->name('create');
            Route::post('/', [PharmacyPurchaseController::class, 'store'])->name('store');
            Route::post('/{purchase}/order', [PharmacyPurchaseController::class, 'markOrdered'])->name('order');
            Route::post('/{purchase}/cancel', [PharmacyPurchaseController::class, 'cancel'])->name('cancel');
        });
        Route::get('/{purchase}', [PharmacyPurchaseController::class, 'show'])->name('show');
        Route::middleware(['permission:receive_pharmacy_purchases'])->group(function () {
            Route::get('/{purchase}/receive', [PharmacyPurchaseController::class, 'receiveForm'])->name('receive');
            Route::post('/{purchase}/receive', [PharmacyPurchaseController::class, 'receive'])->name('receive.store');
        });
    });

    Route::middleware(['permission:manage_pharmacy_inventory|dispense_drugs|view_pharmacy_analytics|create_drugs|edit_drugs|manage_inventory'])->group(function () {
        // Resource routes - Define last so static paths (purchases, prescriptions, etc.) match first
        Route::resource('pharmacy', PharmacyController::class);
    });
    
    // Laboratory
    Route::middleware(['permission:view_lab_requests'])->group(function () {
        // Lab Archive Routes (must come before resource routes to avoid conflicts)
        Route::get('/lab/archive', [LabArchiveController::class, 'index'])->name('lab.archive.index');
        Route::get('/lab/archive/patient/{patient}/history', [LabArchiveController::class, 'patientHistory'])->name('lab.archive.patient-history');
        Route::get('/lab/archive/compare-results', [LabArchiveController::class, 'compareResults'])->name('lab.archive.compare-results');
        Route::get('/lab/archive/patient/{patient}/parameter/{parameter}/trend', [LabArchiveController::class, 'parameterTrend'])->name('lab.archive.parameter-trend');
        Route::get('/lab/archive/patient/{patient}/export', [LabArchiveController::class, 'exportPatientHistory'])->name('lab.archive.export-patient-history');
        
        // Quality Control Routes (requires manage_lab_setup permission) - MUST come before resource routes
        Route::middleware(['permission:manage_lab_setup'])->prefix('lab')->name('lab.quality-control.')->group(function () {
            Route::get('/quality-control', [\App\Http\Controllers\Web\QualityControlController::class, 'index'])->name('index');
            Route::get('/quality-control/create', [\App\Http\Controllers\Web\QualityControlController::class, 'create'])->name('create');
            Route::post('/quality-control', [\App\Http\Controllers\Web\QualityControlController::class, 'store'])->name('store');
            Route::get('/quality-control/{qualityControl}', [\App\Http\Controllers\Web\QualityControlController::class, 'show'])->name('show');
            Route::get('/quality-control/{qualityControl}/edit', [\App\Http\Controllers\Web\QualityControlController::class, 'edit'])->name('edit');
            Route::put('/quality-control/{qualityControl}', [\App\Http\Controllers\Web\QualityControlController::class, 'update'])->name('update');
            Route::delete('/quality-control/{qualityControl}', [\App\Http\Controllers\Web\QualityControlController::class, 'destroy'])->name('destroy');
            Route::get('/quality-control-statistics', [\App\Http\Controllers\Web\QualityControlController::class, 'statistics'])->name('statistics');
        });
        
        // Equipment Management Routes (requires manage_lab_setup permission) - MUST come before resource routes
        // Lab inventory catalog & purchases — distinct from pharmacy
        Route::middleware(['permission:view_lab_inventory|view_lab_purchases|create_lab_purchases|receive_lab_purchases|view_lab_suppliers|manage_lab_suppliers'])->group(function () {
            Route::get('/lab/inventory', [LabInventoryCatalogController::class, 'index'])->name('lab.inventory.index');
            Route::get('/lab/inventory/movements', [LabInventoryCatalogController::class, 'movements'])->name('lab.inventory.movements');
            Route::middleware(['permission:view_lab_suppliers|manage_lab_suppliers'])->prefix('lab/suppliers')->name('lab.suppliers.')->group(function () {
                Route::get('/', [LabSupplierController::class, 'index'])->name('index');
                Route::middleware(['permission:manage_lab_suppliers'])->group(function () {
                    Route::get('/create', [LabSupplierController::class, 'create'])->name('create');
                    Route::post('/', [LabSupplierController::class, 'store'])->name('store');
                    Route::get('/{supplier}/edit', [LabSupplierController::class, 'edit'])->name('edit');
                    Route::put('/{supplier}', [LabSupplierController::class, 'update'])->name('update');
                    Route::post('/{supplier}/deactivate', [LabSupplierController::class, 'deactivate'])->name('deactivate');
                    Route::post('/{supplier}/activate', [LabSupplierController::class, 'activate'])->name('activate');
                });
            });
            Route::prefix('lab/purchases')->name('lab.purchases.')->group(function () {
                Route::get('/', [LabPurchaseController::class, 'index'])->name('index');
                Route::middleware(['permission:create_lab_purchases'])->group(function () {
                    Route::get('/create', [LabPurchaseController::class, 'create'])->name('create');
                    Route::post('/', [LabPurchaseController::class, 'store'])->name('store');
                    Route::post('/{purchase}/order', [LabPurchaseController::class, 'markOrdered'])->name('order');
                    Route::post('/{purchase}/cancel', [LabPurchaseController::class, 'cancel'])->name('cancel');
                });
                Route::get('/{purchase}', [LabPurchaseController::class, 'show'])->name('show');
                Route::middleware(['permission:receive_lab_purchases'])->group(function () {
                    Route::get('/{purchase}/receive', [LabPurchaseController::class, 'receiveForm'])->name('receive');
                    Route::post('/{purchase}/receive', [LabPurchaseController::class, 'receive'])->name('receive.store');
                });
            });
        });

        Route::middleware(['permission:manage_lab_setup'])->prefix('lab')->name('lab.equipment.')->group(function () {
            Route::get('/equipment', [\App\Http\Controllers\Web\EquipmentController::class, 'index'])->name('index');
            Route::get('/equipment/create', [\App\Http\Controllers\Web\EquipmentController::class, 'create'])->name('create');
            Route::post('/equipment', [\App\Http\Controllers\Web\EquipmentController::class, 'store'])->name('store');
            Route::get('/equipment/{equipment}', [\App\Http\Controllers\Web\EquipmentController::class, 'show'])->name('show');
            Route::get('/equipment/{equipment}/edit', [\App\Http\Controllers\Web\EquipmentController::class, 'edit'])->name('edit');
            Route::put('/equipment/{equipment}', [\App\Http\Controllers\Web\EquipmentController::class, 'update'])->name('update');
            Route::delete('/equipment/{equipment}', [\App\Http\Controllers\Web\EquipmentController::class, 'destroy'])->name('destroy');
        });
        
        // Lab-specific routes MUST come before resource route
        Route::get('/lab/test-types-by-category', [LabController::class, 'getTestTypesByCategory'])->name('lab.test-types-by-category');
        Route::post('/lab/bulk-delete', [LabController::class, 'bulkDelete'])->name('lab.bulk-delete')->middleware('permission:delete_lab_requests');
        
        // Lab resource route with RBAC - MUST come after specific routes like quality-control, equipment, and test-types-by-category
        Route::middleware(['permission:create_lab_requests'])->group(function () {
            Route::get('/lab/create', [LabController::class, 'create'])->name('lab.create');
            Route::post('/lab', [LabController::class, 'store'])->name('lab.store');
        });
        
        Route::middleware(['permission:edit_lab_requests'])->group(function () {
            Route::get('/lab/{lab}/edit', [LabController::class, 'edit'])->name('lab.edit');
            Route::put('/lab/{lab}', [LabController::class, 'update'])->name('lab.update');
            Route::post('/lab/{lab}/start', [LabController::class, 'startTest'])->name('lab.start');
            Route::post('/lab/{lab}/complete', [LabController::class, 'completeTest'])->name('lab.complete');
        });
        
        // Other lab routes (index, show, destroy) - already protected by view_lab_requests middleware
        Route::get('/lab/export', [LabController::class, 'export'])->name('lab.export');
        Route::get('/lab', [LabController::class, 'index'])->name('lab.index');
        Route::get('/lab/{lab}', [LabController::class, 'show'])->name('lab.show');
        Route::delete('/lab/{lab}', [LabController::class, 'destroy'])->name('lab.destroy')->middleware('permission:delete_lab_requests');
        
        // Walk-in Lab Routes
        Route::get('visits/{visit}/lab-request', [LabController::class, 'createFromWalkInVisit'])->name('lab.create-from-walk-in');
        Route::post('visits/{visit}/lab-request', [LabController::class, 'storeFromWalkInVisit'])->name('lab.store-from-walk-in');
        Route::post('visits/{visit}/complete-lab-service', [LabController::class, 'completeWalkInService'])->name('lab.complete-walk-in-service');
        
        // Lab Management Routes (requires manage_lab_setup permission)
        Route::middleware(['permission:manage_lab_setup'])->prefix('lab/management')->name('lab.')->group(function () {
            // Test Categories
            Route::get('/categories', [LabManagementController::class, 'categories'])->name('categories');
            Route::get('/categories/create', [LabManagementController::class, 'createCategory'])->name('categories.create');
            Route::post('/categories', [LabManagementController::class, 'storeCategory'])->name('categories.store');
            Route::get('/categories/{category}/edit', [LabManagementController::class, 'editCategory'])->name('categories.edit');
            Route::put('/categories/{category}', [LabManagementController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/categories/{category}', [LabManagementController::class, 'destroyCategory'])->name('categories.destroy');
            
            // Test Types
            Route::get('/test-types', [LabManagementController::class, 'testTypes'])->name('test-types');
            Route::get('/test-types/create', [LabManagementController::class, 'createTestType'])->name('test-types.create');
            Route::post('/test-types', [LabManagementController::class, 'storeTestType'])->name('test-types.store');
            Route::get('/test-types/{testType}', [LabManagementController::class, 'showTestType'])->name('test-types.show');
            Route::get('/test-types/{testType}/edit', [LabManagementController::class, 'editTestType'])->name('test-types.edit');
            Route::put('/test-types/{testType}', [LabManagementController::class, 'updateTestType'])->name('test-types.update');
            Route::post('/test-types/{testType}/consumables', [LabManagementController::class, 'syncTestTypeConsumables'])
                ->name('test-types.consumables.sync')
                ->middleware('permission:manage_lab_test_consumables');
            Route::delete('/test-types/{testType}', [LabManagementController::class, 'destroyTestType'])->name('test-types.destroy');
            
            // Individual Tests
            Route::get('/tests', [LabManagementController::class, 'tests'])->name('tests');
            Route::get('/tests/create', [LabManagementController::class, 'createTest'])->name('tests.create');
            Route::post('/tests', [LabManagementController::class, 'storeTest'])->name('tests.store');
            Route::get('/tests/{test}/edit', [LabManagementController::class, 'editTest'])->name('tests.edit');
            Route::put('/tests/{test}', [LabManagementController::class, 'updateTest'])->name('tests.update');
            Route::delete('/tests/{test}', [LabManagementController::class, 'destroyTest'])->name('tests.destroy');
            
            // Templates
            Route::get('/templates', [LabManagementController::class, 'templates'])->name('templates');
            Route::get('/templates/create', [LabManagementController::class, 'createTemplate'])->name('templates.create');
            Route::post('/templates', [LabManagementController::class, 'storeTemplate'])->name('templates.store');
            Route::get('/templates/{template}', [LabManagementController::class, 'showTemplate'])->name('templates.show');
            Route::get('/templates/{template}/edit', [LabManagementController::class, 'editTemplate'])->name('templates.edit');
            Route::put('/templates/{template}', [LabManagementController::class, 'updateTemplate'])->name('templates.update');
            Route::delete('/templates/{template}', [LabManagementController::class, 'destroyTemplate'])->name('templates.destroy');
            
            // Parameters
            Route::get('/templates/{templateId}/parameters/create', [LabManagementController::class, 'createParameter'])->name('parameters.create');
            Route::post('/templates/{templateId}/parameters', [LabManagementController::class, 'storeParameter'])->name('parameters.store');
            Route::get('/parameters/{parameter}/edit', [LabManagementController::class, 'editParameter'])->name('parameters.edit');
            Route::put('/parameters/{parameter}', [LabManagementController::class, 'updateParameter'])->name('parameters.update');
            Route::delete('/parameters/{parameter}', [LabManagementController::class, 'destroyParameter'])->name('parameters.destroy');
            Route::post('/parameters/{parameter}/update-unit', [LabManagementController::class, 'updateParameterUnit'])->name('parameters.update-unit');
            Route::post('/parameters/{parameter}/toggle-status', [LabManagementController::class, 'toggleParameterStatus'])->name('parameters.toggle-status');
            Route::post('/parameters/bulk-action', [LabManagementController::class, 'bulkParameterAction'])->name('parameters.bulk-action');
            
            // Reference Ranges
            Route::get('/parameters/{parameterId}/ranges/create', [LabManagementController::class, 'createReferenceRange'])->name('reference-ranges.create');
            Route::post('/parameters/{parameterId}/ranges', [LabManagementController::class, 'storeReferenceRange'])->name('reference-ranges.store');
            Route::get('/ranges/{referenceRange}/edit', [LabManagementController::class, 'editReferenceRange'])->name('reference-ranges.edit');
            Route::put('/ranges/{referenceRange}', [LabManagementController::class, 'updateReferenceRange'])->name('reference-ranges.update');
            Route::delete('/ranges/{referenceRange}', [LabManagementController::class, 'destroyReferenceRange'])->name('reference-ranges.destroy');
            Route::post('/ranges/{referenceRange}/toggle-status', [LabManagementController::class, 'toggleReferenceRangeStatus'])->name('reference-ranges.toggle-status');
        });
        
        // Lab Results Routes
        Route::prefix('lab')->name('lab.')->group(function () {
            Route::middleware(['permission:enter_lab_results'])->group(function () {
                Route::get('/{labRequest}/enter-results', [LabResultsController::class, 'enterResults'])->name('enter-results');
                Route::post('/{labRequest}/store-results', [LabResultsController::class, 'storeResults'])->name('store-results');
            });
            
            Route::middleware(['permission:verify_lab_results'])->group(function () {
                Route::post('/{labRequest}/verify', [LabResultsController::class, 'verifyResults'])->name('verify-results');
            });
            
            Route::middleware(['permission:approve_lab_results'])->group(function () {
                Route::post('/{labRequest}/approve', [LabResultsController::class, 'approveResults'])->name('approve-results');
            });
            
            Route::middleware(['permission:print_lab_results'])->group(function () {
                Route::get('/{labRequest}/pdf', [LabResultsController::class, 'generatePdf'])->name('generate-pdf');
            });
        });
    });
    
    // Radiology inventory & purchases — distinct from pharmacy and lab
    Route::middleware(['permission:view_radiology_inventory|view_radiology_purchases|create_radiology_purchases|receive_radiology_purchases|view_radiology_suppliers|manage_radiology_suppliers'])->prefix('radiology')->name('radiology.')->group(function () {
        Route::get('/inventory', [RadiologyInventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/movements', [RadiologyInventoryController::class, 'movements'])->name('inventory.movements');
        Route::middleware(['permission:view_radiology_suppliers|manage_radiology_suppliers'])->prefix('suppliers')->name('suppliers.')->group(function () {
            Route::get('/', [RadiologySupplierController::class, 'index'])->name('index');
            Route::middleware(['permission:manage_radiology_suppliers'])->group(function () {
                Route::get('/create', [RadiologySupplierController::class, 'create'])->name('create');
                Route::post('/', [RadiologySupplierController::class, 'store'])->name('store');
                Route::get('/{supplier}/edit', [RadiologySupplierController::class, 'edit'])->name('edit');
                Route::put('/{supplier}', [RadiologySupplierController::class, 'update'])->name('update');
                Route::post('/{supplier}/deactivate', [RadiologySupplierController::class, 'deactivate'])->name('deactivate');
                Route::post('/{supplier}/activate', [RadiologySupplierController::class, 'activate'])->name('activate');
            });
        });
        Route::prefix('purchases')->name('purchases.')->group(function () {
            Route::get('/', [RadiologyPurchaseController::class, 'index'])->name('index');
            Route::middleware(['permission:create_radiology_purchases'])->group(function () {
                Route::get('/create', [RadiologyPurchaseController::class, 'create'])->name('create');
                Route::post('/', [RadiologyPurchaseController::class, 'store'])->name('store');
                Route::post('/{purchase}/order', [RadiologyPurchaseController::class, 'markOrdered'])->name('order');
                Route::post('/{purchase}/cancel', [RadiologyPurchaseController::class, 'cancel'])->name('cancel');
            });
            Route::get('/{purchase}', [RadiologyPurchaseController::class, 'show'])->name('show');
            Route::middleware(['permission:receive_radiology_purchases'])->group(function () {
                Route::get('/{purchase}/receive', [RadiologyPurchaseController::class, 'receiveForm'])->name('receive');
                Route::post('/{purchase}/receive', [RadiologyPurchaseController::class, 'receive'])->name('receive.store');
            });
        });
    });

    // Radiology & Imaging — radiology staff only (doctors order via consultation pages)
    Route::middleware(['permission:view_radiology_requests|perform_radiology_studies|complete_radiology_studies|process_radiology_requests|manage_radiology_setup|edit_radiology_requests|create_radiology_studies|edit_radiology_studies|upload_radiology_images|create_radiology_reports|edit_radiology_reports|manage_imaging_modalities|manage_radiology_equipment|view_radiology_inventory|view_radiology_purchases'])->prefix('radiology')->name('radiology.')->group(function () {
        // Radiology Studies (MUST come before resource routes to avoid conflicts)
        Route::middleware(['permission:view_radiology_studies'])->group(function () {
            Route::get('/studies', [RadiologyController::class, 'studies'])->name('studies');
            // Specific routes with additional segments MUST come before generic {study} route
            Route::get('/studies/{study}/pdf', [RadiologyController::class, 'generateStudyPdf'])->name('studies.pdf');
            Route::get('/studies/{study}/viewer', [RadiologyController::class, 'viewer'])->name('viewer');
            Route::post('/studies/{study}/upload-images', [RadiologyController::class, 'uploadImages'])->name('studies.upload-images');
            Route::post('/studies/{study}/complete', [RadiologyController::class, 'completeStudy'])->name('complete-study');
            // Generic show route comes LAST
            Route::get('/studies/{study}', [RadiologyController::class, 'showStudy'])->name('studies.show');
            Route::post('/requests/{radiology}/start-study', [RadiologyController::class, 'startStudy'])->name('start-study');
        });
        
        // Radiology Reports (MUST come before resource routes to avoid conflicts)
        Route::middleware(['permission:view_radiology_reports'])->group(function () {
            Route::get('/reports/export', [RadiologyController::class, 'exportReports'])->name('reports.export');
            Route::get('/reports', [RadiologyController::class, 'reports'])->name('reports');
            // Specific routes with additional segments MUST come before generic {report} route
            Route::get('/studies/{study}/create-report', [RadiologyController::class, 'createReport'])->name('reports.create');
            Route::post('/studies/{study}/store-report', [RadiologyController::class, 'storeReport'])->name('reports.store');
            Route::get('/reports/{report}/pdf', [RadiologyController::class, 'generateReportPdf'])->name('reports.pdf');
            Route::get('/reports/{report}/edit', [RadiologyController::class, 'editReport'])->name('reports.edit');
            Route::put('/reports/{report}', [RadiologyController::class, 'updateReport'])->name('reports.update');
            // Generic show route comes LAST
            Route::get('/reports/{report}', [RadiologyController::class, 'showReport'])->name('reports.show');
        });

        // DICOM Viewer & Series
        Route::middleware(['permission:view_radiology_studies'])->group(function () {
            Route::get('/series/{series}/images', [RadiologyController::class, 'getSeriesImages'])->name('series.images');
            // Route to serve radiology images dynamically (handles 403 errors and missing files)
            Route::get('/images/{image}/serve', [RadiologyController::class, 'serveImage'])->name('images.serve');
        });
        
        // Radiology Requests Resource Routes (MUST come LAST to avoid route conflicts)
        Route::middleware(['permission:view_radiology_requests'])->group(function () {
            Route::get('/export', [RadiologyController::class, 'export'])->name('export');
            Route::resource('', RadiologyController::class)->parameters(['' => 'radiology']);
        });
        
        // Walk-in radiology routes
        Route::middleware(['permission:create_radiology_requests'])->group(function () {
            Route::get('/walk-in/{visit}/create', [RadiologyController::class, 'createFromWalkInVisit'])->name('walk-in.create');
            Route::post('/walk-in/{visit}', [RadiologyController::class, 'storeFromWalkInVisit'])->name('walk-in.store');
        });
        
        // Radiology data endpoints (for dropdowns)
        Route::get('/radiologists', [RadiologyController::class, 'getRadiologists'])->name('radiologists.list');
    });
    
    // Wards & Beds
    Route::middleware(['permission:view_wards'])->group(function () {
        Route::resource('wards', WardController::class);
    });
    
    // Visits (OPD/IPD Check-in)
    Route::middleware(['permission:view_visits'])->group(function () {
        Route::get('/visits/export', [VisitController::class, 'export'])->name('visits.export');
        Route::get('/visits', [VisitController::class, 'index'])->name('visits.index');

        // Register before /visits/{visit} so "create" is not captured as a visit ID.
        Route::middleware(['permission:create_visits|manage_walk_ins'])->group(function () {
            Route::get('/visits/create', [VisitController::class, 'create'])->name('visits.create');
            Route::post('/visits', [VisitController::class, 'store'])->name('visits.store');
        });

        Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('visits.show');

        Route::middleware(['permission:edit_visits'])->group(function () {
            Route::get('/visits/{visit}/edit', [VisitController::class, 'edit'])->name('visits.edit');
            Route::put('/visits/{visit}', [VisitController::class, 'update'])->name('visits.update');
            Route::patch('/visits/{visit}', [VisitController::class, 'update']);
        });

        Route::middleware(['permission:manage_visits'])->group(function () {
            Route::delete('/visits/{visit}', [VisitController::class, 'destroy'])->name('visits.destroy');
            Route::post('/visits/bulk-delete', [VisitController::class, 'bulkDelete'])->name('visits.bulk-delete');
        });
    });
    
    // Queue Management
    Route::prefix('queues')->name('queues.')->group(function () {
        Route::middleware(['permission:view_queues|view_lab_queue|view_pharmacy_queue|view_emergency_queue'])->group(function () {
            Route::get('/export', [QueueController::class, 'export'])->name('export');
            Route::get('/', [QueueController::class, 'index'])->name('index');
            if (config('app.debug')) {
                Route::get('/test', [QueueController::class, 'index'])->name('test');
            }
            Route::get('/opd', [QueueController::class, 'opd'])->name('opd');
            Route::get('/lab', [QueueController::class, 'lab'])->name('lab');
            Route::get('/pharmacy', [QueueController::class, 'pharmacy'])->name('pharmacy');
            Route::get('/emergency', [QueueController::class, 'emergency'])->name('emergency');
            Route::get('/statistics', [QueueController::class, 'statistics'])->name('statistics');
        });

        Route::middleware(['permission:view_radiology_requests|perform_radiology_studies|complete_radiology_studies|process_radiology_requests|manage_radiology_setup'])->group(function () {
            Route::get('/radiology', [QueueController::class, 'radiology'])->name('radiology');
        });
        
        // Lab-specific routes - MUST come before other parameterized routes
        Route::middleware(['permission:view_lab_queue'])->group(function () {
            Route::get('/{id}/lab-details', [QueueController::class, 'labDetails'])->name('lab-details');
        });
        
        Route::middleware(['permission:view_queues'])->group(function () {
            Route::get('/{id}/print-ticket', [QueueController::class, 'printTicket'])->name('print-ticket');
            Route::get('/{id}/reprint-ticket', [QueueController::class, 'reprintTicket'])->name('reprint-ticket');
        });
        
        // Allow manage_queues, manage_lab_queue, and manage_pharmacy_queue permissions for queue actions
        Route::middleware(['permission:manage_queues|manage_lab_queue|manage_pharmacy_queue|manage_opd_queue|manage_emergency_queue|perform_radiology_studies|complete_radiology_studies'])->group(function () {
            Route::post('/call-next', [QueueController::class, 'callNext'])->name('call-next');
            Route::post('/{id}/start-serving', [QueueController::class, 'startServing'])->name('start-serving');
            Route::post('/{id}/complete-serving', [QueueController::class, 'completeServing'])->name('complete-serving');
            Route::post('/{id}/no-show', [QueueController::class, 'markNoShow'])->name('no-show');
        });
    });
    
    // Insurance Management
    Route::middleware(['permission:view_insurance'])->group(function () {
        Route::get('/insurance', [InsuranceController::class, 'index'])->name('insurance.index');
        Route::get('/insurance/policies', [InsuranceController::class, 'policies'])->name('insurance.policies');
        Route::post('/insurance/policies', [InsuranceController::class, 'storePolicy'])->name('insurance.policies.store');
        Route::get('/insurance/policies/{policy}/edit', [InsuranceController::class, 'editPolicy'])->name('insurance.policies.edit');
        Route::put('/insurance/policies/{policy}', [InsuranceController::class, 'updatePolicy'])->name('insurance.policies.update');
        Route::get('/insurance/claims', [InsuranceController::class, 'claims'])->name('insurance.claims');
        Route::post('/insurance/claims', [InsuranceController::class, 'storeClaim'])->name('insurance.claims.store');
        Route::put('/insurance/claims/{claim}/status', [InsuranceController::class, 'updateClaimStatus'])->name('insurance.claims.status');
        Route::get('/insurance/providers', [InsuranceController::class, 'providers'])->name('insurance.providers');
        Route::post('/insurance/providers', [InsuranceController::class, 'storeProvider'])->name('insurance.providers.store');
        Route::get('/insurance/providers/{provider}/edit', [InsuranceController::class, 'editProvider'])->name('insurance.providers.edit');
        Route::put('/insurance/providers/{provider}', [InsuranceController::class, 'updateProvider'])->name('insurance.providers.update');
        Route::get('/insurance/pre-authorizations', [InsuranceController::class, 'preAuthorizations'])->name('insurance.pre-authorizations');
        Route::post('/insurance/pre-authorizations', [InsuranceController::class, 'storePreAuthorization'])->name('insurance.pre-authorizations.store');
        Route::put('/insurance/pre-authorizations/{preAuthorization}', [InsuranceController::class, 'updatePreAuthorization'])->name('insurance.pre-authorizations.update');
        Route::get('/insurance/analytics', [InsuranceController::class, 'analytics'])->name('insurance.analytics');
        Route::post('/insurance/check-coverage', [InsuranceController::class, 'checkCoverage'])->name('insurance.check-coverage');
        Route::get('/insurance/patients/{patient}/policies', [InsuranceController::class, 'getPatientPolicies'])->name('insurance.patient-policies');
        Route::get('/insurance/export-report', [InsuranceController::class, 'exportReport'])->name('insurance.export-report');
    });
    
    // Users Management
    Route::middleware(['permission:view_users'])->group(function () {
        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::resource('users', UserController::class);

        Route::middleware(['permission:delete_users|manage_users'])->group(function () {
            Route::post('/users/bulk-delete', [UserController::class, 'bulkDestroy'])->name('users.bulk-delete');
        });
        
        // Direct User Permission Management (for temporary permission grants)
        Route::middleware(['permission:manage_roles'])->group(function () {
            Route::get('/users/{user}/permissions', [UserController::class, 'managePermissions'])->name('users.manage-permissions');
            Route::put('/users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.update-permissions');
            Route::post('/users/{user}/permissions/grant', [UserController::class, 'grantPermission'])->name('users.grant-permission');
            Route::post('/users/{user}/permissions/revoke', [UserController::class, 'revokePermission'])->name('users.revoke-permission');
        });
    });
    
    // Branch Management
    Route::middleware(['permission:view_branches'])->group(function () {
        Route::resource('branches', App\Http\Controllers\Web\BranchController::class);
    });
    
    // Switch Branch (For Super Admin & Admin)
    Route::post('/switch-branch', [App\Http\Controllers\Web\BranchController::class, 'switchBranch'])
        ->name('switch-branch')
        ->middleware('auth');
    
    // Notifications Management
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\NotificationController::class, 'index'])->name('index');
        Route::get('/latest', [App\Http\Controllers\Web\NotificationController::class, 'getLatest'])->name('latest');
        Route::get('/unread-count', [App\Http\Controllers\Web\NotificationController::class, 'getUnreadCount'])->name('unread-count');
        Route::post('/{id}/mark-read', [App\Http\Controllers\Web\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [App\Http\Controllers\Web\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [App\Http\Controllers\Web\NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/clear-all', [App\Http\Controllers\Web\NotificationController::class, 'clearAll'])->name('clear-all');
    });
    
    // Messages (Internal Staff Communication)
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\MessageController::class, 'index'])->name('index');
        Route::get('/users', [App\Http\Controllers\Web\MessageController::class, 'getUsers'])->name('users');
        Route::get('/latest', [App\Http\Controllers\Web\MessageController::class, 'getLatest'])->name('latest');
        Route::get('/unread-count', [App\Http\Controllers\Web\MessageController::class, 'getUnreadCount'])->name('unread-count');
        Route::post('/', [App\Http\Controllers\Web\MessageController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Web\MessageController::class, 'show'])->name('show');
        Route::post('/{id}/send', [App\Http\Controllers\Web\MessageController::class, 'sendMessage'])->name('send');
        Route::post('/{id}/mark-read', [App\Http\Controllers\Web\MessageController::class, 'markAsRead'])->name('mark-read');
    });
    
    // User Profile (accessible to all authenticated users)
    Route::get('/profile', [UserController::class, 'profile'])->name('profile.show');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [UserController::class, 'changePassword'])->name('profile.password');
    
    // Roles & Permissions Management
    Route::middleware(['permission:manage_roles'])->group(function () {
        Route::get('roles/export', [App\Http\Controllers\Web\RoleController::class, 'export'])->name('roles.export');
        Route::resource('roles', App\Http\Controllers\Web\RoleController::class);
        Route::get('/permissions', [App\Http\Controllers\Web\RoleController::class, 'permissions'])->name('permissions.index');
    });
    
    // Settings
    Route::middleware(['permission:view_settings'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        
        // Clean Data Feature (Super Admin Only)
        Route::middleware(['permission:manage_data_cleanup'])->group(function () {
            Route::get('/settings/clean-data', [SettingsController::class, 'cleanData'])->name('settings.clean-data');
            Route::post('/settings/clean-data', [SettingsController::class, 'processCleanData'])->name('settings.process-clean-data');
            Route::post('/settings/clean-module', [SettingsController::class, 'cleanIndividualModule'])->name('settings.clean-module');
            Route::get('/settings/clean-data/stats', [SettingsController::class, 'getModuleStats'])->name('settings.clean-data.stats');
            Route::post('/settings/clean-data/preview', [SettingsController::class, 'previewCleanData'])->name('settings.clean-data.preview');
        });

        // Permission registry sync (super_admin only)
        Route::middleware(['role:super_admin'])->group(function () {
            Route::get('/settings/permissions-sync', [PermissionSyncController::class, 'index'])->name('settings.permissions-sync');
            Route::post('/settings/permissions-sync', [PermissionSyncController::class, 'sync'])->name('settings.permissions-sync.run');
        });
        
        // ID Prefix Management
        Route::prefix('id-prefixes')->name('id-prefixes.')->group(function () {
            Route::get('/', [IdPrefixController::class, 'index'])->name('index');
            Route::get('/create', [IdPrefixController::class, 'create'])->name('create');
            Route::post('/', [IdPrefixController::class, 'store'])->name('store');
            Route::get('/{entityType}/edit', [IdPrefixController::class, 'edit'])->name('edit');
            Route::put('/{entityType}', [IdPrefixController::class, 'update'])->name('update');
            Route::get('/{entityType}/test', [IdPrefixController::class, 'test'])->name('test');
            Route::post('/{entityType}/reset-sequence', [IdPrefixController::class, 'resetSequence'])->name('reset-sequence');
            Route::post('/{entityType}/lock', [IdPrefixController::class, 'lock'])->name('lock');
            Route::post('/{entityType}/toggle-active', [IdPrefixController::class, 'toggleActive'])->name('toggle-active');
        });
    });
    
    // Walk-ins Register Routes
    // CRITICAL FIX: Accept both view_walk_ins_register OR manage_walk_ins to support direct permission assignments
    Route::middleware(['permission:view_walk_ins_register|manage_walk_ins'])->prefix('walk-ins')->name('walk-ins.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\WalkInsController::class, 'index'])->name('index');
        Route::get('/export', [App\Http\Controllers\Web\WalkInsController::class, 'export'])->name('export');
        Route::get('/export-csv', [App\Http\Controllers\Web\WalkInsController::class, 'exportCsv'])->name('export-csv');
        Route::get('/statistics', [App\Http\Controllers\Web\WalkInsController::class, 'statistics'])->name('statistics');
        // Specific routes must come before parameterized routes
        Route::get('/{id}/timeline', [App\Http\Controllers\Web\WalkInsController::class, 'timeline'])->name('timeline');
        Route::get('/{id}', [App\Http\Controllers\Web\WalkInsController::class, 'show'])->name('show');
    });
    
    // Service Pricing Management
    // CRITICAL FIX: Accept both view_service_pricing OR manage_service_pricing to support direct permission assignments
    Route::middleware(['permission:view_service_pricing|manage_service_pricing'])->group(function () {
        Route::get('/pricing/price-list', [ServicePricingController::class, 'priceList'])->name('pricing.price-list');
        Route::get('/pricing/export', [ServicePricingController::class, 'export'])->name('pricing.export');
        Route::post('/pricing/bulk-import', [ServicePricingController::class, 'bulkImport'])->name('pricing.bulk-import');
        Route::post('/pricing/{pricing}/toggle-active', [ServicePricingController::class, 'toggleActive'])->name('pricing.toggle-active');
        Route::resource('pricing', ServicePricingController::class);
    });
    
    // Revenue Analytics
    Route::middleware(['permission:view_revenue_analytics'])->group(function () {
        Route::get('/revenue', [RevenueAnalyticsController::class, 'index'])->name('revenue.index');
        Route::get('/revenue/export', [RevenueAnalyticsController::class, 'export'])->name('revenue.export');
        
        // Transaction Traceability Routes
        Route::get('/revenue/invoice/{invoice}/trail', [RevenueAnalyticsController::class, 'getInvoiceTransactionTrail'])->name('revenue.invoice.trail');
        Route::get('/revenue/payment/{payment}/trail', [RevenueAnalyticsController::class, 'getPaymentTransactionTrail'])->name('revenue.payment.trail');
        Route::get('/revenue/transactions/service-type', [RevenueAnalyticsController::class, 'getTransactionsByServiceType'])->name('revenue.transactions.service-type');
        Route::get('/revenue/transactions/payment-method', [RevenueAnalyticsController::class, 'getTransactionsByPaymentMethod'])->name('revenue.transactions.payment-method');
        Route::get('/revenue/transactions/detailed', [RevenueAnalyticsController::class, 'getDetailedTransactionTrail'])->name('revenue.transactions.detailed');
    });

    // Reports hub — index visible when user can access any catalog report; sub-routes use specific permissions
    Route::middleware(['permission:' . \App\Services\ReportCatalog::hubMiddlewarePermissions()])->group(function () {
        Route::get('/reports', [ReportsHubController::class, 'index'])->name('reports.index');
    });
    Route::middleware(['permission:view_financial_reports|view_revenue_analytics'])->group(function () {
        Route::get('/reports/financial', [FinancialReportWebController::class, 'index'])->name('reports.financial');
    });
    Route::middleware(['permission:view_reports|generate_reports'])->group(function () {
        Route::get('/reports/ghs', [GhsReportWebController::class, 'index'])->name('reports.ghs.index');
        Route::get('/reports/nhis', [NhisClaimWebController::class, 'index'])->name('reports.nhis.index');
    });
    
    // Eye Services (minimal web UI)
    Route::middleware(['permission:view_consultations'])->prefix('eye-services')->name('eye-services.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\EyeServiceController::class, 'index'])->name('index');
        Route::get('/{eyeService}', [App\Http\Controllers\Web\EyeServiceController::class, 'show'])->name('show');
    });

    // Blood Bank
    Route::middleware(['permission:view_wards'])->prefix('blood-bank')->name('blood-bank.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\BloodBankController::class, 'index'])->name('index');
        Route::get('/donations/create', [App\Http\Controllers\Web\BloodBankController::class, 'createDonation'])->name('donations.create');
        Route::post('/donations', [App\Http\Controllers\Web\BloodBankController::class, 'storeDonation'])->name('donations.store');
        Route::get('/donations/{donation}', [App\Http\Controllers\Web\BloodBankController::class, 'showDonation'])->name('donations.show');
        Route::get('/donations/{donation}/edit', [App\Http\Controllers\Web\BloodBankController::class, 'editDonation'])->name('donations.edit');
        Route::put('/donations/{donation}', [App\Http\Controllers\Web\BloodBankController::class, 'updateDonation'])->name('donations.update');
        Route::get('/inventory/{inventory}', [App\Http\Controllers\Web\BloodBankController::class, 'showInventory'])->name('inventory.show');
        Route::get('/inventory/{inventory}/edit', [App\Http\Controllers\Web\BloodBankController::class, 'editInventory'])->name('inventory.edit');
        Route::put('/inventory/{inventory}', [App\Http\Controllers\Web\BloodBankController::class, 'updateInventory'])->name('inventory.update');
        Route::get('/transfusions/create', [App\Http\Controllers\Web\BloodBankController::class, 'createTransfusion'])->name('transfusions.create');
        Route::post('/transfusions', [App\Http\Controllers\Web\BloodBankController::class, 'storeTransfusion'])->name('transfusions.store');
        Route::get('/transfusions/{transfusion}', [App\Http\Controllers\Web\BloodBankController::class, 'showTransfusion'])->name('transfusions.show');
        Route::get('/transfusions/{transfusion}/edit', [App\Http\Controllers\Web\BloodBankController::class, 'editTransfusion'])->name('transfusions.edit');
        Route::put('/transfusions/{transfusion}', [App\Http\Controllers\Web\BloodBankController::class, 'updateTransfusion'])->name('transfusions.update');
    });

    // ICU dashboard
    Route::middleware(['permission:view_wards'])->prefix('icu')->name('icu.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\IcuController::class, 'index'])->name('index');
        Route::middleware(['permission:manage_wards'])->group(function () {
            Route::get('/create', [App\Http\Controllers\Web\IcuController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Web\IcuController::class, 'store'])->name('store');
            Route::get('/{icu}/edit', [App\Http\Controllers\Web\IcuController::class, 'edit'])->name('edit');
            Route::put('/{icu}', [App\Http\Controllers\Web\IcuController::class, 'update'])->name('update');
            Route::get('/{icu}/discharge', [App\Http\Controllers\Web\IcuController::class, 'dischargeForm'])->name('discharge.form');
            Route::post('/{icu}/discharge', [App\Http\Controllers\Web\IcuController::class, 'discharge'])->name('discharge');
        });
        Route::get('/{icu}', [App\Http\Controllers\Web\IcuController::class, 'show'])->name('show');
    });

    // Audit trail
    Route::middleware(['permission:view_audit_logs'])->prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\AuditController::class, 'index'])->name('index');
    });

    // Stock count / cycle count
    Route::middleware(['permission:view_pharmacy'])->prefix('stock-count')->name('stock-count.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\StockCountController::class, 'index'])->name('index');
        Route::middleware(['permission:manage_pharmacy_inventory'])->group(function () {
            Route::get('/create', [App\Http\Controllers\Web\StockCountController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Web\StockCountController::class, 'store'])->name('store');
            Route::get('/{stockCount}', [App\Http\Controllers\Web\StockCountController::class, 'show'])->name('show');
            Route::put('/{stockCount}/counts', [App\Http\Controllers\Web\StockCountController::class, 'updateCounts'])->name('update-counts');
            Route::post('/{stockCount}/complete', [App\Http\Controllers\Web\StockCountController::class, 'complete'])->name('complete');
        });
    });
    
    // Customer Complaints Management
    // CRITICAL FIX: Accept both view_complaints OR manage_complaints to support direct permission assignments
    Route::middleware(['permission:view_complaints|manage_complaints'])->group(function () {
        Route::resource('complaints', ComplaintController::class);
    });
    
    // Workflow Notification Preferences (Web Only - No permissions needed, user-specific)
    Route::prefix('notification-preferences')->name('notification-preferences.')->group(function () {
        Route::get('/', [NotificationPreferenceController::class, 'index'])->name('index');
        Route::post('/', [NotificationPreferenceController::class, 'update'])->name('update');
        Route::get('/check-new-work', [NotificationPreferenceController::class, 'checkNewWork'])->name('check-new-work');
    });
    
    // Notification Settings Page (user-specific workflow preferences)
    Route::get('/notification-settings', [NotificationPreferenceController::class, 'settings'])->name('notification-settings');
    
    // Print Settings
    Route::middleware(['permission:view_settings'])->group(function () {
        Route::get('/print-settings', [PrintSettingsController::class, 'index'])->name('print-settings.index');
        Route::post('/print-settings', [PrintSettingsController::class, 'store'])->name('print-settings.store');
        Route::get('/print-settings/formats', [PrintSettingsController::class, 'getFormats'])->name('print-settings.formats');
        Route::get('/print-settings/current', [PrintSettingsController::class, 'getSettings'])->name('print-settings.current');
        
        // Print preview routes
        Route::get('/invoices/{id}/preview', [PrintSettingsController::class, 'previewInvoice'])->name('invoices.preview');
        Route::get('/receipts/{id}/preview', [PrintSettingsController::class, 'previewReceipt'])->name('receipts.preview');
        
        // Print generation routes
        Route::get('/invoices/{id}/print', [PrintSettingsController::class, 'generateInvoicePdf'])->name('invoices.print');
        Route::get('/receipts/{id}/print', [PrintSettingsController::class, 'generateReceiptPdf'])->name('receipts.print');
    });
    
    // Emergency Management
    Route::middleware(['permission:view_emergency_visits'])->group(function () {
        Route::get('emergency/export', [App\Http\Controllers\Web\EmergencyController::class, 'export'])->name('emergency.export');
        Route::resource('emergency', App\Http\Controllers\Web\EmergencyController::class);
    });
    
    // Emergency Alerts Management
    Route::middleware(['permission:view_emergency_alerts'])->group(function () {
        Route::resource('emergency-alerts', App\Http\Controllers\Web\EmergencyAlertController::class);
        Route::post('emergency-alerts/{emergencyAlert}/acknowledge', [App\Http\Controllers\Web\EmergencyAlertController::class, 'acknowledge'])->name('emergency-alerts.acknowledge');
        Route::post('emergency-alerts/{emergencyAlert}/resolve', [App\Http\Controllers\Web\EmergencyAlertController::class, 'resolve'])->name('emergency-alerts.resolve');
    });
    
    // Surgery & Theatre Management
    Route::middleware(['permission:view_surgery_schedules'])->group(function () {
        Route::get('surgery/export', [App\Http\Controllers\Web\SurgeryController::class, 'export'])->name('surgery.export');
        Route::resource('surgery', App\Http\Controllers\Web\SurgeryController::class);
        Route::post('surgery/{surgery}/start', [App\Http\Controllers\Web\SurgeryController::class, 'start'])->name('surgery.start');
        Route::post('surgery/{surgery}/complete', [App\Http\Controllers\Web\SurgeryController::class, 'complete'])->name('surgery.complete');
    });
    
    // E-Commerce Management
    Route::middleware(['permission:view_store_items'])->group(function () {
        // E-Commerce Dashboard (must come before resource route)
        Route::get('ecommerce/dashboard', [App\Http\Controllers\Web\ECommerceController::class, 'dashboard'])->name('ecommerce.dashboard');
        
        Route::get('ecommerce/export', [App\Http\Controllers\Web\ECommerceController::class, 'export'])->name('ecommerce.export');
        // Orders Management (must come before resource route)
        Route::get('ecommerce/orders/export', [App\Http\Controllers\Web\ECommerceController::class, 'exportOrders'])->name('ecommerce.orders.export');
        Route::get('ecommerce/orders', [App\Http\Controllers\Web\ECommerceController::class, 'orders'])->name('ecommerce.orders');
        Route::get('ecommerce/orders/{order}', [App\Http\Controllers\Web\ECommerceController::class, 'showOrder'])->name('ecommerce.orders.show');
        Route::post('ecommerce/orders/{order}/status', [App\Http\Controllers\Web\ECommerceController::class, 'updateOrderStatus'])->name('ecommerce.orders.status');
        
        // Delivery Management (must come before resource route)
        Route::get('ecommerce/deliveries', [App\Http\Controllers\Web\ECommerceController::class, 'deliveries'])->name('ecommerce.deliveries');
        Route::post('ecommerce/deliveries/{delivery}/assign', [App\Http\Controllers\Web\ECommerceController::class, 'assignDelivery'])->name('ecommerce.deliveries.assign');
        Route::post('ecommerce/deliveries/{delivery}/status', [App\Http\Controllers\Web\ECommerceController::class, 'updateDeliveryStatus'])->name('ecommerce.deliveries.status');
        
        // Delivery Riders Management (must come before resource route)
        Route::get('ecommerce/riders', [App\Http\Controllers\Web\ECommerceController::class, 'riders'])->name('ecommerce.riders');
        Route::get('ecommerce/riders/create', [App\Http\Controllers\Web\ECommerceController::class, 'createRider'])->name('ecommerce.riders.create');
        Route::post('ecommerce/riders', [App\Http\Controllers\Web\ECommerceController::class, 'storeRider'])->name('ecommerce.riders.store');
        Route::get('ecommerce/riders/{rider}', [App\Http\Controllers\Web\ECommerceController::class, 'showRider'])->name('ecommerce.riders.show');
        Route::get('ecommerce/riders/{rider}/edit', [App\Http\Controllers\Web\ECommerceController::class, 'editRider'])->name('ecommerce.riders.edit');
        Route::put('ecommerce/riders/{rider}', [App\Http\Controllers\Web\ECommerceController::class, 'updateRider'])->name('ecommerce.riders.update');
        Route::delete('ecommerce/riders/{rider}', [App\Http\Controllers\Web\ECommerceController::class, 'destroyRider'])->name('ecommerce.riders.destroy');
        
        // Store Items Management (resource route MUST come LAST to avoid route conflicts)
        Route::resource('ecommerce', App\Http\Controllers\Web\ECommerceController::class);
    });

    // Jitsi Settings
    Route::middleware(['permission:view_settings'])->group(function () {
        Route::get('/settings/jitsi', [JitsiSettingsController::class, 'index'])->name('settings.jitsi');
        Route::put('/settings/jitsi', [JitsiSettingsController::class, 'update'])->name('settings.jitsi.update');
        Route::post('/settings/jitsi/test', [JitsiSettingsController::class, 'testConnection'])->name('settings.jitsi.test');
    });

    // App Version Settings
    Route::middleware(['permission:view_settings'])->group(function () {
        Route::get('/settings/app-versions', [\App\Http\Controllers\Web\AppVersionSettingsController::class, 'index'])->name('settings.app-versions');
        Route::post('/settings/app-versions', [\App\Http\Controllers\Web\AppVersionSettingsController::class, 'store'])->name('settings.app-versions.store');
        Route::put('/settings/app-versions/{version}', [\App\Http\Controllers\Web\AppVersionSettingsController::class, 'update'])->name('settings.app-versions.update');
        Route::delete('/settings/app-versions/{version}', [\App\Http\Controllers\Web\AppVersionSettingsController::class, 'destroy'])->name('settings.app-versions.destroy');
        Route::post('/settings/app-versions/{version}/toggle-active', [\App\Http\Controllers\Web\AppVersionSettingsController::class, 'toggleActive'])->name('settings.app-versions.toggle-active');
        Route::post('/settings/app-versions/{version}/toggle-force', [\App\Http\Controllers\Web\AppVersionSettingsController::class, 'toggleForceUpdate'])->name('settings.app-versions.toggle-force');
    });

    // Payment Settings (Paystack)
    Route::middleware(['permission:view_settings'])->group(function () {
        Route::get('/settings/payment', [\App\Http\Controllers\Web\PaymentSettingsController::class, 'index'])->name('settings.payment');
        Route::put('/settings/payment', [\App\Http\Controllers\Web\PaymentSettingsController::class, 'update'])->name('settings.payment.update');
        Route::post('/settings/payment/test', [\App\Http\Controllers\Web\PaymentSettingsController::class, 'testConnection'])->name('settings.payment.test');
    });

    // Appointment Fees Management
    Route::middleware(['permission:view_settings'])->group(function () {
        Route::resource('appointment-fees', AppointmentFeeController::class)->names([
            'index' => 'appointment-fees.index',
            'create' => 'appointment-fees.create',
            'store' => 'appointment-fees.store',
            'show' => 'appointment-fees.show',
            'edit' => 'appointment-fees.edit',
            'update' => 'appointment-fees.update',
            'destroy' => 'appointment-fees.destroy',
        ]);
        Route::post('/appointment-fees/bulk-create', [AppointmentFeeController::class, 'bulkCreate'])->name('appointment-fees.bulk-create');
        Route::patch('/appointment-fees/{appointmentFee}/toggle-status', [AppointmentFeeController::class, 'toggleStatus'])->name('appointment-fees.toggle-status');
    });

    // Debug routes (APP_DEBUG only)
    if (config('app.debug')) {
        Route::get('/debug/realtime', function () {
            return view('test-realtime-debug');
        })->name('debug.realtime');

        Route::get('/debug/test', function () {
            return response()->json([
                'success' => true,
                'message' => 'Test route working',
                'user' => Auth::check() ? Auth::user()->name : 'Not authenticated',
                'session_id' => session()->getId(),
            ]);
        })->name('debug.test');

        Route::get('/debug/realtime-simple', function () {
            return view('test-realtime-simple');
        })->name('debug.realtime-simple');

        Route::get('/debug/dynamic-urls', function () {
            return view('test-dynamic-urls');
        })->name('debug.dynamic-urls');
    }

    // Teleconsultation Management
    // Note: Specific routes MUST come before dynamic routes (like {teleconsultation})
    
    // CREATE routes (most specific - must be first)
    Route::middleware(['permission:teleconsultation.create'])->group(function () {
        Route::get('/teleconsultations/create', [TeleconsultationController::class, 'create'])->name('teleconsultations.create');
        Route::post('/teleconsultations', [TeleconsultationController::class, 'store'])->name('teleconsultations.store');
    });
    
    // VIEW routes (specific routes like debug, test must come before {teleconsultation})
    Route::middleware(['permission:teleconsultation.view'])->group(function () {
        Route::get('/teleconsultations', [TeleconsultationController::class, 'index'])->name('teleconsultations.index');
        if (config('app.debug')) {
            Route::get('/teleconsultations/debug', [TeleconsultationController::class, 'debug'])->name('teleconsultations.debug');
            Route::get('/teleconsultations/test', [TeleconsultationController::class, 'test'])->name('teleconsultations.test');
        }
        Route::get('/teleconsultations/{teleconsultation}', [TeleconsultationController::class, 'show'])->name('teleconsultations.show');
    });
    
    // EDIT routes
    Route::middleware(['permission:teleconsultation.edit'])->group(function () {
        Route::get('/teleconsultations/{teleconsultation}/edit', [TeleconsultationController::class, 'edit'])->name('teleconsultations.edit');
        Route::put('/teleconsultations/{teleconsultation}', [TeleconsultationController::class, 'update'])->name('teleconsultations.update');
        Route::post('/teleconsultations/{teleconsultation}/start', [TeleconsultationController::class, 'start'])->name('teleconsultations.start');
        Route::post('/teleconsultations/{teleconsultation}/end', [TeleconsultationController::class, 'end'])->name('teleconsultations.end');
        Route::post('/teleconsultations/{teleconsultation}/cancel', [TeleconsultationController::class, 'cancel'])->name('teleconsultations.cancel');
        Route::post('/teleconsultations/{teleconsultation}/consent', [TeleconsultationController::class, 'giveConsent'])->name('teleconsultations.consent');
    });
    
    // DELETE routes
    Route::middleware(['permission:teleconsultation.delete'])->group(function () {
        Route::delete('/teleconsultations/{teleconsultation}', [TeleconsultationController::class, 'destroy'])->name('teleconsultations.destroy');
    });
    
});

// Emergency Alerts API routes (for web interface)
Route::middleware(['auth', 'permission:view_emergency_alerts'])->group(function () {
    Route::get('/api/emergency-alerts/active', [App\Http\Controllers\API\EmergencyAlertController::class, 'getActiveAlerts']);
    Route::post('/api/emergency-alerts/{alert}/acknowledge', [App\Http\Controllers\API\EmergencyAlertController::class, 'acknowledge']);
});

// Voice system test route (debug only)
if (config('app.debug')) {
    Route::get('/debug/voice-system', function () {
        return view('test-voice-system');
    })->name('debug.voice-system');

    Route::get('/test/404', function () {
        abort(404);
    })->name('test.404');

    Route::get('/test/403', function () {
        abort(403);
    })->name('test.403');

    Route::get('/test/500', function () {
        abort(500);
    })->name('test.500');
}

// Workflow API routes
Route::middleware(['auth'])->prefix('api/workflow')->name('workflow.')->group(function () {
    Route::get('/instance/{workflowInstance}/next-step', [App\Http\Controllers\Web\WorkflowController::class, 'getNextStep'])->name('next-step');
    Route::post('/instance/{workflowInstance}/complete-step', [App\Http\Controllers\Web\WorkflowController::class, 'completeStep'])->name('complete-step');
    Route::get('/instance/{workflowInstance}/status', [App\Http\Controllers\Web\WorkflowController::class, 'getStatus'])->name('status');
    Route::get('/instance/{workflowInstance}/progress', [App\Http\Controllers\Web\WorkflowController::class, 'getProgress'])->name('progress');
    Route::post('/log-action', [App\Http\Controllers\Web\WorkflowController::class, 'logAction'])->name('log-action');
});

