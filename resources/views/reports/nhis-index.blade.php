@extends('layouts.app')

@section('title', 'NHIS Claims')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-shield-check me-2"></i>NHIS Claims</h1>
            <p class="text-muted mb-0">National Health Insurance Scheme claims from live data.</p>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">Back to Reports Hub</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Claim ID</th>
                        <th>Patient</th>
                        <th>NHIS No.</th>
                        <th>Visit Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($claims as $claim)
                        <tr>
                            <td>{{ $claim->claim_id ?? $claim->id }}</td>
                            <td>{{ $claim->patient?->first_name }} {{ $claim->patient?->last_name }}</td>
                            <td>{{ $claim->nhis_number ?? '—' }}</td>
                            <td>{{ $claim->visit_date ? \Carbon\Carbon::parse($claim->visit_date)->format('M d, Y') : '—' }}</td>
                            <td>GHS {{ number_format($claim->total_amount ?? 0, 2) }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($claim->status ?? 'draft') }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No NHIS claims yet. Claims can be submitted from insurance workflows or the API.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($claims->hasPages())
            <div class="card-footer">{{ $claims->links() }}</div>
        @endif
    </div>
</div>
@endsection
