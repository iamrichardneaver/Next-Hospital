@extends('layouts.app')

@section('title', 'Customer Complaints')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <!-- Toolbar -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Customer Complaints Management
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Complaints</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @can('create_complaints')
                <a href="{{ route('complaints.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle fs-4"></i> File New Complaint
                </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Content -->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Statistics Cards -->
            <div class="row g-3 mb-5">
                <!-- Total Complaints -->
                <div class="col-xl-3">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>
                        <div class="stat-label">Total Complaints</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>

                <!-- Pending Complaints -->
                <div class="col-xl-3">
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stat-label">Pending</div>
                        <div class="stat-value">{{ $statistics['pending'] }}</div>
                    </div>
                </div>

                <!-- Under Review -->
                <div class="col-xl-3">
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="bi bi-eye"></i>
                        </div>
                        <div class="stat-label">Under Review</div>
                        <div class="stat-value">{{ $statistics['under_review'] }}</div>
                    </div>
                </div>

                <!-- Resolved -->
                <div class="col-xl-3">
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-label">Resolved</div>
                        <div class="stat-value">{{ $statistics['resolved'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Complaints Table -->
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <!-- Search -->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="bi bi-search position-absolute ms-4 fs-3"></i>
                            <input type="text" id="search" class="form-control form-control-solid w-250px ps-12" placeholder="Search complaints..." />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <!-- Filter by Status -->
                        <select id="status-filter" class="form-select form-select-solid w-150px me-3">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="under_review">Under Review</option>
                            <option value="investigating">Investigating</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        
                        <!-- Filter by Category -->
                        <select id="category-filter" class="form-select form-select-solid w-150px">
                            <option value="">All Categories</option>
                            <option value="service_quality">Service Quality</option>
                            <option value="staff_behavior">Staff Behavior</option>
                            <option value="wait_time">Wait Time</option>
                            <option value="billing">Billing</option>
                            <option value="cleanliness">Cleanliness</option>
                            <option value="medical_care">Medical Care</option>
                            <option value="facilities">Facilities</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="complaints-table">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-100px">Complaint #</th>
                                    <th class="min-w-150px">Complainant</th>
                                    <th class="min-w-150px">Subject</th>
                                    <th class="min-w-100px">Category</th>
                                    <th class="min-w-80px">Severity</th>
                                    <th class="min-w-100px">Status</th>
                                    <th class="min-w-100px">Priority</th>
                                    <th class="min-w-100px">Date</th>
                                    <th class="text-end min-w-100px">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 fw-semibold">
                                @forelse($complaints as $complaint)
                                <tr>
                                    <td>
                                        <span class="text-gray-800 fw-bold">{{ $complaint->complaint_number }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold">{{ $complaint->complainant_name }}</span>
                                            @if($complaint->patient)
                                            <span class="text-muted fs-7">Patient: {{ $complaint->patient->patient_id }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-gray-800">{{ Str::limit($complaint->subject, 30) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-info">{{ ucfirst(str_replace('_', ' ', $complaint->category)) }}</span>
                                    </td>
                                    <td>
                                        @if($complaint->severity === 'critical')
                                            <span class="badge badge-danger">Critical</span>
                                        @elseif($complaint->severity === 'high')
                                            <span class="badge badge-warning">High</span>
                                        @elseif($complaint->severity === 'medium')
                                            <span class="badge badge-info">Medium</span>
                                        @else
                                            <span class="badge badge-light">Low</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($complaint->status === 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif($complaint->status === 'under_review')
                                            <span class="badge badge-primary">Under Review</span>
                                        @elseif($complaint->status === 'investigating')
                                            <span class="badge badge-info">Investigating</span>
                                        @elseif($complaint->status === 'resolved')
                                            <span class="badge badge-success">Resolved</span>
                                        @elseif($complaint->status === 'closed')
                                            <span class="badge badge-secondary">Closed</span>
                                        @else
                                            <span class="badge badge-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($complaint->priority === 'urgent')
                                            <span class="badge badge-danger">Urgent</span>
                                        @elseif($complaint->priority === 'high')
                                            <span class="badge badge-warning">High</span>
                                        @elseif($complaint->priority === 'normal')
                                            <span class="badge badge-info">Normal</span>
                                        @else
                                            <span class="badge badge-light">Low</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $complaint->created_at->format('d M Y') }}<br>
                                        <span class="text-muted fs-7">{{ $complaint->created_at->format('h:i A') }}</span>
                                    </td>
                                    <td class="text-end position-static">
                                        <div class="dropdown position-static">
                                            <button class="btn btn-light btn-active-light-primary btn-sm dropdown-toggle" type="button" id="dropdownMenu{{ $complaint->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenu{{ $complaint->id }}">
                                                <li><a class="dropdown-item" href="{{ route('complaints.show', $complaint) }}"><i class="bi bi-eye fs-5 me-2"></i>View Details</a></li>
                                                @can('edit_complaints')
                                                <li><a class="dropdown-item" href="{{ route('complaints.edit', $complaint) }}"><i class="bi bi-pencil fs-5 me-2"></i>Edit</a></li>
                                                @endcan
                                                @can('delete_complaints')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('complaints.destroy', $complaint) }}" method="POST" class="d-inline delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash fs-5 me-2"></i>Delete</button>
                                                    </form>
                                                </li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-10">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-inbox fs-3x text-muted mb-3"></i>
                                            <span class="text-muted fs-5">No complaints found</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <div class="text-muted">
                            Showing {{ $complaints->firstItem() ?? 0 }} to {{ $complaints->lastItem() ?? 0 }} of {{ $complaints->total() }} complaints
                        </div>
                        <div>
                            {{ $complaints->links() }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live search
    const searchInput = document.getElementById('search');
    const statusFilter = document.getElementById('status-filter');
    const categoryFilter = document.getElementById('category-filter');
    
    let searchTimeout;
    
    function performSearch() {
        const search = searchInput.value;
        const status = statusFilter.value;
        const category = categoryFilter.value;
        
        const url = new URL(window.location.href);
        url.searchParams.set('search', search);
        url.searchParams.set('status', status);
        url.searchParams.set('category', category);
        
        window.location.href = url.toString();
    }
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 500);
    });
    
    statusFilter.addEventListener('change', performSearch);
    categoryFilter.addEventListener('change', performSearch);
    
    // Delete confirmation
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this complaint? This action cannot be undone.')) {
                this.submit();
            }
        });
    });
});
</script>
@endpush
@endsection

