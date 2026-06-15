@php
    $labRequests = $consultation->labRequests ?? collect();
    $pendingCount = $labRequests->whereIn('status', ['pending', 'in_progress'])->count();
    $withResultsCount = $labRequests->filter(fn ($lr) => $lr->results && $lr->results->count() > 0)->count();
@endphp

@can('view_lab_requests')
<div class="card shadow-sm mb-4" id="consultation-lab-requests">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 text-dark">
            <i class="bi bi-flask"></i> Lab Requests
            @if($labRequests->count() > 0)
                <span class="badge bg-primary ms-1">{{ $labRequests->count() }}</span>
            @endif
        </h6>
        <div class="d-flex align-items-center gap-2">
            @if($pendingCount > 0)
                <span class="badge bg-warning">
                    <i class="bi bi-hourglass-split"></i> {{ $pendingCount }} pending
                </span>
            @endif
            @if($withResultsCount > 0)
                <span class="badge bg-success">
                    <i class="bi bi-check-circle"></i> {{ $withResultsCount }} with results
                </span>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        @if($labRequests->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Test</th>
                            <th>Status</th>
                            <th>Results</th>
                            <th>Last Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($labRequests as $labRequest)
                            @php
                                $resultCount = $labRequest->results ? $labRequest->results->count() : 0;
                                $hasResults = $resultCount > 0;
                                $statusColors = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                ];
                                $statusColor = $statusColors[$labRequest->status] ?? 'secondary';
                                $summaryResults = $hasResults
                                    ? $labRequest->results->take(3)
                                    : collect();
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $labRequest->test_type_name ?? $labRequest->test_type ?? 'Lab Test' }}</div>
                                    @if($labRequest->request_number || $labRequest->lab_request_number)
                                        <small class="text-muted">{{ $labRequest->lab_request_number ?? $labRequest->request_number }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $statusColor }}">
                                        {{ ucfirst(str_replace('_', ' ', $labRequest->status)) }}
                                    </span>
                                    @if($labRequest->priority && $labRequest->priority !== 'routine')
                                        <span class="badge bg-{{ $labRequest->priority === 'stat' ? 'danger' : 'warning' }} ms-1">
                                            {{ strtoupper($labRequest->priority) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($hasResults)
                                        <span class="badge bg-success mb-1">Results available</span>
                                        <div class="small text-muted">
                                            @foreach($summaryResults as $result)
                                                <div>
                                                    <strong>{{ $result->parameter_name }}:</strong>
                                                    {{ $result->formatted_value ?? $result->result_value }}
                                                    @if($result->result_status && $result->result_status !== 'normal')
                                                        <span class="badge bg-{{ $result->result_status === 'critical' ? 'danger' : 'warning' }}">
                                                            {{ ucfirst($result->result_status) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if($resultCount > 3)
                                                <div class="text-muted">+{{ $resultCount - 3 }} more</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-hourglass"></i> Results pending
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $labRequest->updated_at?->format('M d, Y H:i') ?? '—' }}</div>
                                    @if($labRequest->completed_at)
                                        <small class="text-success">Completed {{ $labRequest->completed_at->diffForHumans() }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        @can('view_lab_results')
                                        <a href="{{ route('lab.show', $labRequest) }}" class="btn btn-sm btn-outline-primary" title="View full report">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @endcan
                                        @if($hasResults)
                                        @can('print_lab_results')
                                        <a href="{{ route('lab.generate-pdf', $labRequest) }}" class="btn btn-sm btn-outline-success" title="Download PDF" target="_blank">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                        @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2 border-top bg-light small text-muted">
                <i class="bi bi-arrow-clockwise"></i> Refresh this page to see the latest lab status and results.
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="bi bi-flask display-6 d-block mb-2"></i>
                No lab tests ordered for this consultation.
            </div>
        @endif
    </div>
</div>
@endcan
