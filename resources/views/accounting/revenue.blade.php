@extends('layouts.app')

@section('title', 'Revenue Streams')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-pie-chart me-2"></i>Revenue Streams
            </h1>
            <p class="text-secondary mb-0">Breakdown by hospital service module from completed payments</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @include('accounting.partials.export-buttons')
            <a href="{{ route('accounting.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Accounting Hub
            </a>
        </div>
    </div>

    @include('accounting.partials.filters', ['branches' => $branches, 'branchId' => $branchId, 'startDate' => $startDate, 'endDate' => $endDate])

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">GH₵{{ number_format($totalRevenue, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-layers"></i></div>
                <div class="stat-label">Service Streams</div>
                <div class="stat-value">{{ count($revenueByService) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-credit-card"></i></div>
                <div class="stat-label">Payment Methods</div>
                <div class="stat-value">{{ count($revenueByMethod) }}</div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><strong>Revenue by Service Module</strong></div>
                <div class="card-body">
                    @if(empty($revenueByService))
                        <p class="text-muted mb-0">No revenue data for the selected period.</p>
                    @else
                        <canvas id="serviceChart" height="260"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><strong>Revenue by Payment Method</strong></div>
                <div class="card-body">
                    @if(empty($revenueByMethod))
                        <p class="text-muted mb-0">No payment method data for the selected period.</p>
                    @else
                        <canvas id="methodChart" height="260"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Detailed Revenue Breakdown</strong></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Service Module</th>
                        <th class="text-end">Transactions</th>
                        <th class="text-end">Revenue (GH₵)</th>
                        <th class="text-end">Share</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revenueByService as $row)
                    <tr>
                        <td>
                            <a href="{{ route('accounting.revenue.drill-down', array_merge(request()->query(), ['serviceType' => $row['service_type']])) }}" class="text-decoration-none">
                                {{ $row['label'] }}
                                <i class="bi bi-box-arrow-up-right small text-muted"></i>
                            </a>
                        </td>
                        <td class="text-end">{{ number_format($row['count']) }}</td>
                        <td class="text-end">{{ number_format($row['total'], 2) }}</td>
                        <td class="text-end">{{ number_format($row['percentage'], 1) }}%</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No revenue records found.</td></tr>
                    @endforelse
                </tbody>
                @if(!empty($revenueByService))
                <tfoot class="table-light">
                    <tr>
                        <th>Total</th>
                        <th class="text-end">{{ number_format(array_sum(array_column($revenueByService, 'count'))) }}</th>
                        <th class="text-end">{{ number_format($totalRevenue, 2) }}</th>
                        <th class="text-end">100%</th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="alert alert-light border small mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Revenue is sourced from <strong>revenue_transactions</strong> (completed payments via PaymentObserver). Service type is derived from invoice line items.
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const serviceData = @json($revenueByService);
    const methodData = @json($revenueByMethod);
    const colors = ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#20c997','#fd7e14','#0dcaf0','#6c757d'];

    if (serviceData.length && document.getElementById('serviceChart')) {
        new Chart(document.getElementById('serviceChart'), {
            type: 'doughnut',
            data: {
                labels: serviceData.map(d => d.label),
                datasets: [{ data: serviceData.map(d => d.total), backgroundColor: colors }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    if (methodData.length && document.getElementById('methodChart')) {
        new Chart(document.getElementById('methodChart'), {
            type: 'bar',
            data: {
                labels: methodData.map(d => d.label),
                datasets: [{ label: 'GH₵', data: methodData.map(d => d.total), backgroundColor: '#0d6efd' }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    }
</script>
@endpush
