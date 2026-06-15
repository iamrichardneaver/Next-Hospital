@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Dashboard</h1>
            <p class="text-secondary mb-0">Welcome back, {{ auth()->user()->name }}!</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary">{{ $userRole }}</span>
            <span class="badge bg-info">
                <i class="bi bi-clock"></i> {{ now()->format('M d, Y h:i A') }}
            </span>
            <span id="refresh-indicator" class="badge bg-secondary d-none">
                <i class="bi bi-arrow-clockwise"></i> <span id="last-refresh-time">--:--:--</span>
            </span>
            @can('create_visits')
            <a href="{{ route('visits.create') }}" class="btn btn-success btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-person-plus"></i>
                <span>Patient Check-in</span>
            </a>
            @endcan
        </div>
    </div>
    
    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <!-- Total Patients -->
        @if(isset($statistics['total_patients']) || auth()->user()->can('view_patients'))
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-label">Total Patients</div>
                <div class="stat-value" data-stat="total_patients">{{ number_format($statistics['total_patients'] ?? 0) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-graph-up"></i> All registered patients
                </div>
            </div>
        </div>
        @endif
        
        <!-- Total Users - Only for Admin/Super Admin -->
        @can('view_users') @if(isset($statistics['total_users']))
        <div class="col-md-3 mb-3">
            <div class="stat-card secondary">
                <div class="stat-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value" data-stat="total_users">{{ number_format($statistics['total_users']) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-people"></i> System users
                </div>
            </div>
        </div>
        @endif @endcan
        
        <!-- Total Branches - Only for Admin/Super Admin -->
        @can('view_branches') @if(isset($statistics['total_branches']))
        <div class="col-md-3 mb-3">
            <div class="stat-card dark">
                <div class="stat-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div class="stat-label">Total Branches</div>
                <div class="stat-value">{{ number_format($statistics['total_branches']) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-geo-alt"></i> Facility locations
                </div>
            </div>
        </div>
        @endif @endcan
        
        <!-- Today's Appointments -->
        @if(isset($statistics['today_appointments']) || isset($statistics['my_appointments_today']))
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-label">
                    @if($userRole === 'Doctor')
                        My Appointments Today
                    @else
                        Today's Appointments
                    @endif
                </div>
                <div class="stat-value" data-stat="{{ isset($statistics['my_appointments_today']) ? 'my_appointments_today' : 'today_appointments' }}">{{ $statistics['today_appointments'] ?? $statistics['my_appointments_today'] ?? 0 }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-clock-history"></i> Scheduled for today
                </div>
            </div>
        </div>
        @endif
        
        <!-- Active Visits / Assigned Visits -->
        @if(isset($statistics['active_visits']))
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-activity"></i>
                </div>
                <div class="stat-label">Active Visits</div>
                <div class="stat-value" data-stat="active_visits">{{ number_format($statistics['active_visits']) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-person-check"></i> Currently active
                </div>
            </div>
        </div>
        @endif
        
        <!-- My Assigned Visits (Nurse/Doctor specific) -->
        @if(isset($statistics['assigned_visits']) && ($userRole === 'Nurse' || $userRole === 'Doctor'))
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="stat-label">My Assigned Visits</div>
                <div class="stat-value" data-stat="assigned_visits">{{ number_format($statistics['assigned_visits']) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-person-check"></i> Assigned to me
                </div>
            </div>
        </div>
        @endif
        
        @if(!empty($statistics['revenue_visible']))
        <div class="col-md-3 mb-3">
            <div class="stat-card warning" @if(!empty($statistics['revenue_tooltip'])) title="{{ $statistics['revenue_tooltip'] }}" data-bs-toggle="tooltip" @endif>
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-label">{{ $statistics['revenue_label'] ?? 'Revenue' }}</div>
                <div class="stat-value">GH₵{{ number_format($statistics['revenue_amount'] ?? 0, 2) }}</div>
                <div class="small opacity-75">
                    @if(($statistics['revenue_scope'] ?? '') === 'all_time')
                        <i class="bi bi-infinity"></i> All completed payments
                    @elseif(($statistics['revenue_scope'] ?? '') === 'today_module')
                        <i class="bi bi-calendar-day"></i> Resets daily — your module only
                    @else
                        <i class="bi bi-graph-up-arrow"></i> Financial reporting scope
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if(!empty($statistics['revenue_visible']) && ($statistics['revenue_scope'] ?? '') === 'all_time' && isset($statistics['today_revenue']))
        <div class="col-md-3 mb-3">
            <div class="stat-card success" title="Today's completed payments at your branch" data-bs-toggle="tooltip">
                <div class="stat-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="stat-label">Today's Revenue</div>
                <div class="stat-value">GH₵{{ number_format($statistics['today_revenue'] ?? 0, 2) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-calendar-day"></i> Today only
                </div>
            </div>
        </div>
        @endif
        
        <!-- Total Invoices - Only for Accountant -->
        @if($userRole === 'Accountant' && isset($statistics['total_invoices']))
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-label">Total Invoices</div>
                <div class="stat-value">{{ number_format($statistics['total_invoices'] ?? 0) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-file-text"></i> All time invoices
                </div>
            </div>
        </div>
        @endif
        
        <!-- Doctor-specific stats -->
        @if($userRole === 'Doctor')
            @if(isset($statistics['my_consultations_today']))
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="bi bi-clipboard2-pulse"></i>
                    </div>
                    <div class="stat-label">My Consultations Today</div>
                    <div class="stat-value">{{ $statistics['my_consultations_today'] }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-clock-history"></i> Completed today
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['total_consultations']))
            <div class="col-md-3 mb-3">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div class="stat-label">Total Consultations</div>
                    <div class="stat-value">{{ number_format($statistics['total_consultations']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-graph-up"></i> All time
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['pending_consultations']))
            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-label">Pending Consultations</div>
                    <div class="stat-value">{{ number_format($statistics['pending_consultations']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-clock"></i> In queue
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['active_assigned_visits']))
            <div class="col-md-3 mb-3">
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div class="stat-label">Active Assigned Visits</div>
                    <div class="stat-value">{{ number_format($statistics['active_assigned_visits']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-activity"></i> Currently active
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['my_patients']))
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-label">My Patients</div>
                    <div class="stat-value">{{ number_format($statistics['my_patients']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-person-heart"></i> Unique patients
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['total_appointments']))
            <div class="col-md-3 mb-3">
                <div class="stat-card secondary">
                    <div class="stat-icon">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <div class="stat-label">Total Appointments</div>
                    <div class="stat-value">{{ number_format($statistics['total_appointments']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-calendar3"></i> All time
                    </div>
                </div>
            </div>
            @endif
        @endif
        
        <!-- Nurse-specific stats -->
        @if($userRole === 'Nurse')
            @if(isset($statistics['vitals_pending']))
            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <div class="stat-label">Vitals Pending</div>
                    <div class="stat-value" data-stat="vitals_pending">{{ $statistics['vitals_pending'] }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-clock-history"></i> Need vitals capture
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['my_vitals_today']))
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-label">My Vitals Recorded Today</div>
                    <div class="stat-value" data-stat="my_vitals_today">{{ $statistics['my_vitals_today'] }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-clipboard-check"></i> Vitals I captured
                    </div>
                </div>
            </div>
            @endif
        @endif
        
        <!-- Pharmacist-specific stats -->
        @if($userRole === 'Pharmacist')
            @if(isset($statistics['pending_prescriptions']))
            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="bi bi-capsule"></i>
                    </div>
                    <div class="stat-label">Pending Prescriptions</div>
                    <div class="stat-value" data-stat="pending_prescriptions">{{ $statistics['pending_prescriptions'] }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-clock-history"></i> Awaiting dispensing
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['low_stock_drugs']))
            <div class="col-md-3 mb-3">
                <div class="stat-card danger">
                    <div class="stat-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-label">Low Stock Drugs</div>
                    <div class="stat-value">{{ $statistics['low_stock_drugs'] }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-exclamation-triangle"></i> Need restocking
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['pharmacy_queue_waiting']))
            <div class="col-md-3 mb-3">
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-label">Pharmacy Queue Waiting</div>
                    <div class="stat-value" data-stat="pharmacy_queue_waiting">{{ number_format($statistics['pharmacy_queue_waiting']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-clock"></i> Patients waiting
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['pharmacy_queue_serving']))
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="stat-label">Currently Serving</div>
                    <div class="stat-value" data-stat="pharmacy_queue_serving">{{ number_format($statistics['pharmacy_queue_serving']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-check-circle"></i> Being served
                    </div>
                </div>
            </div>
            @endif
        @endif
        
        <!-- Lab Technician-specific stats -->
        @if($userRole === 'Lab Technician')
            @if(isset($statistics['pending_tests']))
            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="bi bi-flask"></i>
                    </div>
                    <div class="stat-label">Pending Tests</div>
                    <div class="stat-value" data-stat="pending_tests">{{ number_format($statistics['pending_tests'] ?? 0) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-clock-history"></i> Awaiting processing
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['in_progress']))
            <div class="col-md-3 mb-3">
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="bi bi-gear"></i>
                    </div>
                    <div class="stat-label">Tests In Progress</div>
                    <div class="stat-value" data-stat="in_progress">{{ number_format($statistics['in_progress']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-hourglass-split"></i> Currently processing
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['completed_today']))
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-label">Completed Today</div>
                    <div class="stat-value" data-stat="completed_today">{{ number_format($statistics['completed_today']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-calendar-check"></i> Tests completed today
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['total_tests']))
            <div class="col-md-3 mb-3">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                    <div class="stat-label">Total Tests</div>
                    <div class="stat-value" data-stat="total_tests">{{ number_format($statistics['total_tests']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-graph-up"></i> All lab requests
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['lab_queue_waiting']))
            <div class="col-md-3 mb-3">
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-label">Lab Queue Waiting</div>
                    <div class="stat-value" data-stat="lab_queue_waiting">{{ number_format($statistics['lab_queue_waiting']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-clock"></i> Patients waiting
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['lab_queue_serving']))
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="stat-label">Currently Serving</div>
                    <div class="stat-value" data-stat="lab_queue_serving">{{ number_format($statistics['lab_queue_serving']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-check-circle"></i> Being served
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['completed_this_week']))
            <div class="col-md-3 mb-3">
                <div class="stat-card secondary">
                    <div class="stat-icon">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <div class="stat-label">Completed This Week</div>
                    <div class="stat-value" data-stat="completed_this_week">{{ number_format($statistics['completed_this_week']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-bar-chart"></i> Weekly progress
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['completed_this_month']))
            <div class="col-md-3 mb-3">
                <div class="stat-card dark">
                    <div class="stat-icon">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                    <div class="stat-label">Completed This Month</div>
                    <div class="stat-value" data-stat="completed_this_month">{{ number_format($statistics['completed_this_month']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-graph-up-arrow"></i> Monthly performance
                    </div>
                </div>
            </div>
            @endif
        @endif
        
        <!-- Radiologist-specific stats -->
        @if($userRole === 'Radiologist')
            @if(isset($statistics['pending_studies']))
            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-label">Pending Studies</div>
                    <div class="stat-value" data-stat="pending_studies">{{ number_format($statistics['pending_studies']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-clock-history"></i> Awaiting review
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['awaiting_reports']))
            <div class="col-md-3 mb-3">
                <div class="stat-card danger">
                    <div class="stat-icon">
                        <i class="bi bi-file-earmark-medical"></i>
                    </div>
                    <div class="stat-label">Awaiting Reports</div>
                    <div class="stat-value" data-stat="awaiting_reports">{{ number_format($statistics['awaiting_reports']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-exclamation-circle"></i> Need reports
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['drafts_to_sign']))
            <div class="col-md-3 mb-3">
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="bi bi-pen"></i>
                    </div>
                    <div class="stat-label">Drafts to Sign</div>
                    <div class="stat-value" data-stat="drafts_to_sign">{{ number_format($statistics['drafts_to_sign']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-pencil-square"></i> Pending signature
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['completed_today']))
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-label">Completed Today</div>
                    <div class="stat-value" data-stat="completed_today">{{ number_format($statistics['completed_today']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-calendar-check"></i> Reports signed
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['total_studies']))
            <div class="col-md-3 mb-3">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                    <div class="stat-label">Total Studies</div>
                    <div class="stat-value" data-stat="total_studies">{{ number_format($statistics['total_studies']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-graph-up"></i> All studies
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['critical_findings']))
            <div class="col-md-3 mb-3">
                <div class="stat-card dark">
                    <div class="stat-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-label">Critical Findings</div>
                    <div class="stat-value" data-stat="critical_findings">{{ number_format($statistics['critical_findings']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-shield-exclamation"></i> Last 7 days
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['radiology_queue_waiting']))
            <div class="col-md-3 mb-3">
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-label">Radiology Queue Waiting</div>
                    <div class="stat-value" data-stat="radiology_queue_waiting">{{ number_format($statistics['radiology_queue_waiting']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-clock"></i> Patients waiting
                    </div>
                </div>
            </div>
            @endif
            
            @if(isset($statistics['radiology_queue_serving']))
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="stat-label">Currently Serving</div>
                    <div class="stat-value" data-stat="radiology_queue_serving">{{ number_format($statistics['radiology_queue_serving']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-check-circle"></i> Being served
                    </div>
                </div>
            </div>
            @endif
        @endif

        @if(isset($statistics['my_pending_expenses']) && auth()->user()->can('view_own_expenses'))
        <div class="col-md-3 mb-3">
            <a href="{{ route('expenses.my', ['status' => 'pending']) }}" class="text-decoration-none">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
                    <div class="stat-label">My Pending Expenses</div>
                    <div class="stat-value">{{ $statistics['my_pending_expenses'] }}</div>
                    <div class="small opacity-75">Awaiting accountant approval</div>
                </div>
            </a>
        </div>
        @endif

        @if(isset($statistics['pending_expense_approvals']) && auth()->user()->canany(['approve_expenses', 'manage_expenses']))
        <div class="col-md-3 mb-3">
            <a href="{{ route('accounting.expenses.index', ['status' => 'pending']) }}" class="text-decoration-none">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-label">Expenses to Approve</div>
                    <div class="stat-value">{{ $statistics['pending_expense_approvals'] }}</div>
                    <div class="small opacity-75">Department submissions</div>
                </div>
            </a>
        </div>
        @endif
    </div>
    
    <!-- Today's Quick Stats - Only show for Admin/Super Admin -->
    @if($userRole === 'Admin' || $userRole === 'super_admin')
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-hospital"></i>
                </div>
                <div class="stat-label">OPD Visits Today</div>
                <div class="stat-value" data-quick-stat="today_opd">{{ $quickStats['today_opd'] ?? 0 }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div class="stat-label">IPD Admissions Today</div>
                <div class="stat-value" data-quick-stat="today_ipd">{{ $quickStats['today_ipd'] ?? 0 }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <div class="stat-label">Lab Tests Today</div>
                <div class="stat-value" data-quick-stat="today_lab">{{ $quickStats['today_lab'] ?? 0 }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-capsule"></i>
                </div>
                <div class="stat-label">Prescriptions Today</div>
                <div class="stat-value" data-quick-stat="today_pharmacy">{{ $quickStats['today_pharmacy'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="row">
        <!-- Appointments Chart - Only for Admin/Super Admin -->
        @if($userRole === 'Admin' || $userRole === 'super_admin')
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark">Appointments (Last 7 Days)</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active">Week</button>
                            <button class="btn btn-outline-primary">Month</button>
                            <button class="btn btn-outline-primary">Year</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="appointmentsChart" height="80"></canvas>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Recent Activities - Role-specific -->
        <div class="col-md-{{ $userRole === 'Admin' || $userRole === 'super_admin' ? '4' : '12' }} mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">
                        @if($userRole === 'Doctor')
                            My Recent Consultations
                        @elseif($userRole === 'Nurse')
                            My Assigned Visits
                        @elseif($userRole === 'Pharmacist')
                            Pharmacy Queue & Recent Prescriptions
                        @elseif($userRole === 'Lab Technician' || auth()->user()->can('process_lab_requests') || auth()->user()->can('enter_lab_results') || auth()->user()->can('view_lab_requests'))
                            Lab Queue & Recent Lab Requests
                        @elseif($userRole === 'Radiologist')
                            Radiology Queue & Recent Studies
                        @elseif($userRole === 'Accountant')
                            Recent Invoices, Debtors & Expenses
                        @else
                            Recent Appointments
                        @endif
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @if($userRole === 'Doctor' && (isset($recentActivities['consultations']) || isset($recentActivities['assigned_visits'])))
                            @php
                                $allPatients = collect();
                                
                                // Process consultations - each consultation has unique patient (already deduplicated in controller)
                                if(isset($recentActivities['consultations']) && $recentActivities['consultations']->count() > 0) {
                                    $allPatients = $allPatients->merge($recentActivities['consultations']->map(function($consultation) {
                                        return (object) [
                                            'type' => 'consultation',
                                            'id' => $consultation->id,
                                            'patient' => $consultation->patient,
                                            'patient_id' => $consultation->patient_id,
                                            'date' => $consultation->consultation_date,
                                            'status' => $consultation->consultation_status,
                                            'is_draft' => $consultation->is_draft ?? false,
                                            'icon' => 'bi-clipboard2-pulse',
                                            'badge_class' => $consultation->consultation_status === 'completed' ? 'success' : ($consultation->consultation_status === 'ongoing' && !($consultation->is_draft ?? false) ? 'info' : 'warning'),
                                            'url' => route('consultations.edit', $consultation->id)
                                        ];
                                    }));
                                }
                                
                                // Process assigned visits - only include if patient doesn't already have a consultation
                                if(isset($recentActivities['assigned_visits']) && $recentActivities['assigned_visits']->count() > 0) {
                                    $existingPatientIds = $allPatients->pluck('patient_id')->toArray();
                                    $allPatients = $allPatients->merge($recentActivities['assigned_visits']->filter(function($visit) use ($existingPatientIds) {
                                        return !in_array($visit->patient_id, $existingPatientIds);
                                    })->map(function($visit) {
                                        return (object) [
                                            'type' => 'visit',
                                            'id' => $visit->id,
                                            'patient' => $visit->patient,
                                            'patient_id' => $visit->patient_id,
                                            'date' => $visit->check_in_time,
                                            'status' => $visit->status,
                                            'visit_type' => $visit->visit_type,
                                            'icon' => 'bi-hospital',
                                            'badge_class' => $visit->status === 'active' ? 'success' : 'secondary',
                                            'url' => route('consultations.create-for-patient', $visit->patient_id)
                                        ];
                                    }));
                                }
                                
                                // Sort by date (most recent first) and take top 10
                                $allPatients = $allPatients->sortByDesc(function($item) {
                                    return $item->date instanceof \Carbon\Carbon ? $item->date->timestamp : strtotime($item->date);
                                })->take(10)->values();
                            @endphp
                            
                            @forelse($allPatients as $item)
                                <a href="{{ $item->url ?? '#' }}" class="list-group-item bg-transparent text-decoration-none consultation-item" style="cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="user-avatar bg-primary">
                                                {{ $item->patient ? substr($item->patient->first_name, 0, 1) : '?' }}
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0 text-dark">{{ $item->patient ? $item->patient->first_name . ' ' . $item->patient->last_name : 'Patient Not Found' }}</h6>
                                            <small class="text-secondary">
                                                <i class="{{ $item->icon }}"></i> 
                                                @if($item->type === 'consultation')
                                                    @if($item->is_draft)
                                                        Draft Consultation - {{ \Carbon\Carbon::parse($item->date)->format('M d, Y') }}
                                                    @else
                                                        Consultation - {{ \Carbon\Carbon::parse($item->date)->format('M d, Y') }}
                                                    @endif
                                                @else
                                                    {{ $item->visit_type }} - {{ \Carbon\Carbon::parse($item->date)->format('M d, Y H:i') }}
                                                @endif
                                            </small>
                                        </div>
                                        <span class="badge bg-{{ $item->badge_class }}">
                                            @if($item->type === 'consultation')
                                                @if($item->is_draft)
                                                    Draft
                                                @else
                                                    {{ ucfirst($item->status) }}
                                                @endif
                                            @else
                                                {{ ucfirst($item->status) }}
                                            @endif
                                        </span>
                                    </div>
                                </a>
                            @empty
                                <div class="list-group-item text-center py-4 bg-transparent">
                                    <i class="bi bi-clipboard2-pulse text-secondary" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p class="text-secondary mb-0 mt-2">No recent consultations or assigned patients</p>
                                </div>
                            @endforelse
                        @elseif($userRole === 'Nurse' && isset($recentActivities['visits']))
                            @forelse($recentActivities['visits'] as $visit)
                                <div class="list-group-item bg-transparent">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="user-avatar bg-primary">
                                                {{ $visit->patient ? substr($visit->patient->first_name, 0, 1) : '?' }}
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0 text-dark">{{ $visit->patient ? $visit->patient->first_name . ' ' . $visit->patient->last_name : 'Patient Not Found' }}</h6>
                                            <small class="text-secondary">
                                                <i class="bi bi-hospital"></i> {{ $visit->visit_type }}
                                            </small>
                                        </div>
                                        <span class="badge bg-{{ $visit->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($visit->status) }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-center py-4 bg-transparent">
                                    <i class="bi bi-person-check text-secondary" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p class="text-secondary mb-0 mt-2">No assigned visits</p>
                                </div>
                            @endforelse
                        @elseif($userRole === 'Pharmacist')
                            @if(isset($recentActivities['pharmacy_queues']) && $recentActivities['pharmacy_queues']->isNotEmpty())
                                @foreach($recentActivities['pharmacy_queues'] as $queue)
                                    <div class="list-group-item bg-transparent">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="user-avatar bg-info">
                                                    {{ $queue->patient ? substr($queue->patient->first_name, 0, 1) : '?' }}
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0 text-dark">{{ $queue->patient ? $queue->patient->first_name . ' ' . $queue->patient->last_name : 'Patient Not Found' }}</h6>
                                                <small class="text-secondary">
                                                    <i class="bi bi-person-lines-fill"></i> 
                                                    {{ $queue->patient ? $queue->patient->patient_number : 'N/A' }}
                                                    @if($queue->visit)
                                                        | Visit: {{ $queue->visit->visit_token }}
                                                    @endif
                                                </small>
                                            </div>
                                            <span class="badge bg-{{ $queue->status === 'waiting' ? 'warning' : ($queue->status === 'serving' ? 'success' : 'secondary') }}">
                                                {{ ucfirst($queue->status) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                            
                            @if(isset($recentActivities['prescriptions']))
                            @forelse($recentActivities['prescriptions'] as $prescription)
                                <div class="list-group-item bg-transparent">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="user-avatar bg-primary">
                                                {{ substr($prescription->patient->first_name, 0, 1) }}
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0 text-dark">{{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}</h6>
                                            <small class="text-secondary">
                                                <i class="bi bi-capsule"></i> {{ $prescription->created_at->format('M d, Y') }}
                                            </small>
                                        </div>
                                        <span class="badge bg-{{ $prescription->status === 'dispensed' ? 'success' : ($prescription->status === 'pending' ? 'warning' : 'info') }}">
                                            {{ ucfirst($prescription->status) }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-center py-4 bg-transparent">
                                    <i class="bi bi-capsule text-secondary" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p class="text-secondary mb-0 mt-2">No recent prescriptions</p>
                                </div>
                            @endforelse
                            @endif
                        @elseif($userRole === 'Lab Technician' || auth()->user()->can('process_lab_requests') || auth()->user()->can('enter_lab_results') || auth()->user()->can('view_lab_requests'))
                            @if(isset($recentActivities['lab_requests']))
                            @forelse($recentActivities['lab_requests'] as $request)
                                <div class="card mb-3 shadow-sm" style="border-left: 4px solid {{ $request->status === 'completed' ? '#28a745' : ($request->status === 'in_progress' ? '#17a2b8' : '#ffc107') }}; border-radius: 8px;">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-1">
                                                    <h6 class="mb-0 me-2" style="font-weight: 600; color: #1e3a5f;">
                                                        New Patient
                                                    </h6>
                                                    <span class="badge bg-{{ $request->priority === 'stat' ? 'danger' : ($request->priority === 'urgent' ? 'warning' : 'secondary') }}" style="font-size: 0.7rem;">
                                                        @if($request->priority === 'stat')
                                                            STAT
                                                        @elseif($request->priority === 'urgent')
                                                            URGENT
                                                        @else
                                                            ROUTINE
                                                        @endif
                                                    </span>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="bi bi-hash"></i> {{ $request->patient->patient_number ?? 'N/A' }}
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-{{ $request->status === 'completed' ? 'success' : ($request->status === 'in_progress' ? 'info' : 'warning') }} mb-2" style="font-size: 0.75rem; padding: 0.4rem 0.8rem;">
                                                    {{ str_replace('_', ' ', strtoupper($request->status)) }}
                                                </span>
                                                <div class="text-muted" style="font-size: 0.7rem;">
                                                    <i class="bi bi-clock-history"></i> {{ $request->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-2 mb-2" style="font-size: 0.85rem;">
                                            <div class="col-12">
                                                <div class="text-secondary mb-1">
                                                    <i class="bi bi-hash text-primary"></i> 
                                                    <strong>Request:</strong> {{ $request->request_number ?? 'N/A' }}
                                                </div>
                                                <div class="text-secondary mb-1">
                                                    <i class="bi bi-clipboard-pulse text-info"></i> 
                                                    <strong>Test:</strong> {{ $request->template ? $request->template->template_name : ($request->test_type_name ?? $request->test_type ?? 'N/A') }}
                                                </div>
                                                @if($request->clinical_notes)
                                                <div class="text-secondary mb-1">
                                                    <i class="bi bi-journal-medical text-info"></i> 
                                                    <strong>Clinical Notes:</strong> {{ Str::limit($request->clinical_notes, 100) }}
                                                </div>
                                                @endif
                                                <div class="text-secondary mb-1">
                                                    <i class="bi bi-calendar3 text-warning"></i> 
                                                    <strong>Requested:</strong> {{ $request->created_at->format('M d, Y h:i A') }}
                                                </div>
                                                <div class="text-secondary mb-1">
                                                    <i class="bi bi-person-badge text-success"></i> 
                                                    <strong>Requested by:</strong> 
                                                    @if($request->doctor)
                                                        Dr. {{ $request->doctor->first_name }} {{ $request->doctor->last_name }}
                                                    @elseif($request->creator)
                                                        {{ $request->creator->first_name }} {{ $request->creator->last_name }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </div>
                                                @if($request->branch)
                                                <div class="text-secondary">
                                                    <i class="bi bi-building text-warning"></i> 
                                                    <strong>Branch:</strong> {{ $request->branch->name }}
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-end mt-3 pt-2 border-top">
                                            <a href="{{ route('lab.show', $request) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-center py-4 bg-transparent">
                                    <i class="bi bi-flask text-secondary" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p class="text-secondary mb-0 mt-2">No recent lab requests</p>
                                </div>
                            @endforelse
                            @else
                                <div class="list-group-item text-center py-4 bg-transparent">
                                    <i class="bi bi-flask text-secondary" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p class="text-secondary mb-0 mt-2">No recent lab requests</p>
                                </div>
                            @endif
                        @elseif($userRole === 'Radiologist')
                            @if(isset($recentActivities['radiology_queues']) && $recentActivities['radiology_queues']->isNotEmpty())
                                @foreach($recentActivities['radiology_queues'] as $queue)
                                    <div class="list-group-item bg-transparent">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="user-avatar bg-info">
                                                    {{ $queue->patient ? substr($queue->patient->first_name, 0, 1) : '?' }}
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0 text-dark">{{ $queue->patient ? $queue->patient->first_name . ' ' . $queue->patient->last_name : 'Patient Not Found' }}</h6>
                                                <small class="text-secondary">
                                                    <i class="bi bi-person-lines-fill"></i> 
                                                    {{ $queue->patient ? $queue->patient->patient_number : 'N/A' }}
                                                    @if($queue->visit)
                                                        | Visit: {{ $queue->visit->visit_token }}
                                                    @endif
                                                </small>
                                            </div>
                                            <span class="badge bg-{{ $queue->status === 'waiting' ? 'warning' : ($queue->status === 'serving' ? 'success' : 'secondary') }}">
                                                {{ ucfirst($queue->status) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                            
                            @if(isset($recentActivities['studies']))
                            @forelse($recentActivities['studies'] as $study)
                                <div class="list-group-item bg-transparent">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="user-avatar bg-primary">
                                                {{ $study->patient ? substr($study->patient->first_name, 0, 1) : '?' }}
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0 text-dark">{{ $study->patient ? $study->patient->first_name . ' ' . $study->patient->last_name : 'Patient Not Found' }}</h6>
                                            <small class="text-secondary">
                                                <i class="bi bi-camera-reels"></i> 
                                                {{ $study->modality ? $study->modality->name : 'N/A' }}
                                                | {{ $study->created_at->format('M d, Y') }}
                                            </small>
                                        </div>
                                        <span class="badge bg-{{ $study->status === 'completed' ? 'success' : ($study->status === 'in_progress' ? 'info' : 'warning') }}">
                                            {{ ucfirst($study->status) }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-center py-4 bg-transparent">
                                    <i class="bi bi-camera-reels text-secondary" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p class="text-secondary mb-0 mt-2">No recent studies</p>
                                </div>
                            @endforelse
                            @endif
                        @elseif($userRole === 'Accountant')
                            @php
                                $invoiceStatusColors = [
                                    'pending' => 'warning',
                                    'paid' => 'success',
                                    'partial' => 'info',
                                    'overdue' => 'danger',
                                    'cancelled' => 'secondary',
                                ];
                                $debtStatusColors = [
                                    'current' => 'info',
                                    'overdue' => 'warning',
                                    'critical' => 'danger',
                                    'resolved' => 'success',
                                ];
                                $hasAccountantActivity = (isset($recentActivities['invoices']) && $recentActivities['invoices']->isNotEmpty())
                                    || (isset($recentActivities['debtors']) && $recentActivities['debtors']->isNotEmpty())
                                    || (isset($recentActivities['pending_expenses']) && $recentActivities['pending_expenses']->isNotEmpty());
                            @endphp

                            @if(isset($recentActivities['invoices']) && $recentActivities['invoices']->isNotEmpty())
                                <div class="list-group-item bg-light border-0 py-2">
                                    <small class="text-uppercase text-secondary fw-semibold">
                                        <i class="bi bi-receipt"></i> Recent Invoices
                                    </small>
                                </div>
                                @foreach($recentActivities['invoices'] as $invoice)
                                    <a href="{{ route('billing.show', $invoice) }}" class="list-group-item bg-transparent text-decoration-none" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="user-avatar bg-primary">
                                                    {{ $invoice->patient ? substr($invoice->patient->first_name, 0, 1) : '?' }}
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0 text-dark">
                                                    {{ $invoice->patient ? $invoice->patient->first_name . ' ' . $invoice->patient->last_name : 'Patient Not Found' }}
                                                </h6>
                                                <small class="text-secondary">
                                                    <i class="bi bi-hash"></i> {{ $invoice->invoice_number }}
                                                    · GH₵{{ number_format($invoice->total_amount ?? 0, 2) }}
                                                    · {{ $invoice->invoice_date ? $invoice->invoice_date->format('M d, Y') : $invoice->created_at->format('M d, Y') }}
                                                </small>
                                            </div>
                                            <span class="badge bg-{{ $invoiceStatusColors[$invoice->status] ?? 'secondary' }}">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            @endif

                            @if(isset($recentActivities['debtors']) && $recentActivities['debtors']->isNotEmpty())
                                <div class="list-group-item bg-light border-0 py-2">
                                    <small class="text-uppercase text-secondary fw-semibold">
                                        <i class="bi bi-exclamation-circle"></i> Top Outstanding Debtors
                                    </small>
                                </div>
                                @foreach($recentActivities['debtors'] as $debtor)
                                    <a href="{{ route('debtors.show', $debtor) }}" class="list-group-item bg-transparent text-decoration-none" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="user-avatar bg-warning">
                                                    {{ $debtor->patient ? substr($debtor->patient->first_name, 0, 1) : '?' }}
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0 text-dark">
                                                    {{ $debtor->patient ? $debtor->patient->first_name . ' ' . $debtor->patient->last_name : 'Patient Not Found' }}
                                                </h6>
                                                <small class="text-secondary">
                                                    <i class="bi bi-currency-dollar"></i> Outstanding: GH₵{{ number_format($debtor->total_outstanding, 2) }}
                                                    @if($debtor->days_overdue > 0)
                                                        · {{ $debtor->days_overdue }} days overdue
                                                    @endif
                                                </small>
                                            </div>
                                            <span class="badge bg-{{ $debtStatusColors[$debtor->debt_status] ?? 'secondary' }}">
                                                {{ ucfirst(str_replace('_', ' ', $debtor->debt_status ?? 'outstanding')) }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            @endif

                            @if(isset($recentActivities['pending_expenses']) && $recentActivities['pending_expenses']->isNotEmpty())
                                <div class="list-group-item bg-light border-0 py-2">
                                    <small class="text-uppercase text-secondary fw-semibold">
                                        <i class="bi bi-hourglass-split"></i> Pending Expense Approvals
                                    </small>
                                </div>
                                @foreach($recentActivities['pending_expenses'] as $expense)
                                    <a href="{{ route('accounting.expenses.show', $expense) }}" class="list-group-item bg-transparent text-decoration-none" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="user-avatar bg-info">
                                                    <i class="bi bi-wallet2"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0 text-dark">{{ $expense->expense_reference ?? 'Expense #' . $expense->id }}</h6>
                                                <small class="text-secondary">
                                                    <i class="bi bi-building"></i> {{ \App\Models\Expense::DEPARTMENTS[$expense->department] ?? ucfirst($expense->department ?? 'General') }}
                                                    · GH₵{{ number_format($expense->amount, 2) }}
                                                    @if($expense->creator)
                                                        · {{ $expense->creator->first_name }} {{ $expense->creator->last_name }}
                                                    @endif
                                                </small>
                                            </div>
                                            <span class="badge bg-warning">Pending</span>
                                        </div>
                                    </a>
                                @endforeach
                            @endif

                            @unless($hasAccountantActivity)
                                <div class="list-group-item text-center py-4 bg-transparent">
                                    <i class="bi bi-calculator text-secondary" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p class="text-secondary mb-0 mt-2">No recent financial activity</p>
                                </div>
                            @endunless
                        @elseif(isset($recentActivities['appointments']))
                            @forelse($recentActivities['appointments'] as $appointment)
                                @if($appointment->patient)
                                <div class="list-group-item bg-transparent">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="user-avatar bg-primary">
                                                {{ substr($appointment->patient->first_name, 0, 1) }}
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0 text-dark">{{ $appointment->patient->first_name }} {{ $appointment->patient->last_name }}</h6>
                                            <small class="text-secondary">
                                                <i class="bi bi-clock"></i> {{ $appointment->appointment_date }} {{ $appointment->appointment_time }}
                                            </small>
                                        </div>
                                        <span class="badge bg-{{ $appointment->status === 'scheduled' ? 'info' : ($appointment->status === 'completed' ? 'success' : 'danger') }}">
                                            {{ ucfirst($appointment->status) }}
                                        </span>
                                    </div>
                                </div>
                                @endif
                            @empty
                                <div class="list-group-item text-center py-4 bg-transparent">
                                    <i class="bi bi-calendar-x text-secondary" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p class="text-secondary mb-0 mt-2">No recent appointments</p>
                                </div>
                            @endforelse
                        @else
                            <div class="list-group-item text-center py-4 bg-transparent">
                                <i class="bi bi-info-circle text-secondary" style="font-size: 2rem; opacity: 0.5;"></i>
                                <p class="text-secondary mb-0 mt-2">No recent activities</p>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer text-center" style="background: #f8f9fa;">
                    @if($userRole === 'Doctor')
                        <a href="{{ route('consultations.index') }}" class="text-decoration-none" style="color: var(--accent);">
                            View All Consultations <i class="bi bi-arrow-right"></i>
                        </a>
                    @elseif($userRole === 'Nurse')
                        <a href="{{ route('visits.index') }}" class="text-decoration-none" style="color: var(--accent);">
                            View All Visits <i class="bi bi-arrow-right"></i>
                        </a>
                    @elseif($userRole === 'Pharmacist')
                        <a href="{{ route('pharmacy.index') }}" class="text-decoration-none" style="color: var(--accent);">
                            View All Prescriptions <i class="bi bi-arrow-right"></i>
                        </a>
                    @elseif($userRole === 'Lab Technician' || auth()->user()->can('process_lab_requests') || auth()->user()->can('enter_lab_results') || auth()->user()->can('view_lab_requests'))
                        <a href="{{ route('lab.index') }}" class="text-decoration-none" style="color: var(--accent);">
                            View All Lab Requests <i class="bi bi-arrow-right"></i>
                        </a>
                    @elseif($userRole === 'Radiologist')
                        <a href="{{ route('radiology.index') }}" class="text-decoration-none" style="color: var(--accent);">
                            View All Radiology Requests <i class="bi bi-arrow-right"></i>
                        </a>
                    @elseif($userRole === 'Accountant')
                        <a href="{{ route('accounting.index') }}" class="text-decoration-none" style="color: var(--accent);">
                            Open Accounting Hub <i class="bi bi-arrow-right"></i>
                        </a>
                    @else
                        <a href="{{ route('appointments.index') }}" class="text-decoration-none" style="color: var(--accent);">
                            View All Appointments <i class="bi bi-arrow-right"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Real-time Notifications -->
    @if($userRole === 'Admin' || $userRole === 'super_admin')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-bell"></i> Real-time Notifications
                    </h5>
                    <span id="notification-count" class="badge bg-primary">0</span>
                </div>
                <div class="card-body p-0">
                    <div id="notifications-container" class="list-group list-group-flush">
                        <!-- Notifications will be loaded here via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Quick Actions -->
    {{-- 
    TODO: Uncomment when module routes are created
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @can('create_patients')
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <a href="{{ route('patients.create') }}" class="text-decoration-none">
                                <div class="text-center p-3 border rounded hover-effect">
                                    <i class="bi bi-person-plus text-primary" style="font-size: 2rem;"></i>
                                    <p class="mb-0 mt-2 small">Add Patient</p>
                                </div>
                            </a>
                        </div>
                        @endcan
                        
                        @can('create_appointments')
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <a href="{{ route('appointments.create') }}" class="text-decoration-none">
                                <div class="text-center p-3 border rounded hover-effect">
                                    <i class="bi bi-calendar-plus text-info" style="font-size: 2rem;"></i>
                                    <p class="mb-0 mt-2 small">New Appointment</p>
                                </div>
                            </a>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
    --}}
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Global variables for real-time updates
let appointmentsChart = null;
let refreshInterval = null;
let isPageVisible = true;
let lastUpdateTime = null;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    startRealTimeUpdates();
    setupPageVisibilityHandling();
});

// Initialize dashboard components
function initializeDashboard() {
    initializeChart();
    setupHoverEffects();
    updateLastRefreshTime();
}

// Initialize appointments chart
function initializeChart() {
const ctx = document.getElementById('appointmentsChart');
if (ctx) {
        appointmentsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartData['labels'] ?? []),
            datasets: [{
                label: 'Appointments',
                data: @json($chartData['appointments'] ?? []),
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
                animation: {
                    duration: 750,
                    easing: 'easeInOutQuart'
                },
            plugins: {
                legend: {
                    labels: {
                        color: '#333'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#6b7280'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#6b7280'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        }
    });
    }
}

// Start real-time updates every 10 seconds
function startRealTimeUpdates() {
    refreshInterval = setInterval(function() {
        if (isPageVisible) {
            updateDashboardData();
        }
    }, 10000); // 10 seconds
}

// Update dashboard data via AJAX
async function updateDashboardData() {
    try {
        showLoadingIndicator();
        
        const response = await fetch('{{ route("dashboard.realtime") }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            updateDashboardUI(data.data);
            updateLastRefreshTime();
            showSuccessIndicator();
        } else {
            throw new Error(data.error || 'Failed to fetch data');
        }
    } catch (error) {
        console.error('Error updating dashboard:', error);
        showErrorIndicator();
    } finally {
        hideLoadingIndicator();
    }
}

// Update dashboard UI with new data
function updateDashboardUI(data) {
    // Update statistics cards
    updateStatisticsCards(data.statistics);
    
    // Update quick stats
    if (data.quick_stats) {
        updateQuickStats(data.quick_stats);
    }
    
    // Update recent activities
    updateRecentActivities(data.recent_activities);
    
    // Update chart data
    if (data.chart_data && appointmentsChart) {
        updateChartData(data.chart_data);
    }
    
    // Update queue status
    if (data.queue_status) {
        updateQueueStatus(data.queue_status);
    }
    
    // Update notifications
    if (data.notifications) {
        updateNotifications(data.notifications);
    }
}

// Update statistics cards with smooth animation
function updateStatisticsCards(statistics) {
    Object.keys(statistics).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            const newValue = statistics[key];
            const currentValue = parseInt(element.textContent.replace(/,/g, '')) || 0;
            
            if (newValue !== currentValue) {
                animateValueChange(element, currentValue, newValue);
            }
        }
    });
}

// Update quick stats
function updateQuickStats(quickStats) {
    Object.keys(quickStats).forEach(key => {
        const element = document.querySelector(`[data-quick-stat="${key}"]`);
        if (element) {
            const newValue = quickStats[key];
            const currentValue = parseInt(element.textContent) || 0;
            
            if (newValue !== currentValue) {
                animateValueChange(element, currentValue, newValue);
            }
        }
    });
}

// Update recent activities
function updateRecentActivities(activities) {
    // This would require more complex DOM manipulation
    // For now, we'll just refresh the entire activities section
    if (activities.appointments || activities.visits || activities.consultations || activities.prescriptions || activities.lab_requests) {
        // Reload the page section or update specific elements
        location.reload(); // Simple approach for now
    }
}

// Update chart data
function updateChartData(chartData) {
    if (appointmentsChart && chartData.labels && chartData.appointments) {
        appointmentsChart.data.labels = chartData.labels;
        appointmentsChart.data.datasets[0].data = chartData.appointments;
        appointmentsChart.update('active');
    }
}

// Update queue status
function updateQueueStatus(queueStatus) {
    Object.keys(queueStatus).forEach(key => {
        const element = document.querySelector(`[data-queue="${key}"]`);
        if (element) {
            const newValue = queueStatus[key];
            const currentValue = parseInt(element.textContent) || 0;
            
            if (newValue !== currentValue) {
                animateValueChange(element, currentValue, newValue);
            }
        }
    });
}

// Update notifications
function updateNotifications(notifications) {
    const container = document.getElementById('notifications-container');
    const countElement = document.getElementById('notification-count');
    
    if (!container || !countElement) return;
    
    // Update notification count
    countElement.textContent = notifications.length;
    
    // Clear existing notifications
    container.innerHTML = '';
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="list-group-item text-center py-4 bg-transparent">
                <i class="bi bi-check-circle text-success" style="font-size: 2rem; opacity: 0.5;"></i>
                <p class="text-secondary mb-0 mt-2">No notifications</p>
            </div>
        `;
        return;
    }
    
    // Add notifications
    notifications.forEach(notification => {
        const notificationElement = createNotificationElement(notification);
        container.appendChild(notificationElement);
    });
}

// Create notification element
function createNotificationElement(notification) {
    const div = document.createElement('div');
    div.className = 'list-group-item bg-transparent notification-item';
    
    const priorityClass = getPriorityClass(notification.priority);
    const typeClass = getTypeClass(notification.type);
    
    div.innerHTML = `
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0">
                <div class="notification-icon ${typeClass}">
                    <i class="${notification.icon}"></i>
                </div>
            </div>
            <div class="flex-grow-1 ms-3">
                <h6 class="mb-1 text-dark">${notification.title}</h6>
                <p class="mb-1 text-secondary">${notification.message}</p>
                <small class="text-muted">
                    <i class="bi bi-clock"></i> ${formatTimestamp(notification.timestamp)}
                </small>
            </div>
            <div class="flex-shrink-0">
                <span class="badge ${priorityClass}">${notification.priority}</span>
            </div>
        </div>
    `;
    
    return div;
}

// Get priority class for badge
function getPriorityClass(priority) {
    switch (priority) {
        case 'high': return 'bg-danger';
        case 'medium': return 'bg-warning';
        case 'low': return 'bg-info';
        default: return 'bg-secondary';
    }
}

// Get type class for icon
function getTypeClass(type) {
    switch (type) {
        case 'danger': return 'text-danger';
        case 'warning': return 'text-warning';
        case 'success': return 'text-success';
        case 'info': return 'text-info';
        default: return 'text-secondary';
    }
}

// Format timestamp
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));
    
    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
    return date.toLocaleDateString();
}

// Animate value changes
function animateValueChange(element, start, end) {
    const duration = 1000; // 1 second
    const startTime = performance.now();
    
    function updateValue(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.round(start + (end - start) * progress);
        element.textContent = current.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(updateValue);
        }
    }
    
    requestAnimationFrame(updateValue);
}

// Show loading indicator
function showLoadingIndicator() {
    const indicator = document.getElementById('refresh-indicator');
    if (indicator) {
        indicator.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Updating...';
        indicator.classList.remove('d-none');
    }
}

// Hide loading indicator
function hideLoadingIndicator() {
    const indicator = document.getElementById('refresh-indicator');
    if (indicator) {
        indicator.classList.add('d-none');
    }
}

// Show success indicator
function showSuccessIndicator() {
    const indicator = document.getElementById('refresh-indicator');
    if (indicator) {
        indicator.innerHTML = '<i class="bi bi-check-circle text-success"></i> Updated';
        indicator.classList.remove('d-none');
        setTimeout(() => {
            indicator.classList.add('d-none');
        }, 2000);
    }
}

// Show error indicator
function showErrorIndicator() {
    const indicator = document.getElementById('refresh-indicator');
    if (indicator) {
        indicator.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> Update failed';
        indicator.classList.remove('d-none');
        setTimeout(() => {
            indicator.classList.add('d-none');
        }, 3000);
    }
}

// Update last refresh time
function updateLastRefreshTime() {
    const timeElement = document.getElementById('last-refresh-time');
    if (timeElement) {
        timeElement.textContent = new Date().toLocaleTimeString();
    }
}

// Setup page visibility handling
function setupPageVisibilityHandling() {
    document.addEventListener('visibilitychange', function() {
        isPageVisible = !document.hidden;
        
        if (isPageVisible) {
            // Page became visible, update immediately
            updateDashboardData();
        }
    });
}

// Setup hover effects
function setupHoverEffects() {
document.querySelectorAll('.hover-effect').forEach(el => {
    el.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.transition = 'all 0.3s ease';
    });
    el.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
    });
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
@endpush

@push('styles')
<style>
.hover-effect:hover {
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    border-color: var(--accent) !important;
}

/* Real-time update animations */
.stat-value {
    transition: all 0.3s ease;
}

.stat-value.updating {
    color: #007bff;
    font-weight: bold;
}

.stat-value.updated {
    color: #28a745;
    animation: pulse 0.5s ease-in-out;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Spinning animation for refresh indicator */
.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Smooth transitions for all dashboard elements */
.stat-card {
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Loading state for dashboard */
.dashboard-loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Real-time indicator styles */
#refresh-indicator {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

#refresh-indicator .spin {
    margin-right: 0.25rem;
}

/* Chart container with smooth updates */
#appointmentsChart {
    transition: opacity 0.3s ease;
}

.chart-updating {
    opacity: 0.7;
}

/* Notification styles */
.notification-item {
    border-left: 4px solid transparent;
    transition: all 0.3s ease;
}

.notification-item:hover {
    background-color: #f8f9fa !important;
    border-left-color: #007bff;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    background-color: #f8f9fa;
}

.notification-item .badge {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Notification animations */
.notification-item {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Notification count badge */
#notification-count {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
</style>
@endpush
@endsection
