@extends('layouts.app')

@section('title', 'Cashier Daily Report')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-cash-coin me-2"></i>Cashier Daily Report</h1>
            <p class="text-muted mb-0">{{ \Carbon\Carbon::parse($date)->format('l, M d, Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm">
                <button type="submit" class="btn btn-primary btn-sm">Go</button>
            </form>
            <a href="{{ route('cashier.daily-report', ['date' => $date, 'export' => 'pdf']) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-download"></i> PDF
            </a>
            @can('view_financial_dashboard')
            <a href="{{ route('accounting.index') }}" class="btn btn-outline-secondary btn-sm">Accounting Hub</a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Total Collected</div>
                <div class="h4 mb-0">GH₵ {{ number_format($statistics['total_collected'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Payments</div>
                <div class="h4 mb-0">{{ number_format($statistics['total_payments']) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Patients Served</div>
                <div class="h4 mb-0">{{ number_format($statistics['total_patients_served']) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Outstanding</div>
                <div class="h4 mb-0 text-warning">GH₵ {{ number_format($statistics['outstanding_amount'], 2) }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><strong>Payment Methods</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Method</th><th class="text-end">Count</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody>
                            @forelse($paymentBreakdown as $row)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $row->payment_method)) }}</td>
                                <td class="text-end">{{ $row->count }}</td>
                                <td class="text-end">GH₵ {{ number_format($row->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted text-center py-3">No payments</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><strong>Recent Payments</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Time</th><th>Patient</th><th>Method</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                            <tr>
                                <td>{{ $payment->created_at?->format('H:i') }}</td>
                                <td>{{ $payment->patient?->full_name ?? $payment->invoice?->patient?->full_name ?? '—' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? '')) }}</td>
                                <td class="text-end">GH₵ {{ number_format($payment->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No payments</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
