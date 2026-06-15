@extends('layouts.app')

@section('title', 'Financial Report')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-receipt me-2"></i>Financial Report</h1>
            <p class="text-muted mb-0">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('revenue.index', ['start_date' => $startDate, 'end_date' => $endDate, 'branch_id' => $branchId]) }}" class="btn btn-primary btn-sm">Revenue Analytics</a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Completed Revenue</div>
                <div class="h4 mb-0">GHS {{ number_format($summary['total_revenue'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Invoices</div>
                <div class="h4 mb-0">{{ number_format($summary['invoice_count']) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Payments</div>
                <div class="h4 mb-0">{{ number_format($summary['payments_count']) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Outstanding</div>
                <div class="h4 mb-0 text-warning">GHS {{ number_format($summary['outstanding'], 2) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header"><strong>Recent Payments</strong></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Invoice</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') : '—' }}</td>
                            <td>{{ $payment->patient?->first_name }} {{ $payment->patient?->last_name }}</td>
                            <td>{{ $payment->invoice?->invoice_number ?? '—' }}</td>
                            <td>{{ \App\Enums\PaymentMethod::labelFor($payment->payment_method ?? '') ?: '—' }}</td>
                            <td class="text-end">GHS {{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No payments in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
