@extends('layouts.app')

@section('title', 'Equipment Details - ' . $equipment->name)

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('lab.equipment.index') }}">Equipment</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $equipment->name }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-1" style="color: #1e3a5f;">
                    <i class="bi bi-gear"></i> {{ $equipment->name }}
                </h1>
                <p class="text-secondary mb-0">{{ $equipment->model }} - {{ $equipment->manufacturer }}</p>
            </div>
            <div>
                <a href="{{ route('lab.equipment.edit', $equipment) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="{{ route('lab.equipment.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Status Alert -->
    @if($equipment->status !== 'operational')
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Status:</strong> This equipment is currently 
        <span class="badge bg-warning">{{ ucfirst(str_replace('_', ' ', $equipment->status)) }}</span>
    </div>
    @endif

    <!-- Equipment Information -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- Basic Information Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Name:</dt>
                                <dd class="col-sm-7">{{ $equipment->name }}</dd>

                                <dt class="col-sm-5">Model:</dt>
                                <dd class="col-sm-7">{{ $equipment->model ?? 'N/A' }}</dd>

                                <dt class="col-sm-5">Manufacturer:</dt>
                                <dd class="col-sm-7">{{ $equipment->manufacturer ?? 'N/A' }}</dd>

                                <dt class="col-sm-5">Serial Number:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-secondary">{{ $equipment->serial_number ?? 'N/A' }}</span>
                                </dd>

                                <dt class="col-sm-5">Equipment Type:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-info">{{ ucfirst($equipment->equipment_type ?? 'N/A') }}</span>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Location:</dt>
                                <dd class="col-sm-7">{{ $equipment->location ?? 'N/A' }}</dd>

                                <dt class="col-sm-5">Department:</dt>
                                <dd class="col-sm-7">{{ $equipment->department ?? 'N/A' }}</dd>

                                <dt class="col-sm-5">Status:</dt>
                                <dd class="col-sm-7">
                                    @if($equipment->status === 'operational')
                                        <span class="badge bg-success">Operational</span>
                                    @elseif($equipment->status === 'under_maintenance')
                                        <span class="badge bg-warning">Under Maintenance</span>
                                    @elseif($equipment->status === 'out_of_service')
                                        <span class="badge bg-danger">Out of Service</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($equipment->status) }}</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Active:</dt>
                                <dd class="col-sm-7">
                                    @if($equipment->is_active)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-danger">No</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Technical Specifications -->
            @if($equipment->specifications)
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-cpu"></i> Technical Specifications</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0" style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">{{ json_encode($equipment->specifications, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            @endif

            <!-- Maintenance History -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-tools"></i> Maintenance History</h5>
                </div>
                <div class="card-body">
                    @if($equipment->maintenanceRecords && $equipment->maintenanceRecords->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Performed By</th>
                                        <th>Description</th>
                                        <th>Cost</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($equipment->maintenanceRecords as $record)
                                    <tr>
                                        <td>{{ $record->maintenance_date->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ ucfirst($record->maintenance_type) }}</span>
                                        </td>
                                        <td>{{ $record->performer->name ?? 'N/A' }}</td>
                                        <td>{{ Str::limit($record->description ?? 'N/A', 50) }}</td>
                                        <td>GH₵ {{ number_format($record->cost ?? 0, 2) }}</td>
                                        <td>
                                            @if($record->status === 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @elseif($record->status === 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @elseif($record->status === 'in_progress')
                                                <span class="badge bg-info">In Progress</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($record->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3">No maintenance records found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <!-- Purchase Information Card -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-cart"></i> Purchase Information</h5>
                </div>
                <div class="card-body">
                    <dl>
                        <dt>Purchase Date:</dt>
                        <dd>{{ $equipment->purchase_date ? $equipment->purchase_date->format('M d, Y') : 'N/A' }}</dd>

                        <dt>Purchase Cost:</dt>
                        <dd class="h5 text-success">GH₵ {{ number_format($equipment->purchase_cost ?? 0, 2) }}</dd>

                        <dt>Supplier:</dt>
                        <dd>{{ $equipment->supplier->name ?? 'N/A' }}</dd>

                        <dt>Warranty Expiry:</dt>
                        <dd>
                            @if($equipment->warranty_expiry)
                                {{ $equipment->warranty_expiry->format('M d, Y') }}
                                @if($equipment->isUnderWarranty())
                                    <span class="badge bg-success">Under Warranty</span>
                                @else
                                    <span class="badge bg-danger">Expired</span>
                                @endif
                            @else
                                N/A
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Maintenance Schedule Card -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Maintenance Schedule</h5>
                </div>
                <div class="card-body">
                    <dl>
                        <dt>Installation Date:</dt>
                        <dd>{{ $equipment->installation_date ? $equipment->installation_date->format('M d, Y') : 'N/A' }}</dd>

                        <dt>Last Maintenance:</dt>
                        <dd>{{ $equipment->last_maintenance_date ? $equipment->last_maintenance_date->format('M d, Y') : 'Never' }}</dd>

                        <dt>Next Maintenance:</dt>
                        <dd>
                            @if($equipment->next_maintenance_date)
                                {{ $equipment->next_maintenance_date->format('M d, Y') }}
                                @if($equipment->checkNeedsMaintenance())
                                    <span class="badge bg-danger">Due Soon</span>
                                @endif
                            @else
                                Not Scheduled
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Audit Information Card -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Audit Information</h5>
                </div>
                <div class="card-body">
                    <dl>
                        <dt>Created By:</dt>
                        <dd>{{ $equipment->creator->name ?? 'N/A' }}</dd>

                        <dt>Created At:</dt>
                        <dd>{{ $equipment->created_at->format('M d, Y h:i A') }}</dd>

                        <dt>Last Updated:</dt>
                        <dd>{{ $equipment->updated_at->format('M d, Y h:i A') }}</dd>

                        @if($equipment->updater)
                        <dt>Updated By:</dt>
                        <dd>{{ $equipment->updater->name }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.stat-card .stat-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.stat-card.primary .stat-icon { color: #0d6efd; }
.stat-card.success .stat-icon { color: #198754; }
.stat-card.warning .stat-icon { color: #ffc107; }
.stat-card.danger .stat-icon { color: #dc3545; }

.stat-card .stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.stat-card .stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #1e3a5f;
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    border-bottom: 2px solid rgba(255,255,255,0.2);
}

dl dt {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

dl dd {
    margin-bottom: 1rem;
    color: #6c757d;
}

.breadcrumb {
    background-color: transparent;
    padding: 0;
    margin-bottom: 1rem;
}
</style>
@endsection
