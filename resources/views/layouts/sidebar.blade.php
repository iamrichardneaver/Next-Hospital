<aside class="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        @php
            // Always fetch fresh branding data (no cache)
            $branding = \App\Helpers\BrandingHelper::getBranding();
        @endphp
        @if($branding->logo_url)
            <div class="logo-container mb-2">
                <img src="{{ $branding->logo_url }}" alt="{{ $branding->business_name ?? 'Logo' }}" 
                     style="max-height: 40px; max-width: 150px; object-fit: contain;" 
                     id="sidebar-logo" />
            </div>
        @else
            <h3><i class="bi bi-hospital"></i> {{ $hospitalBranding['name'] ?? $branding->platform_name ?? 'Hospital' }}</h3>
        @endif
        <p>{{ $hospitalBranding['tagline'] ?? $branding->business_name ?? 'Healthcare Management System' }}</p>
    </div>
    
    <!-- Navigation Menu -->
    <ul class="sidebar-menu">
        <!-- Dashboard -->
        <li class="sidebar-menu-item">
            <a href="{{ route('dashboard') }}" class="sidebar-menu-link">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        @if(auth()->user()->isPatient())
        <li class="sidebar-menu-item">
            <a href="{{ route('appointments.index') }}" class="sidebar-menu-link">
                <i class="bi bi-calendar-check"></i>
                <span>My Appointments</span>
            </a>
        </li>
        @can('view_consultations')
        <li class="sidebar-menu-item">
            <a href="{{ route('consultations.index') }}" class="sidebar-menu-link">
                <i class="bi bi-clipboard2-pulse"></i>
                <span>My Consultations</span>
            </a>
        </li>
        @endcan
        <li class="sidebar-menu-item">
            <a href="{{ route('lab.my-results') }}" class="sidebar-menu-link">
                <i class="bi bi-clipboard-data"></i>
                <span>My Lab Results</span>
            </a>
        </li>
        @can('view_prescriptions')
        <li class="sidebar-menu-item">
            <a href="{{ route('pharmacy.my-prescriptions') }}" class="sidebar-menu-link">
                <i class="bi bi-prescription"></i>
                <span>My Prescriptions</span>
            </a>
        </li>
        @endcan
        @can('view_invoices')
        <li class="sidebar-menu-item">
            <a href="{{ route('billing.index') }}" class="sidebar-menu-link">
                <i class="bi bi-receipt"></i>
                <span>My Billing</span>
            </a>
        </li>
        @endcan
        @can('view_vitals')
        <li class="sidebar-menu-item">
            <a href="{{ route('vitals.index') }}" class="sidebar-menu-link">
                <i class="bi bi-heart-pulse"></i>
                <span>My Vitals</span>
            </a>
        </li>
        @endcan
        @can('teleconsultation.view')
        <li class="sidebar-menu-item">
            <a href="{{ route('teleconsultations.index') }}" class="sidebar-menu-link">
                <i class="bi bi-camera-video"></i>
                <span>Teleconsultations</span>
            </a>
        </li>
        @endcan
        <li class="sidebar-menu-item">
            <a href="{{ route('shop.index') }}" class="sidebar-menu-link">
                <i class="bi bi-bag"></i>
                <span>Shop</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="{{ route('shop.cart') }}" class="sidebar-menu-link">
                <i class="bi bi-cart"></i>
                <span>My Cart</span>
            </a>
        </li>
        @canany(['view_complaints', 'manage_complaints'])
        <li class="sidebar-menu-item">
            <a href="{{ route('complaints.index') }}" class="sidebar-menu-link">
                <i class="bi bi-chat-left-text"></i>
                <span>Feedback</span>
            </a>
        </li>
        @endcanany
        <li class="sidebar-menu-item">
            <a href="{{ route('profile.show') }}" class="sidebar-menu-link">
                <i class="bi bi-person-circle"></i>
                <span>My Profile</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="{{ route('notification-settings') }}" class="sidebar-menu-link">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </a>
        </li>
        @else
        @php
            $cashierSidebarOnly = auth()->user()->hasRole('cashier');
            $accountantSidebarOnly = auth()->user()->hasRole('accountant');
        @endphp
        
        <!-- Patients -->
        @can('view_patients')
        <li class="sidebar-menu-item">
            <a href="{{ route('patients.index') }}" class="sidebar-menu-link">
                <i class="bi bi-person-badge"></i>
                <span>Patients</span>
            </a>
        </li>
        @endcan

        <!-- Vitals (nurses — no consultation menu access) -->
        @unless($cashierSidebarOnly || $accountantSidebarOnly)
        @canany(['view_vitals', 'record_vitals'])
        @cannot('view_consultations')
        <li class="sidebar-menu-item has-submenu {{ request()->routeIs('vitals.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-activity"></i>
                <span>Vitals</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="sidebar-submenu">
                @can('view_vitals')
                <li><a href="{{ route('vitals.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-list"></i>
                    <span>All Vitals</span>
                </a></li>
                @endcan
                @can('record_vitals')
                <li><a href="{{ route('vitals.create') }}" class="sidebar-submenu-link">
                    <i class="bi bi-clipboard-pulse"></i>
                    <span>Record Vitals</span>
                </a></li>
                @endcan
                @can('create_expenses')
                <li><a href="{{ route('nursing.expenses.create') }}" class="sidebar-submenu-link">
                    <i class="bi bi-wallet2"></i>
                    <span>Record Expense</span>
                </a></li>
                @endcan
            </ul>
        </li>
        @endcannot
        @endcanany
        
        <!-- Walk-ins Register -->
        @canany(['view_walk_ins_register', 'manage_walk_ins'])
        <li class="sidebar-menu-item">
            <a href="{{ route('walk-ins.index') }}" class="sidebar-menu-link">
                <i class="bi bi-clipboard-check"></i>
                <span>Walk-ins Register</span>
            </a>
        </li>
        @endcanany

        <!-- My Expenses (department staff) -->
        @can('view_own_expenses')
        <li class="sidebar-menu-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
            <a href="{{ route('expenses.my') }}" class="sidebar-menu-link">
                <i class="bi bi-wallet2"></i>
                <span>My Expenses</span>
            </a>
        </li>
        @endcan
        
        <!-- Appointments (receptionist: view/create/edit via RefineReceptionistPermissionsSeeder) -->
        @can('view_appointments')
        <li class="sidebar-menu-item has-submenu {{ request()->routeIs('appointments.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-calendar-check"></i>
                <span>Appointments</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="sidebar-submenu">
                <li><a href="{{ route('appointments.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-list"></i>
                    <span>All Appointments</span>
                </a></li>
                @can('create_appointments')
                <li><a href="{{ route('appointments.create') }}" class="sidebar-submenu-link">
                    <i class="bi bi-plus-circle"></i>
                    <span>Book Appointment</span>
                </a></li>
                @endcan
                @can('create_expenses')
                <li><a href="{{ route('reception.expenses.create') }}" class="sidebar-submenu-link">
                    <i class="bi bi-wallet2"></i>
                    <span>Record Expense</span>
                </a></li>
                @endcan
                @can('view_appointment_slots')
                <li><a href="{{ route('appointments.slots.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-calendar3"></i>
                    <span>Appointment Slots</span>
                </a></li>
                @endcan
            </ul>
        </li>
        @endcan
        
        <!-- Doctor Schedule (My Schedule for doctors, Doctor Schedules for admins) -->
        @canany(['manage_doctor_schedules', 'view_doctor_schedules', 'manage_appointments'])
        <li class="sidebar-menu-item">
            <a href="{{ route('doctor-schedules.index') }}" class="sidebar-menu-link">
                <i class="bi bi-calendar-event"></i>
                <span>{{ auth()->user()->hasRole('doctor') ? 'My Schedule' : 'Doctor Schedules' }}</span>
            </a>
        </li>
        @endcanany
        
        <!-- Consultations -->
        @can('view_consultations')
        <li class="sidebar-menu-item has-submenu {{ request()->routeIs('consultations.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-clipboard2-pulse"></i>
                <span>Consultations</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="sidebar-submenu">
                <li><a href="{{ route('consultations.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-list"></i>
                    <span>All Consultations</span>
                </a></li>
                @can('manage_consultations')
                <li><a href="{{ route('consultations.doctor-queue') }}" class="sidebar-submenu-link">
                    <i class="bi bi-person-check"></i>
                    <span>Doctor Queue</span>
                </a></li>
                @endcan
                <li><a href="{{ route('consultations.completed') }}" class="sidebar-submenu-link">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Completed History</span>
                </a></li>
                @can('record_vitals')
                <li><a href="{{ route('vitals.create') }}" class="sidebar-submenu-link">
                    <i class="bi bi-clipboard-pulse"></i>
                    <span>Record Vitals</span>
                </a></li>
                @endcan
            </ul>
        </li>
        @endcan
        
        <!-- Visits (OPD/IPD) -->
        @can('view_visits')
        <li class="sidebar-menu-item">
            <a href="{{ route('visits.index') }}" class="sidebar-menu-link">
                <i class="bi bi-person-check"></i>
                <span>Visits (OPD/IPD)</span>
            </a>
        </li>
        @endcan
        
        <!-- Queue Management (receptionist: OPD via view_opd_queue + manage_opd_queue) -->
        @can('view_queues')
        <li class="sidebar-menu-item has-submenu {{ request()->routeIs('queues.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-list-ol"></i>
                <span>Queue Management</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="sidebar-submenu">
                @can('view_queue_statistics')
                <li><a href="{{ route('queues.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a></li>
                @endcan
                @can('view_opd_queue')
                <li><a href="{{ route('queues.opd') }}" class="sidebar-submenu-link">
                    <i class="bi bi-hospital"></i>
                    <span>OPD Queue</span>
                </a></li>
                @endcan
                @can('view_lab_queue')
                <li><a href="{{ route('queues.lab') }}" class="sidebar-submenu-link">
                    <i class="bi bi-clipboard2-check"></i>
                    <span>Lab Queue</span>
                </a></li>
                @endcan
                @can('view_pharmacy_queue')
                <li><a href="{{ route('queues.pharmacy') }}" class="sidebar-submenu-link">
                    <i class="bi bi-capsule"></i>
                    <span>Pharmacy Queue</span>
                </a></li>
                @endcan
                @can('view_emergency_queue')
                <li><a href="{{ route('queues.emergency') }}" class="sidebar-submenu-link">
                    <i class="bi bi-heart-pulse-fill"></i>
                    <span>Emergency Queue</span>
                </a></li>
                @endcan
            </ul>
        </li>
        @endcan
        
        <!-- Teleconsultations -->
        @can('teleconsultation.view')
        <li class="sidebar-menu-item">
            <a href="{{ route('teleconsultations.index') }}" class="sidebar-menu-link">
                <i class="bi bi-camera-video"></i>
                <span>Teleconsultations</span>
            </a>
        </li>
        @endcan
        
        <!-- Pharmacy — hidden from doctors (they prescribe via consultations only) -->
        @canany(['manage_pharmacy_inventory', 'dispense_drugs', 'view_pharmacy_analytics', 'create_drugs', 'edit_drugs', 'view_drugs', 'view_pharmacy_purchases', 'create_pharmacy_purchases', 'receive_pharmacy_purchases', 'view_pharmacy_suppliers', 'manage_pharmacy_suppliers'])
        <li class="sidebar-menu-item has-submenu {{ request()->routeIs('pharmacy.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-capsule"></i>
                <span>Pharmacy</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="sidebar-submenu">
                @canany(['manage_pharmacy_inventory', 'create_drugs', 'edit_drugs', 'view_drugs'])
                <li><a href="{{ route('pharmacy.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-list-ul"></i>
                    <span>Drug Formulary</span>
                </a></li>
                @endcanany
                @canany(['dispense_drugs', 'manage_pharmacy_inventory'])
                <li><a href="{{ route('pharmacy.prescriptions') }}" class="sidebar-submenu-link">
                    <i class="bi bi-prescription"></i>
                    <span>Prescriptions</span>
                </a></li>
                @endcanany
                @can('dispense_drugs')
                <li><a href="{{ route('pharmacy.dispensing') }}" class="sidebar-submenu-link">
                    <i class="bi bi-capsule-pill"></i>
                    <span>Dispensing</span>
                </a></li>
                @endcan
                @canany(['manage_pharmacy_inventory', 'manage_inventory'])
                <li><a href="{{ route('pharmacy.stock') }}" class="sidebar-submenu-link">
                    <i class="bi bi-box-seam"></i>
                    <span>Inventory</span>
                </a></li>
                @endcanany
                @can('manage_pharmacy_inventory')
                <li><a href="{{ route('stock-count.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Stock Counts</span>
                </a></li>
                @endcan
                @canany(['view_pharmacy_purchases', 'create_pharmacy_purchases', 'receive_pharmacy_purchases'])
                <li><a href="{{ route('pharmacy.purchases.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-cart-plus"></i>
                    <span>Pharmacy Purchases</span>
                </a></li>
                @endcanany
                @canany(['view_pharmacy_suppliers', 'manage_pharmacy_suppliers'])
                <li><a href="{{ route('pharmacy.suppliers.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-truck"></i>
                    <span>Suppliers</span>
                </a></li>
                @endcanany
                @can('view_pharmacy_analytics')
                <li><a href="{{ route('pharmacy.analytics') }}" class="sidebar-submenu-link">
                    <i class="bi bi-graph-up"></i>
                    <span>Analytics</span>
                </a></li>
                @endcan
                @can('create_expenses')
                <li><a href="{{ route('pharmacy.expenses.create') }}" class="sidebar-submenu-link">
                    <i class="bi bi-wallet2"></i>
                    <span>Record Expense</span>
                </a></li>
                @endcan
            </ul>
        </li>
        @endcanany
        
        <!-- E-Commerce & Store -->
        @can('view_store_items')
        <li class="sidebar-menu-item has-submenu">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-shop"></i>
                <span>E-Commerce</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="sidebar-submenu">
                <li><a href="{{ route('ecommerce.dashboard') }}" class="sidebar-submenu-link">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a></li>
                <li><a href="{{ route('ecommerce.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-boxes"></i>
                    <span>Store Items</span>
                </a></li>
                <li><a href="{{ route('ecommerce.orders') }}" class="sidebar-submenu-link">
                    <i class="bi bi-cart-check"></i>
                    <span>Orders</span>
                </a></li>
                <li><a href="{{ route('ecommerce.deliveries') }}" class="sidebar-submenu-link">
                    <i class="bi bi-truck"></i>
                    <span>Deliveries</span>
                </a></li>
                <li><a href="{{ route('ecommerce.riders') }}" class="sidebar-submenu-link">
                    <i class="bi bi-person-badge"></i>
                    <span>Delivery Riders</span>
                </a></li>
            </ul>
        </li>
        @endcan
        
        <!-- Laboratory -->
        @canany(['view_lab_requests', 'manage_lab_setup', 'view_lab_inventory', 'view_lab_purchases', 'create_lab_purchases', 'receive_lab_purchases', 'view_lab_suppliers', 'manage_lab_suppliers'])
        <li class="sidebar-menu-item has-submenu {{ request()->routeIs('lab.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-heart-pulse"></i>
                <span>Laboratory</span>
                <i class="bi bi-chevron-down submenu-icon"></i>
            </a>
            <ul class="sidebar-submenu">
                <li>
                    <a href="{{ route('lab.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-list-ul"></i>
                        <span>Lab Requests</span>
                    </a>
                </li>
                @can('manage_lab_setup')
                <li class="submenu-divider"></li>
                <li>
                    <a href="{{ route('lab.categories') }}" class="sidebar-submenu-link">
                        <i class="bi bi-folder"></i>
                        <span>Test Categories</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('lab.test-types') }}" class="sidebar-submenu-link">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Test Types</span>
                    </a>
                </li>
                
                <li>
                    <a href="{{ route('lab.tests') }}" class="sidebar-submenu-link">
                        <i class="bi bi-clipboard-data"></i>
                        <span>Individual Tests</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('lab.templates') }}" class="sidebar-submenu-link">
                        <i class="bi bi-file-earmark-medical"></i>
                        <span>Test Templates</span>
                    </a>
                </li>

                <li> <hr class="my-2"></li>
                
                <li class="submenu-divider"></li>
                <li>
                    <a href="{{ route('lab.quality-control.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Quality Control</span>
                        
                    </a>
                </li>
                <li>
                    <a href="{{ route('lab.equipment.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-gear"></i>
                        <span>Equipment</span>
                    </a>
                </li>
                @endcan
                <li class="submenu-divider"></li>
                <li>
                    <a href="{{ route('lab.archive.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-archive"></i>
                        <span>Lab Archive</span>
                    </a>
                </li>
                @canany(['view_lab_inventory', 'view_lab_purchases', 'create_lab_purchases', 'receive_lab_purchases', 'view_lab_suppliers', 'manage_lab_suppliers'])
                <li class="submenu-divider"></li>
                @can('view_lab_inventory')
                <li>
                    <a href="{{ route('lab.inventory.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-boxes"></i>
                        <span>Lab Inventory</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('lab.inventory.movements') }}" class="sidebar-submenu-link">
                        <i class="bi bi-clock-history"></i>
                        <span>Stock Movements</span>
                    </a>
                </li>
                @endcan
                @canany(['view_lab_purchases', 'create_lab_purchases', 'receive_lab_purchases'])
                <li>
                    <a href="{{ route('lab.purchases.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-cart-plus"></i>
                        <span>Lab Supplies Purchases</span>
                    </a>
                </li>
                @endcanany
                @canany(['view_lab_suppliers', 'manage_lab_suppliers'])
                <li>
                    <a href="{{ route('lab.suppliers.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-truck"></i>
                        <span>Suppliers</span>
                    </a>
                </li>
                @endcanany
                @endcanany
                @can('create_expenses')
                <li class="submenu-divider"></li>
                <li>
                    <a href="{{ route('lab.expenses.create') }}" class="sidebar-submenu-link">
                        <i class="bi bi-wallet2"></i>
                        <span>Record Expense</span>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endcanany
        
        <!-- Radiology — hidden from doctors (they order via consultations only) -->
        @canany(['view_radiology_requests', 'perform_radiology_studies', 'complete_radiology_studies', 'process_radiology_requests', 'manage_radiology_setup', 'edit_radiology_requests', 'create_radiology_studies', 'upload_radiology_images', 'create_radiology_reports', 'view_radiology_inventory', 'view_radiology_purchases', 'view_radiology_suppliers'])
        <li class="sidebar-menu-item has-submenu">
            <a href="#" class="sidebar-menu-link">
                <i class="bi bi-file-medical"></i>
                <span>Radiology & Imaging</span>
                <i class="bi bi-chevron-down submenu-arrow"></i>
            </a>
            <ul class="sidebar-submenu">
                @can('view_radiology_requests')
                <li>
                    <a href="{{ route('radiology.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-file-earmark-medical"></i>
                        <span>Radiology Requests</span>
                    </a>
                </li>
                @endcan
                
                @can('view_radiology_studies')
                <li>
                    <a href="{{ route('radiology.studies') }}" class="sidebar-submenu-link">
                        <i class="bi bi-image"></i>
                        <span>Studies & Images</span>
                    </a>
                </li>
                @endcan
                
                @can('view_radiology_reports')
                <li>
                    <a href="{{ route('radiology.reports') }}" class="sidebar-submenu-link">
                        <i class="bi bi-file-text"></i>
                        <span>Reports</span>
                    </a>
                </li>
                @endcan
                
                <li class="submenu-divider"></li>

                @canany(['view_radiology_inventory', 'view_radiology_purchases', 'create_radiology_purchases', 'receive_radiology_purchases'])
                <li>
                    <a href="{{ route('radiology.inventory.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-boxes"></i>
                        <span>Radiology Inventory</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('radiology.inventory.movements') }}" class="sidebar-submenu-link">
                        <i class="bi bi-clock-history"></i>
                        <span>Stock Movements</span>
                    </a>
                </li>
                @endcanany
                @canany(['view_radiology_purchases', 'create_radiology_purchases', 'receive_radiology_purchases'])
                <li>
                    <a href="{{ route('radiology.purchases.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-cart-plus"></i>
                        <span>Radiology Purchases</span>
                    </a>
                </li>
                @endcanany
                @canany(['view_radiology_suppliers', 'manage_radiology_suppliers'])
                <li>
                    <a href="{{ route('radiology.suppliers.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-truck"></i>
                        <span>Radiology Suppliers</span>
                    </a>
                </li>
                @endcanany
                
                @can('manage_radiology_setup')
                <li>
                    <a href="{{ route('radiology.index') }}?tab=equipment" class="sidebar-submenu-link">
                        <i class="bi bi-tools"></i>
                        <span>Equipment</span>
                    </a>
                </li>
                @endcan
                
                @can('manage_imaging_modalities')
                <li>
                    <a href="{{ route('radiology.index') }}?tab=modalities" class="sidebar-submenu-link">
                        <i class="bi bi-gear"></i>
                        <span>Modalities</span>
                    </a>
                </li>
                @endcan
                @can('create_expenses')
                <li class="submenu-divider"></li>
                <li>
                    <a href="{{ route('radiology.expenses.create') }}" class="sidebar-submenu-link">
                        <i class="bi bi-wallet2"></i>
                        <span>Record Expense</span>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endcan
        
        <!-- Wards & Beds -->
        @can('view_wards')
        <li class="sidebar-menu-item has-submenu {{ request()->routeIs('wards.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-building"></i>
                <span>Wards & Beds</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="sidebar-submenu">
                <li><a href="{{ route('wards.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-list"></i>
                    <span>Ward Overview</span>
                </a></li>
                @can('create_expenses')
                <li><a href="{{ route('nursing.expenses.create') }}" class="sidebar-submenu-link">
                    <i class="bi bi-wallet2"></i>
                    <span>Record Expense</span>
                </a></li>
                @endcan
            </ul>
        </li>
        @endcan
        
        <!-- Emergency -->
        @canany(['view_emergency_visits', 'view_emergency_alerts', 'view_emergency_queue'])
        <li class="sidebar-menu-item has-submenu">
            <a href="#" class="sidebar-menu-link">
                <i class="bi bi-hospital"></i>
                <span>Emergency</span>
                <i class="bi bi-chevron-down submenu-arrow"></i>
            </a>
            <ul class="sidebar-submenu">
                @can('view_emergency_alerts')
                <li><a href="{{ route('emergency-alerts.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Emergency Alerts</span>
                </a></li>
                @endcan
                @can('view_emergency_queue')
                <li><a href="{{ route('queues.emergency') }}" class="sidebar-submenu-link">
                    <i class="bi bi-heart-pulse-fill"></i>
                    <span>Emergency Queue</span>
                </a></li>
                @endcan
            </ul>
        </li>
        @endcanany
        
        <!-- Surgery -->
        @can('view_surgery_schedules')
        <li class="sidebar-menu-item">
            <a href="{{ route('surgery.index') }}" class="sidebar-menu-link">
                <i class="bi bi-scissors"></i>
                <span>Surgery</span>
            </a>
        </li>
        @endcan
        @endunless
        
        <!-- Accounting hub (financial oversight) -->
        @canany(['view_financial_dashboard', 'view_revenue_reports', 'view_expenses', 'view_balance_sheet', 'view_cash_flow'])
        <li class="sidebar-menu-item has-submenu {{ request()->routeIs('accounting.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-calculator"></i>
                <span>Accounting</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="sidebar-submenu">
                @can('view_financial_dashboard')
                <li><a href="{{ route('accounting.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-speedometer2"></i><span>Accounting Hub</span>
                </a></li>
                @endcan
                @can('view_revenue_reports')
                <li><a href="{{ route('accounting.revenue') }}" class="sidebar-submenu-link">
                    <i class="bi bi-pie-chart"></i><span>Revenue</span>
                </a></li>
                @endcan
                @canany(['view_expenses', 'manage_expenses', 'approve_expenses'])
                <li><a href="{{ route('accounting.expenses.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-wallet2"></i><span>Expenses</span>
                    @canany(['approve_expenses', 'manage_expenses'])
                    @php
                        $dashboardStats = app(\App\Services\DashboardStatsService::class);
                        $pendingExpenseCount = $dashboardStats->countPendingExpenseApprovals(
                            $dashboardStats->resolveBranchId(auth()->user())
                        );
                    @endphp
                    @if($pendingExpenseCount > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $pendingExpenseCount }}</span>
                    @endif
                    @endcanany
                </a></li>
                @endcanany
                @can('view_balance_sheet')
                <li><a href="{{ route('accounting.balance-sheet') }}" class="sidebar-submenu-link">
                    <i class="bi bi-clipboard-data"></i><span>Balance Sheet</span>
                </a></li>
                @endcan
                @can('view_cash_flow')
                <li><a href="{{ route('accounting.cash-flow') }}" class="sidebar-submenu-link">
                    <i class="bi bi-arrow-left-right"></i><span>Cash Flow</span>
                </a></li>
                @endcan
                @can('view_revenue_reports')
                <li><a href="{{ route('accounting.revenue-vs-expenses') }}" class="sidebar-submenu-link">
                    <i class="bi bi-bar-chart-line"></i><span>Revenue vs Expenses</span>
                </a></li>
                @endcan
            </ul>
        </li>
        @endcanany
        
        <!-- Billing -->
        @canany(['view_invoices', 'manage_billing'])
        <li class="sidebar-menu-item">
            <a href="{{ route('billing.index') }}" class="sidebar-menu-link">
                <i class="bi bi-receipt"></i>
                <span>Billing</span>
            </a>
        </li>
        @endcanany
        
        <!-- Cashier -->
        @can('process_payments')
        <li class="sidebar-menu-item has-submenu {{ request()->routeIs('cashier.*') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-cash-coin"></i>
                <span>Cashier</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="sidebar-submenu">
                <li><a href="{{ route('cashier.index') }}" class="sidebar-submenu-link">
                    <i class="bi bi-cash-stack"></i>
                    <span>Payment Center</span>
                </a></li>
                @can('view_payments')
                <li><a href="{{ route('cashier.index', ['tab' => 'history']) }}" class="sidebar-submenu-link">
                    <i class="bi bi-clock-history"></i>
                    <span>Payment History</span>
                </a></li>
                @endcan
                @can('create_expenses')
                <li><a href="{{ route('cashier.expenses.create') }}" class="sidebar-submenu-link">
                    <i class="bi bi-wallet2"></i>
                    <span>Record Expense</span>
                </a></li>
                @endcan
            </ul>
        </li>
        @endcan

        @can('view_cashier_reports')
        @cannot('process_payments')
        <li class="sidebar-menu-item {{ request()->routeIs('cashier.daily-report') ? 'active' : '' }}">
            <a href="{{ route('cashier.daily-report') }}" class="sidebar-menu-link">
                <i class="bi bi-journal-text"></i>
                <span>Cashier Reports</span>
            </a>
        </li>
        @endcannot
        @endcan
        
        <!-- Debtors -->
        @canany(['view_debtors', 'manage_debtors'])
        <li class="sidebar-menu-item">
            <a href="{{ route('debtors.index') }}" class="sidebar-menu-link">
                <i class="bi bi-people-fill"></i>
                <span>Debtors</span>
            </a>
        </li>
        @endcanany
        
        <!-- Insurance -->
        @unless($cashierSidebarOnly)
        @canany(['manage_insurance_providers', 'manage_insurance_policies', 'manage_insurance_claims', 'view_insurance'])
        <li class="sidebar-menu-item has-submenu">
            <a href="#" class="sidebar-menu-link">
                <i class="bi bi-shield-check"></i>
                <span>Insurance</span>
                <i class="bi bi-chevron-down submenu-icon"></i>
            </a>
            <ul class="sidebar-submenu">
                @can('view_insurance')
                <li>
                    <a href="{{ route('insurance.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                @endcan
                @can('manage_insurance_providers')
                <li>
                    <a href="{{ route('insurance.providers') }}" class="sidebar-submenu-link">
                        <i class="bi bi-building"></i>
                        <span>Providers</span>
                    </a>
                </li>
                @endcan
                @can('manage_insurance_policies')
                <li>
                    <a href="{{ route('insurance.policies') }}" class="sidebar-submenu-link">
                        <i class="bi bi-file-earmark-medical"></i>
                        <span>Policies</span>
                    </a>
                </li>
                @endcan
                @can('manage_insurance_claims')
                <li>
                    <a href="{{ route('insurance.claims') }}" class="sidebar-submenu-link">
                        <i class="bi bi-receipt"></i>
                        <span>Claims</span>
                    </a>
                </li>
                @endcan
                @can('manage_insurance_claims')
                <li>
                    <a href="{{ route('insurance.pre-authorizations') }}" class="sidebar-submenu-link">
                        <i class="bi bi-check-circle"></i>
                        <span>Pre-Authorizations</span>
                    </a>
                </li>
                @endcan
                @can('view_insurance_analytics')
                <li class="submenu-divider"></li>
                <li>
                    <a href="{{ route('insurance.analytics') }}" class="sidebar-submenu-link">
                        <i class="bi bi-graph-up"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endcanany
        
        <!-- Service Pricing -->
        @canany(['view_service_pricing', 'manage_service_pricing'])
        <li class="sidebar-menu-item">
            <a href="{{ route('pricing.index') }}" class="sidebar-menu-link">
                <i class="bi bi-tag"></i>
                <span>Service Pricing</span>
            </a>
        </li>
        @endcanany
        
        <!-- Revenue Analytics -->
        @can('view_revenue_analytics')
        <li class="sidebar-menu-item">
            <a href="{{ route('revenue.index') }}" class="sidebar-menu-link">
                <i class="bi bi-cash-stack"></i>
                <span>Revenue Analytics</span>
            </a>
        </li>
        @endcan
        
        <!-- Complaints -->
        @unless($accountantSidebarOnly)
        @canany(['view_complaints', 'manage_complaints'])
        <li class="sidebar-menu-item">
            <a href="{{ route('complaints.index') }}" class="sidebar-menu-link">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Complaints</span>
            </a>
        </li>
        @endcanany
        
        <!-- Inventory (legacy link — pharmacy submenu also has inventory) -->
        @canany(['manage_pharmacy_inventory', 'manage_inventory'])
        <li class="sidebar-menu-item">
            <a href="{{ route('pharmacy.stock') }}" class="sidebar-menu-link">
                <i class="bi bi-box-seam"></i>
                <span>Inventory</span>
            </a>
        </li>
        @endcanany
        @endunless
        @endunless
        
        <!-- Reports -->
        @if(\App\Services\ReportCatalog::userCanAccessHub(auth()->user()))
        <li class="sidebar-menu-item">
            <a href="{{ route('reports.index') }}" class="sidebar-menu-link">
                <i class="bi bi-graph-up"></i>
                <span>Reports</span>
            </a>
        </li>
        @endif
        
        @unless($cashierSidebarOnly || $accountantSidebarOnly)
        <!-- Eye Services -->
        @can('view_consultations')
        <li class="sidebar-menu-item">
            <a href="{{ route('eye-services.index') }}" class="sidebar-menu-link">
                <i class="bi bi-eye"></i>
                <span>Eye Services</span>
            </a>
        </li>
        @endcan

        <!-- Blood Bank (not in nurse clinical scope) -->
        @can('view_wards')
        @unless(auth()->user()->hasRole('nurse'))
        <li class="sidebar-menu-item">
            <a href="{{ route('blood-bank.index') }}" class="sidebar-menu-link">
                <i class="bi bi-droplet-fill"></i>
                <span>Blood Bank</span>
            </a>
        </li>
        @endunless
        @endcan

        <!-- ICU -->
        @can('view_wards')
        <li class="sidebar-menu-item">
            <a href="{{ route('icu.index') }}" class="sidebar-menu-link">
                <i class="bi bi-heart-pulse"></i>
                <span>ICU</span>
            </a>
        </li>
        @endcan
        
        <!-- Users Management -->
        @can('view_users')
        <li class="sidebar-menu-item">
            <a href="{{ route('users.index') }}" class="sidebar-menu-link">
                <i class="bi bi-people"></i>
                <span>Users</span>
            </a>
        </li>
        @endcan
        
        <!-- Roles & Permissions -->
        @can('manage_roles')
        <li class="sidebar-menu-item">
            <a href="{{ route('roles.index') }}" class="sidebar-menu-link">
                <i class="bi bi-shield-lock"></i>
                <span>Roles & Permissions</span>
            </a>
        </li>
        @endcan
        
        <!-- Branches -->
        @can('view_branches')
        <li class="sidebar-menu-item">
            <a href="{{ route('branches.index') }}" class="sidebar-menu-link">
                <i class="bi bi-geo-alt"></i>
                <span>Branches</span>
            </a>
        </li>
        @endcan
        @endunless
        
        <!-- Notification Settings -->
        <li class="sidebar-menu-item">
            <a href="{{ route('notification-settings') }}" class="sidebar-menu-link">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </a>
        </li>
        
        <!-- Settings -->
        @unless($cashierSidebarOnly)
        @can('view_settings')
        <li class="sidebar-menu-item has-submenu">
            <a href="javascript:void(0)" class="sidebar-menu-link">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="sidebar-submenu">
                <li>
                    <a href="{{ route('settings.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-gear-fill"></i>
                        <span>General Settings</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('settings.jitsi') }}" class="sidebar-submenu-link">
                        <i class="bi bi-camera-video"></i>
                        <span>Video Call (Jitsi)</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('settings.payment') }}" class="sidebar-submenu-link">
                        <i class="bi bi-credit-card"></i>
                        <span>Payment Settings</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('settings.app-versions') }}" class="sidebar-submenu-link">
                        <i class="bi bi-phone-fill"></i>
                        <span>Mobile App Versions</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('print-settings.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-printer"></i>
                        <span>Print Settings</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('id-prefixes.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-hash"></i>
                        <span>ID Prefixes</span>
                    </a>
                </li>
                @can('view_audit_logs')
                <li>
                    <a href="{{ route('audit.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-journal-text"></i>
                        <span>Audit Trail</span>
                    </a>
                </li>
                @endcan
                @can('view_appointment_fees')
                <li>
                    <a href="{{ route('appointment-fees.index') }}" class="sidebar-submenu-link">
                        <i class="bi bi-cash-coin"></i>
                        <span>Appointment Fees</span>
                    </a>
                </li>
                @endcan
                @can('manage_data_cleanup')
                <li>
                    <a href="{{ route('settings.clean-data') }}" class="sidebar-submenu-link text-danger">
                        <i class="bi bi-trash3"></i>
                        <span>Data Cleanup</span>
                    </a>
                </li>
                @endcan
                @if(auth()->user()->hasRole('super_admin'))
                <li>
                    <a href="{{ route('settings.permissions-sync') }}" class="sidebar-submenu-link">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Sync Permissions</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>
        @endcan
        @endunless
        @endif
    </ul>
</aside>
