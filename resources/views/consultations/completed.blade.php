@extends('layouts.app')

@section('title', 'Completed Consultations')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-clipboard-check"></i> Completed Consultations</h1>
            <p class="text-secondary mb-0">View your completed consultation history</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            @include('components.export-dropdown', [
                'exportRoute' => route('consultations.completed.export'),
                'permission' => 'view_consultations',
                'params' => request()->only(['branch_id', 'date_filter']),
            ])
            <select class="form-select form-select-sm consultation-filter-select" id="branchFilter" onchange="applyFilters()">
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branch->id == $branchId ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            <select class="form-select form-select-sm consultation-filter-select" id="dateFilter" onchange="applyFilters()">
                <option value="today" {{ $dateFilter == 'today' ? 'selected' : '' }}>Today</option>
                <option value="week" {{ $dateFilter == 'week' ? 'selected' : '' }}>This Week</option>
                <option value="month" {{ $dateFilter == 'month' ? 'selected' : '' }}>This Month</option>
                <option value="all" {{ $dateFilter == 'all' ? 'selected' : '' }}>All Time</option>
            </select>
            <a href="{{ route('consultations.doctor-queue') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back to Queue
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-label">Completed Today</div>
                <div class="stat-value">{{ $stats['completed_today'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="bi bi-calendar-week"></i></div>
                <div class="stat-label">This Week</div>
                <div class="stat-value">{{ $stats['completed_this_week'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-calendar-month"></i></div>
                <div class="stat-label">This Month</div>
                <div class="stat-value">{{ $stats['completed_this_month'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-clipboard-data"></i></div>
                <div class="stat-label">Total Completed</div>
                <div class="stat-value">{{ $stats['total_completed'] }}</div>
            </div>
        </div>
    </div>

    <!-- Completed Consultations List -->
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-check"></i> Completed Consultations ({{ $completedConsultations->total() }})</h5>
            <span class="badge bg-light text-dark">Showing {{ $dateFilter === 'today' ? 'Today' : ($dateFilter === 'week' ? 'This Week' : ($dateFilter === 'month' ? 'This Month' : 'All Time')) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Date/Time</th>
                            <th>Patient</th>
                            <th>Chief Complaint</th>
                            <th>Diagnosis</th>
                            <th>Duration</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($completedConsultations as $consultation)
                        <tr>
                            <td>
                                <div><strong>{{ $consultation->updated_at->format('M d, Y') }}</strong></div>
                                <small class="text-muted">{{ $consultation->updated_at->format('h:i A') }}</small>
                            </td>
                            <td>
                                @if($consultation->patient)
                                    <strong>{{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}</strong><br>
                                    <small class="text-muted">{{ $consultation->patient->patient_number }}</small><br>
                                    <small class="text-muted">{{ $consultation->patient->phone }}</small>
                                @else
                                    <strong class="text-danger">Patient Not Found</strong>
                                @endif
                            </td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width: 250px;" title="{{ $consultation->chief_complaint }}">
                                    {{ Str::limit($consultation->chief_complaint, 60) }}
                                </span>
                            </td>
                            <td>
                                @if($consultation->diagnoses && $consultation->diagnoses->isNotEmpty())
                                    @foreach($consultation->diagnoses->take(2) as $diagnosis)
                                        <span class="badge bg-info mb-1">{{ $diagnosis->diagnosis_description ?? $diagnosis->icd_code }}</span>
                                    @endforeach
                                    @if($consultation->diagnoses->count() > 2)
                                        <span class="badge bg-secondary">+{{ $consultation->diagnoses->count() - 2 }} more</span>
                                    @endif
                                @else
                                    <span class="text-muted">No diagnosis recorded</span>
                                @endif
                            </td>
                            <td>
                                @if($consultation->started_at && $consultation->completed_at)
                                    @php
                                        $duration = \Carbon\Carbon::parse($consultation->started_at)->diffInMinutes(\Carbon\Carbon::parse($consultation->completed_at));
                                    @endphp
                                    <span class="badge bg-light text-dark">{{ $duration }} min</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('consultations.show', $consultation) }}" class="btn btn-info" title="View Details">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @can('edit_consultations')
                                    <a href="{{ route('consultations.edit', $consultation) }}" class="btn btn-warning" title="Amend Consultation">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    <button class="btn btn-success" onclick="printConsultation({{ $consultation->id }})" title="Print">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No completed consultations found for the selected period</p>
                                <a href="{{ route('consultations.doctor-queue') }}" class="btn btn-primary mt-2">
                                    <i class="bi bi-arrow-left"></i> Go to Queue
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($completedConsultations->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Showing {{ $completedConsultations->firstItem() }} to {{ $completedConsultations->lastItem() }} of {{ $completedConsultations->total() }} entries
                </div>
                <div>
                    {{ $completedConsultations->appends(['branch_id' => $branchId, 'date_filter' => $dateFilter])->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
// Apply filters
function applyFilters() {
    const branchId = document.getElementById('branchFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    window.location.href = `{{ route('consultations.completed') }}?branch_id=${branchId}&date_filter=${dateFilter}`;
}

// Print consultation
function printConsultation(consultationId) {
    window.open(`/consultations/${consultationId}/print`, '_blank');
}
</script>

<style>
.stat-card {
    padding: 20px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-left: 4px solid;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.stat-card.success {
    border-left-color: #28a745;
}

.stat-card.info {
    border-left-color: #17a2b8;
}

.stat-card.primary {
    border-left-color: #007bff;
}

.stat-card.warning {
    border-left-color: #ffc107;
}

.stat-icon {
    font-size: 2rem;
    opacity: 0.8;
    margin-bottom: 10px;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #1e3a5f;
}

.table thead th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.consultation-filter-select {
    width: auto;
    min-width: 11rem;
    max-width: 14rem;
    height: calc(1.5em + 0.5rem + 2px);
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
}
</style>
@endsection

