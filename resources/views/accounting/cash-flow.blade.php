@extends('layouts.app')

@section('title', 'Cash Flow Statement')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-arrow-left-right me-2"></i>Cash Flow Statement
            </h1>
            <p class="text-secondary mb-0">Operating cash inflows and outflows for the selected period</p>
        </div>
        <div class="d-flex gap-2">
            @include('accounting.partials.export-buttons')
            <a href="{{ route('accounting.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Hub</a>
        </div>
    </div>

    @include('accounting.partials.filters', ['branches' => $branches, 'branchId' => $branchId, 'startDate' => $startDate, 'endDate' => $endDate])

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-label">Operating Inflows</div>
                <div class="stat-value">GH₵{{ number_format($cashFlow['operating']['inflows'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-label">Operating Outflows</div>
                <div class="stat-value">GH₵{{ number_format($cashFlow['operating']['outflows'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-label">Net Operating</div>
                <div class="stat-value">GH₵{{ number_format($cashFlow['operating']['net'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card {{ $cashFlow['net_change_in_cash'] >= 0 ? 'success' : 'warning' }}">
                <div class="stat-label">Net Change in Cash</div>
                <div class="stat-value">GH₵{{ number_format($cashFlow['net_change_in_cash'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header"><strong>Daily Cash Movement</strong></div>
                <div class="card-body">
                    @if(empty($cashFlow['daily_flows']))
                        <p class="text-muted mb-0">No cash movement in this period.</p>
                    @else
                        <canvas id="cashFlowChart" height="120"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header"><strong>Statement Summary</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr class="table-light"><td colspan="2"><strong>Operating Activities</strong></td></tr>
                            <tr><td>Patient payments received</td><td class="text-end text-success">+{{ number_format($cashFlow['operating']['inflows'], 2) }}</td></tr>
                            <tr><td>Operating expenses paid</td><td class="text-end text-danger">-{{ number_format($cashFlow['operating']['outflows'], 2) }}</td></tr>
                            <tr><td>Refunds / cancellations</td><td class="text-end text-danger">-{{ number_format($cashFlow['operating']['refunds'], 2) }}</td></tr>
                            <tr class="table-light"><th>Net from operating</th><th class="text-end">{{ number_format($cashFlow['operating']['net'], 2) }}</th></tr>
                            <tr class="table-light"><td colspan="2"><strong>Investing / Financing</strong></td></tr>
                            <tr><td>Investing activities</td><td class="text-end">{{ number_format($cashFlow['investing']['net'], 2) }}</td></tr>
                            <tr><td>Financing activities</td><td class="text-end">{{ number_format($cashFlow['financing']['net'], 2) }}</td></tr>
                            <tr class="table-primary"><th>Net change in cash</th><th class="text-end">{{ number_format($cashFlow['net_change_in_cash'], 2) }}</th></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Daily Detail</strong></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th class="text-end">Inflows</th><th class="text-end">Outflows</th><th class="text-end">Net</th></tr>
                </thead>
                <tbody>
                    @forelse($cashFlow['daily_flows'] as $day)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</td>
                        <td class="text-end text-success">GH₵{{ number_format($day['inflow'], 2) }}</td>
                        <td class="text-end text-danger">GH₵{{ number_format($day['outflow'], 2) }}</td>
                        <td class="text-end fw-semibold">GH₵{{ number_format($day['net'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No cash flow activity.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const daily = @json($cashFlow['daily_flows']);
    if (daily.length && document.getElementById('cashFlowChart')) {
        new Chart(document.getElementById('cashFlowChart'), {
            type: 'bar',
            data: {
                labels: daily.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                datasets: [
                    { label: 'Inflows', data: daily.map(d => d.inflow), backgroundColor: '#198754' },
                    { label: 'Outflows', data: daily.map(d => d.outflow), backgroundColor: '#dc3545' }
                ]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    }
</script>
@endpush
