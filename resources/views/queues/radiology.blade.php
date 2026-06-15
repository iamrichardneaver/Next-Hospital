@extends('layouts.app')

@section('title', 'Radiology Queue')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-camera-reels"></i> Radiology Queue Management</h1>
            <p class="text-secondary mb-0">Imaging & Radiology Services Queue</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm queue-filter-select" id="branchFilter" onchange="window.location.href='?branch_id='+this.value">
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branch->id == $branchId ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            <button class="btn btn-secondary" onclick="toggleAudio()" title="Toggle Audio Announcements">
                <i class="bi bi-volume-up"></i>
            </button>
            <button class="btn btn-outline-secondary" onclick="testAudio()" title="Test Audio">
                <i class="bi bi-soundwave"></i>
            </button>
            @canany(['manage_queues', 'perform_radiology_studies', 'complete_radiology_studies'])
            <button class="btn btn-success" onclick="callNextPatient()">
                <i class="bi bi-bell"></i> Call Next Patient
            </button>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-label">Waiting</div>
                <div class="stat-value">{{ $stats['total_waiting'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                <div class="stat-label">Being Served</div>
                <div class="stat-value">{{ $stats['total_serving'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-label">Completed Today</div>
                <div class="stat-value">{{ $stats['total_completed_today'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card secondary">
                <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                <div class="stat-label">Avg Wait Time</div>
                <div class="stat-value">{{ $stats['avg_wait_time'] }} min</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Currently Serving -->
        @if($servingQueue)
        <div class="col-md-12 mb-4">
            <div class="card border-success shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person-check-fill"></i> Currently Serving</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-1">{{ $servingQueue->patient->first_name }} {{ $servingQueue->patient->last_name }}</h4>
                            <p class="text-muted mb-1">
                                <strong>Patient #:</strong> {{ $servingQueue->patient->patient_number }}<br>
                                @if($servingQueue->visit)
                                <strong>Visit Token:</strong> {{ $servingQueue->visit->visit_token }}<br>
                                @endif
                                <strong>Priority:</strong> 
                                <span class="badge bg-{{ $servingQueue->priority === 'critical' ? 'danger' : ($servingQueue->priority === 'urgent' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($servingQueue->priority) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            @canany(['manage_queues', 'perform_radiology_studies', 'complete_radiology_studies'])
                            <button class="btn btn-primary" onclick="completeServing({{ $servingQueue->id }})">
                                <i class="bi bi-check-circle"></i> Complete Service
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Waiting Queue -->
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Waiting Queue ({{ $waitingQueues->count() }})</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Position</th>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Priority</th>
                                    <th>Queued At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($waitingQueues as $queue)
                                <tr class="priority-{{ $queue->priority }}">
                                    <td><span class="badge bg-secondary">{{ $queue->position }}</span></td>
                                    <td>
                                        <strong>{{ $queue->patient->first_name }} {{ $queue->patient->last_name }}</strong><br>
                                        <small class="text-muted">{{ $queue->patient->patient_number }}</small>
                                        @if($queue->visit)
                                        <br><small class="text-info">Visit: {{ $queue->visit->visit_token }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $queue->patient->phone ?? 'N/A' }}</small></td>
                                    <td>
                                        <span class="badge bg-{{ $queue->priority === 'critical' ? 'danger' : ($queue->priority === 'urgent' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($queue->priority) }}
                                        </span>
                                    </td>
                                    <td><small>{{ $queue->queued_at->diffForHumans() }}</small></td>
                                    <td>
                                        @canany(['manage_queues', 'perform_radiology_studies', 'complete_radiology_studies'])
                                        <button class="btn btn-sm btn-primary" onclick="startServing({{ $queue->id }})">
                                            <i class="bi bi-play-circle"></i> Start Serving
                                        </button>
                                        @endcan
                                        <a href="{{ route('patients.show', $queue->patient_id) }}" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-inbox text-secondary" style="font-size: 2rem; opacity: 0.5;"></i>
                                        <p class="text-secondary mb-0 mt-2">No patients waiting in queue</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function callNextPatient() {
        fetch('{{ route("queues.call-next") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                queue_type: 'Radiology',
                branch_id: {{ $branchId }}
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error calling patient');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error calling patient');
        });
    }

    function startServing(queueId) {
        fetch(`/queues/${queueId}/start-serving`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error starting service');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error starting service');
        });
    }

    function completeServing(queueId) {
        fetch(`/queues/${queueId}/complete-serving`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error completing service');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error completing service');
        });
    }

    function toggleAudio() {
        // Audio toggle functionality
        console.log('Toggle audio');
    }

    function testAudio() {
        // Test audio functionality
        console.log('Test audio');
    }
</script>
@endpush
@endsection
