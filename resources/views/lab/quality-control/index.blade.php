@extends('layouts.app')

@section('title', 'Quality Control')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1" style="color: #1e3a5f;">
                    <i class="bi bi-clipboard-check"></i> Quality Control
                </h1>
                <p class="text-secondary mb-0">Monitor and track quality control testing</p>
            </div>
            <div>
                <a href="{{ route('lab.quality-control.statistics') }}" class="btn btn-info">
                    <i class="bi bi-graph-up"></i> Statistics
                </a>
                <a href="{{ route('lab.quality-control.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New QC Record
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <div class="stat-label">Total Records</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <div class="stat-label">This Month</div>
                <div class="stat-value">{{ number_format($statistics['this_month']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Passed</div>
                <div class="stat-value">{{ number_format($statistics['passed']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-label">Failed</div>
                <div class="stat-value">{{ number_format($statistics['failed']) }}</div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('lab.quality-control.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Lot number, material..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" 
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" 
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="acceptable" {{ request('status') === 'acceptable' ? 'selected' : '' }}>Passed</option>
                        <option value="unacceptable" {{ request('status') === 'unacceptable' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('lab.quality-control.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- QC Records Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Parameter</th>
                            <th>Type</th>
                            <th>Level</th>
                            <th>Lot Number</th>
                            <th>Target Value</th>
                            <th>Measured Value</th>
                            <th>Range</th>
                            <th>Status</th>
                            <th>Performed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                        <tr>
                            <td>{{ $record->performed_at ? $record->performed_at->format('M d, Y H:i') : 'N/A' }}</td>
                            <td>
                                <strong>{{ $record->parameter ? $record->parameter->parameter_name : 'N/A' }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $record->qc_type)) }}</span>
                            </td>
                            <td>{{ ucfirst(str_replace('_', ' ', $record->qc_level)) }}</td>
                            <td>{{ $record->lot_number }}</td>
                            <td class="text-end">{{ number_format($record->target_value, 2) }}</td>
                            <td class="text-end">{{ number_format($record->measured_value, 2) }}</td>
                            <td class="text-center">
                                <small>{{ number_format($record->acceptable_range_low, 2) }} - {{ number_format($record->acceptable_range_high, 2) }}</small>
                            </td>
                            <td>
                                @if($record->is_acceptable)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Passed
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> Failed
                                    </span>
                                @endif
                            </td>
                            <td>{{ $record->performedBy ? $record->performedBy->firstname : 'N/A' }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('lab.quality-control.show', $record) }}" 
                                       class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('lab.quality-control.edit', $record) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('lab.quality-control.destroy', $record) }}" 
                                          method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this QC record?')">
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
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                <p class="mt-2">No quality control records found</p>
                                <a href="{{ route('lab.quality-control.create') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Create First Record
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($records->hasPages())
            <div class="mt-4">
                {{ $records->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
