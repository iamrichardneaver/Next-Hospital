@extends('layouts.app')

@section('title', 'QC Statistics')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1" style="color: #1e3a5f;">
                    <i class="bi bi-graph-up"></i> Quality Control Statistics
                </h1>
                <p class="text-secondary mb-0">Analyze QC performance and trends</p>
            </div>
            <div>
                <a href="{{ route('lab.quality-control.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to QC
                </a>
            </div>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('lab.quality-control.statistics') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    @if($parameterStats->count() > 0)
    <div class="row">
        @foreach($parameterStats as $stats)
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $stats['parameter'] ? $stats['parameter']->parameter_name : 'N/A' }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-4 text-center">
                            <h3 class="text-primary">{{ $stats['total'] }}</h3>
                            <p class="text-muted mb-0">Total Tests</p>
                        </div>
                        <div class="col-4 text-center">
                            <h3 class="text-success">{{ $stats['passed'] }}</h3>
                            <p class="text-muted mb-0">Passed</p>
                        </div>
                        <div class="col-4 text-center">
                            <h3 class="text-danger">{{ $stats['failed'] }}</h3>
                            <p class="text-muted mb-0">Failed</p>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h4>
                            @if($stats['pass_rate'] >= 95)
                                <span class="badge bg-success">{{ $stats['pass_rate'] }}% Pass Rate</span>
                            @elseif($stats['pass_rate'] >= 80)
                                <span class="badge bg-warning">{{ $stats['pass_rate'] }}% Pass Rate</span>
                            @else
                                <span class="badge bg-danger">{{ $stats['pass_rate'] }}% Pass Rate</span>
                            @endif
                        </h4>
                    </div>
                    @if($stats['latest'])
                    <div class="mt-3">
                        <small class="text-muted">
                            Last Test: {{ $stats['latest']->performed_at ? $stats['latest']->performed_at->format('M d, Y') : 'N/A' }} - 
                            <span class="{{ $stats['latest']->is_acceptable ? 'text-success' : 'text-danger' }}">
                                {{ $stats['latest']->is_acceptable ? 'Passed' : 'Failed' }}
                            </span>
                        </small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No QC data available for the selected period</p>
            <a href="{{ route('lab.quality-control.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add QC Record
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
