@extends('layouts.app')

@section('title', 'Laboratory Equipment')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1" style="color: #1e3a5f;">
                    <i class="bi bi-gear"></i> Laboratory Equipment
                </h1>
                <p class="text-secondary mb-0">Manage and track laboratory equipment</p>
            </div>
            <div>
                <a href="{{ route('lab.equipment.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Equipment
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-gear-fill"></i>
                </div>
                <div class="stat-label">Total Equipment</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Operational</div>
                <div class="stat-value">{{ number_format($statistics['operational']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-tools"></i>
                </div>
                <div class="stat-label">In Maintenance</div>
                <div class="stat-value">{{ number_format($statistics['maintenance']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-label">Needs Maintenance</div>
                <div class="stat-value">{{ number_format($statistics['needs_maintenance']) }}</div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('lab.equipment.index') }}" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Equipment name, serial number, model..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="operational" {{ request('status') === 'operational' ? 'selected' : '' }}>Operational</option>
                        <option value="under_maintenance" {{ request('status') === 'under_maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                        <option value="out_of_service" {{ request('status') === 'out_of_service' ? 'selected' : '' }}>Out of Service</option>
                        <option value="retired" {{ request('status') === 'retired' ? 'selected' : '' }}>Retired</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('lab.equipment.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Equipment Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Equipment Name</th>
                            <th>Model</th>
                            <th>Serial Number</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Next Maintenance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($equipment as $equip)
                        <tr>
                            <td><strong>{{ $equip->name }}</strong></td>
                            <td>{{ $equip->model ?? 'N/A' }}</td>
                            <td><code>{{ $equip->serial_number }}</code></td>
                            <td><span class="badge bg-secondary">{{ $equip->equipment_type }}</span></td>
                            <td>{{ $equip->location ?? 'N/A' }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'operational' => 'success',
                                        'under_maintenance' => 'warning',
                                        'out_of_service' => 'danger',
                                        'retired' => 'secondary'
                                    ];
                                    $color = $statusColors[$equip->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $equip->status)) }}
                                </span>
                            </td>
                            <td>
                                @if($equip->next_maintenance_date)
                                    @php
                                        $daysUntil = now()->diffInDays($equip->next_maintenance_date, false);
                                        $isOverdue = $daysUntil < 0;
                                        $isDueSoon = $daysUntil <= 7 && $daysUntil >= 0;
                                    @endphp
                                    <span class="badge {{ $isOverdue ? 'bg-danger' : ($isDueSoon ? 'bg-warning' : 'bg-info') }}">
                                        {{ $equip->next_maintenance_date->format('M d, Y') }}
                                        @if($isOverdue)
                                            (Overdue)
                                        @elseif($isDueSoon)
                                            (Due Soon)
                                        @endif
                                    </span>
                                @else
                                    <span class="text-muted">Not scheduled</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('lab.equipment.show', $equip) }}" 
                                       class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('lab.equipment.edit', $equip) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('lab.equipment.destroy', $equip) }}" 
                                          method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this equipment?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                <p class="mt-2">No equipment found</p>
                                <a href="{{ route('lab.equipment.create') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add First Equipment
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($equipment->hasPages())
            <div class="mt-4">
                {{ $equipment->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
