@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-dollar-sign me-2"></i>Appointment Fees Management</h2>
            <p class="text-muted mb-0">Configure pricing for in-person and teleconsultation appointments</p>
        </div>
        <div>
            <button type="button" class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
                <i class="fas fa-magic me-2"></i>Quick Setup
            </button>
            <a href="{{ route('appointment-fees.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Fee
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Fees</h6>
                            <h3 class="mb-0">{{ $stats['total_fees'] }}</h3>
                        </div>
                        <i class="fas fa-list fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Active Fees</h6>
                            <h3 class="mb-0">{{ $stats['active_fees'] }}</h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Avg In-Person</h6>
                            <h3 class="mb-0">GHS {{ number_format($stats['average_in_person_fee'] ?? 0, 2) }}</h3>
                        </div>
                        <i class="fas fa-hospital fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Avg Teleconsult</h6>
                            <h3 class="mb-0">GHS {{ number_format($stats['average_teleconsultation_fee'] ?? 0, 2) }}</h3>
                        </div>
                        <i class="fas fa-video fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('appointment-fees.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Appointment Type</label>
                    <select name="appointment_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="in-person" {{ request('appointment_type') == 'in-person' ? 'selected' : '' }}>In-Person</option>
                        <option value="teleconsultation" {{ request('appointment_type') == 'teleconsultation' ? 'selected' : '' }}>Teleconsultation</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">All Doctors (Branch-level)</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                Dr. {{ $doctor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                    <a href="{{ route('appointment-fees.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Fees Table -->
    <div class="card">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Doctor</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Base Fee</th>
                            <th>Platform Fee</th>
                            <th>Tax Rate</th>
                            <th>Total Fee</th>
                            <th>Status</th>
                            <th>Effective Period</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fees as $fee)
                            <tr>
                                <td>
                                    <strong>{{ $fee->branch->name ?? 'N/A' }}</strong>
                                </td>
                                <td>
                                    @if($fee->doctor)
                                        <span class="badge bg-info">Dr. {{ $fee->doctor->name }}</span>
                                    @else
                                        <span class="badge bg-secondary">Branch-Level</span>
                                    @endif
                                </td>
                                <td>
                                    @if($fee->appointment_type == 'in-person')
                                        <span class="badge bg-primary">
                                            <i class="fas fa-hospital me-1"></i>In-Person
                                        </span>
                                    @else
                                        <span class="badge bg-success">
                                            <i class="fas fa-video me-1"></i>Teleconsultation
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ ucfirst($fee->fee_category) }}</span>
                                </td>
                                <td>{{ $fee->currency }} {{ number_format($fee->base_fee, 2) }}</td>
                                <td>{{ $fee->currency }} {{ number_format($fee->platform_fee, 2) }}</td>
                                <td>{{ $fee->tax_rate }}%</td>
                                <td>
                                    <strong>{{ $fee->currency }} {{ number_format($fee->calculateTotalFee(), 2) }}</strong>
                                </td>
                                <td>
                                    @if($fee->is_active && $fee->isEffective())
                                        <span class="badge bg-success">Active</span>
                                    @elseif($fee->is_active)
                                        <span class="badge bg-warning">Not Effective Yet</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $fee->effective_from ? $fee->effective_from->format('M d, Y') : 'No start date' }}
                                        -
                                        {{ $fee->effective_until ? $fee->effective_until->format('M d, Y') : 'No end date' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('appointment-fees.edit', $fee) }}" class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('appointment-fees.toggle-status', $fee) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn {{ $fee->is_active ? 'btn-secondary' : 'btn-success' }}" title="{{ $fee->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="fas fa-{{ $fee->is_active ? 'pause' : 'play' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('appointment-fees.destroy', $fee) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this fee?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                    <p class="text-muted">No appointment fees found. Create one to get started!</p>
                                    <a href="{{ route('appointment-fees.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Add First Fee
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($fees->hasPages())
                <div class="mt-3">
                    {{ $fees->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Bulk Create Modal -->
<div class="modal fade" id="bulkCreateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('appointment-fees.bulk-create') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-magic me-2"></i>Quick Setup Default Fees
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">This will create default fee structures for a branch:</p>
                    <ul class="text-muted small">
                        <li>In-Person General: GHS 50.00</li>
                        <li>In-Person Specialist: GHS 100.00</li>
                        <li>Teleconsultation General: GHS 30.00</li>
                        <li>Teleconsultation Specialist: GHS 60.00</li>
                    </ul>

                    <div class="mb-3">
                        <label class="form-label">Select Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">-- Select Branch --</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Currency <span class="text-danger">*</span></label>
                        <select name="currency" class="form-select" required>
                            <option value="GHS">GHS - Ghana Cedis</option>
                            <option value="USD">USD - US Dollars</option>
                            <option value="EUR">EUR - Euros</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-magic me-2"></i>Create Default Fees
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

