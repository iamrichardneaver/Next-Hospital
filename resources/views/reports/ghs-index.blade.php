@extends('layouts.app')

@section('title', 'GHS Reports')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-file-earmark-medical me-2"></i>GHS Reports</h1>
            <p class="text-muted mb-0">Ghana Health Service reporting submissions from live data.</p>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">Back to Reports Hub</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Report ID</th>
                        <th>Type</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Cases</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>{{ $report->report_id ?? $report->id }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $report->report_type ?? 'general')) }}</td>
                            <td>{{ $report->report_period ?? ($report->reporting_month . '/' . $report->reporting_year) }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($report->status ?? 'draft') }}</span></td>
                            <td>{{ $report->total_cases ?? 0 }}</td>
                            <td>{{ $report->updated_at?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No GHS reports yet. Create reports via the API or add seed data for UAT.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
            <div class="card-footer">{{ $reports->links() }}</div>
        @endif
    </div>
</div>
@endsection
