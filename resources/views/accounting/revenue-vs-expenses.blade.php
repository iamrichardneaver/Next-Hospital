@extends('layouts.app')

@section('title', 'Revenue vs Expenses')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-bar-chart-line me-2"></i>Revenue vs Expenses
            </h1>
            <p class="text-secondary mb-0">Comparative financial performance over time</p>
        </div>
        <div class="d-flex gap-2">
            @include('accounting.partials.export-buttons')
            <a href="{{ route('accounting.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Hub</a>
        </div>
    </div>

    @include('accounting.partials.filters', ['branches' => $branches, 'branchId' => $branchId, 'startDate' => $startDate, 'endDate' => $endDate, 'showPeriod' => true, 'period' => $period])

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card success">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">GH₵{{ number_format($comparison['totals']['revenue'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card danger">
                <div class="stat-label">Total Expenses</div>
                <div class="stat-value">GH₵{{ number_format($comparison['totals']['expenses'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card {{ $comparison['totals']['net'] >= 0 ? 'primary' : 'warning' }}">
                <div class="stat-label">Net Result</div>
                <div class="stat-value">GH₵{{ number_format($comparison['totals']['net'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Revenue vs Expenses Chart</strong></div>
        <div class="card-body">
            @if(empty($comparison['periods']))
                <p class="text-muted mb-0">No data for comparison in this period.</p>
            @else
                <canvas id="comparisonChart" height="100"></canvas>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header"><strong>Period Comparison</strong></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Expenses</th>
                                <th class="text-end">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($comparison['periods'] as $row)
                            @php
                                [$periodStart, $periodEnd] = app(\App\Services\AccountingReportService::class)
                                    ->resolvePeriodDateRange($row['period_key'], $period ?? 'monthly');
                                $periodQuery = array_filter([
                                    'start_date' => $periodStart,
                                    'end_date' => $periodEnd,
                                    'branch_id' => $branchId,
                                ], fn ($v) => $v !== null && $v !== '');
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('accounting.revenue', $periodQuery) }}" class="text-decoration-none" title="Drill into revenue for this period">
                                        {{ $row['label'] }}
                                        <i class="bi bi-box-arrow-up-right small text-muted"></i>
                                    </a>
                                </td>
                                <td class="text-end text-success">GH₵{{ number_format($row['revenue'], 2) }}</td>
                                <td class="text-end text-danger">
                                    <a href="{{ route('accounting.expenses.index', $periodQuery) }}" class="text-danger text-decoration-none" title="Drill into expenses for this period">
                                        GH₵{{ number_format($row['expenses'], 2) }}
                                    </a>
                                </td>
                                <td class="text-end fw-semibold {{ $row['net'] >= 0 ? 'text-primary' : 'text-warning' }}">
                                    GH₵{{ number_format($row['net'], 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No comparison data.</td></tr>
                            @endforelse
                        </tbody>
                        @if(!empty($comparison['periods']))
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-end">GH₵{{ number_format($comparison['totals']['revenue'], 2) }}</th>
                                <th class="text-end">GH₵{{ number_format($comparison['totals']['expenses'], 2) }}</th>
                                <th class="text-end">GH₵{{ number_format($comparison['totals']['net'], 2) }}</th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header"><strong>Expenses by Category</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Category</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            @forelse($expensesByCategory as $cat)
                            <tr>
                                <td>
                                    <a href="{{ route('accounting.expenses.index', array_filter([
                                        'category_id' => $cat['category_id'],
                                        'start_date' => $startDate,
                                        'end_date' => $endDate,
                                        'branch_id' => $branchId,
                                    ], fn ($v) => $v !== null && $v !== '')) }}" class="text-decoration-none">
                                        {{ $cat['category'] }}
                                        <i class="bi bi-box-arrow-up-right small text-muted"></i>
                                    </a>
                                </td>
                                <td class="text-end">GH₵{{ number_format($cat['total'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">No approved expenses.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const periods = @json($comparison['periods']);
    if (periods.length && document.getElementById('comparisonChart')) {
        new Chart(document.getElementById('comparisonChart'), {
            type: 'bar',
            data: {
                labels: periods.map(p => p.label),
                datasets: [
                    { label: 'Revenue', data: periods.map(p => p.revenue), backgroundColor: '#198754' },
                    { label: 'Expenses', data: periods.map(p => p.expenses), backgroundColor: '#dc3545' },
                    { label: 'Net', data: periods.map(p => p.net), type: 'line', borderColor: '#0d6efd', backgroundColor: 'transparent', tension: 0.3 }
                ]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    }
</script>
@endpush
