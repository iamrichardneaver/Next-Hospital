@extends('layouts.app')

@section('title', 'Queue Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Queue Management Dashboard</h1>
            <p class="text-secondary mb-0">Monitor and manage patient queues across departments</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @include('components.export-dropdown', [
                'exportRoute' => route('queues.export'),
                'permission' => 'view_queues',
                'params' => request()->only(['branch_id', 'queue_type', 'status']),
            ])
            <select class="form-select form-select-sm queue-filter-select" id="branchFilter" onchange="window.location.href='?branch_id='+this.value">
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branch->id == $branchId ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- OPD Queue Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-hospital"></i> OPD Queue</h5>
            <a href="{{ route('queues.opd', ['branch_id' => $branchId]) }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-right-circle"></i> Manage
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-label">Waiting</div>
                        <div class="stat-value">{{ $stats['opd']['total_waiting'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                        <div class="stat-label">Being Served</div>
                        <div class="stat-value">{{ $stats['opd']['total_serving'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-label">Completed Today</div>
                        <div class="stat-value">{{ $stats['opd']['total_completed_today'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="bi bi-clock"></i></div>
                        <div class="stat-label">Avg Wait Time</div>
                        <div class="stat-value">{{ $stats['opd']['avg_wait_time'] }} min</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lab Queue Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-flask"></i> Lab Queue</h5>
            <a href="{{ route('queues.lab', ['branch_id' => $branchId]) }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-right-circle"></i> Manage
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-label">Waiting</div>
                        <div class="stat-value">{{ $stats['lab']['total_waiting'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                        <div class="stat-label">Being Served</div>
                        <div class="stat-value">{{ $stats['lab']['total_serving'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-label">Completed Today</div>
                        <div class="stat-value">{{ $stats['lab']['total_completed_today'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="bi bi-clock"></i></div>
                        <div class="stat-label">Avg Wait Time</div>
                        <div class="stat-value">{{ $stats['lab']['avg_wait_time'] }} min</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pharmacy Queue Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-capsule"></i> Pharmacy Queue</h5>
            <a href="{{ route('queues.pharmacy', ['branch_id' => $branchId]) }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-right-circle"></i> Manage
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-label">Waiting</div>
                        <div class="stat-value">{{ $stats['pharmacy']['total_waiting'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                        <div class="stat-label">Being Served</div>
                        <div class="stat-value">{{ $stats['pharmacy']['total_serving'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-label">Completed Today</div>
                        <div class="stat-value">{{ $stats['pharmacy']['total_completed_today'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="bi bi-clock"></i></div>
                        <div class="stat-label">Avg Wait Time</div>
                        <div class="stat-value">{{ $stats['pharmacy']['avg_wait_time'] }} min</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Radiology Queue Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-camera-reels"></i> Radiology Queue</h5>
            <a href="{{ route('queues.radiology', ['branch_id' => $branchId]) }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-right-circle"></i> Manage
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-label">Waiting</div>
                        <div class="stat-value">{{ $stats['radiology']['total_waiting'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                        <div class="stat-label">Being Served</div>
                        <div class="stat-value">{{ $stats['radiology']['total_serving'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-label">Completed Today</div>
                        <div class="stat-value">{{ $stats['radiology']['total_completed_today'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="bi bi-clock"></i></div>
                        <div class="stat-label">Avg Wait Time</div>
                        <div class="stat-value">{{ $stats['radiology']['avg_wait_time'] }} min</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Queue Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-heart-pulse"></i> Emergency Queue</h5>
            <a href="{{ route('queues.emergency', ['branch_id' => $branchId]) }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-right-circle"></i> Manage
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-label">Waiting</div>
                        <div class="stat-value">{{ $stats['emergency']['total_waiting'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                        <div class="stat-label">Being Served</div>
                        <div class="stat-value">{{ $stats['emergency']['total_serving'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-label">Completed Today</div>
                        <div class="stat-value">{{ $stats['emergency']['total_completed_today'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="bi bi-clock"></i></div>
                        <div class="stat-label">Avg Wait Time</div>
                        <div class="stat-value">{{ $stats['emergency']['avg_wait_time'] }} min</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>
@endsection

