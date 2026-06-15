@extends('layouts.app')

@section('title', $serviceLabel . ' Revenue Detail')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-list-ul me-2"></i>{{ $serviceLabel }} — Revenue Drill-Down
            </h1>
            <p class="text-secondary mb-0">Line-item transactions for the selected period</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.revenue', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Revenue Streams
            </a>
            @include('accounting.partials.export-buttons', ['exportRoute' => route('accounting.revenue.drill-down', $serviceType)])
        </div>
    </div>

    @include('accounting.partials.filters', ['branches' => $branches, 'branchId' => $branchId, 'startDate' => $startDate, 'endDate' => $endDate])

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="stat-label">Stream Total</div>
                <div class="stat-value">GH₵{{ number_format($streamTotal, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                <div class="stat-label">Transactions</div>
                <div class="stat-value">{{ number_format($rows->count()) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="bi bi-layers"></i></div>
                <div class="stat-label">Service Module</div>
                <div class="stat-value" style="font-size: 1.25rem;">{{ $serviceLabel }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Transaction Line Items</strong></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Patient</th>
                        <th>Invoice</th>
                        <th>Payment Method</th>
                        <th class="text-end">Amount (GH₵)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['transaction_date'] }}</td>
                        <td><code class="small">{{ $row['transaction_reference'] }}</code></td>
                        <td>
                            @if($row['patient_id'])
                                <a href="{{ route('patients.show', $row['patient_id']) }}">{{ $row['patient_name'] }}</a>
                                @if($row['patient_number'])
                                    <br><small class="text-muted">{{ $row['patient_number'] }}</small>
                                @endif
                            @else
                                {{ $row['patient_name'] }}
                            @endif
                        </td>
                        <td>{{ $row['invoice_number'] ?? ($row['invoice_id'] ? '#' . $row['invoice_id'] : '—') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $row['payment_method'] ?? '—')) }}</td>
                        <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No transactions for this module in the selected period.</td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                <tfoot class="table-light">
                    <tr>
                        <th colspan="5">Total</th>
                        <th class="text-end">{{ number_format($streamTotal, 2) }}</th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
