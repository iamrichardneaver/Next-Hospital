@extends('layouts.app')

@section('title', 'Accounting Hub')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-calculator me-2"></i>Accounting Hub
            </h1>
            <p class="text-secondary mb-0">Financial oversight — revenue, expenses, and hospital reporting</p>
        </div>
    </div>

    @include('accounting.partials.filters', ['branches' => $branches, 'branchId' => $branchId, 'startDate' => $startDate, 'endDate' => $endDate])

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">GH₵{{ number_format($kpis['total_revenue'], 2) }}</div>
                <small class="text-muted">Period total · Today GH₵{{ number_format($kpis['today_revenue'], 2) }}</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
                <div class="stat-label">Total Expenses</div>
                <div class="stat-value">GH₵{{ number_format($kpis['total_expenses'], 2) }}</div>
                <small class="text-muted">{{ number_format($kpis['expense_count']) }} approved/paid records</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card {{ $kpis['net_income'] >= 0 ? 'primary' : 'warning' }}">
                <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="stat-label">Net Income</div>
                <div class="stat-value">GH₵{{ number_format($kpis['net_income'], 2) }}</div>
                <small class="text-muted">Revenue minus approved expenses</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-label">Outstanding Receivables</div>
                <div class="stat-value">GH₵{{ number_format($kpis['outstanding_receivables'], 2) }}</div>
                <small class="text-muted">{{ number_format($kpis['pending_invoices']) }} unpaid invoices</small>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        @canany(['view_expenses', 'manage_expenses', 'approve_expenses'])
        <div class="col-md-4 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-clipboard-check"></i></div>
                <div class="stat-label">Pending Expense Approvals</div>
                <div class="stat-value">{{ number_format($kpis['pending_expenses']) }}</div>
                <small class="text-muted">
                    @if($kpis['pending_expenses'] > 0)
                        <a href="{{ route('accounting.expenses.index', ['status' => 'pending', 'branch_id' => $branchId]) }}">Review approval queue</a>
                    @else
                        No submissions awaiting approval
                    @endif
                </small>
            </div>
        </div>
        @endcanany
        <div class="col-md-4 mb-3">
            <div class="stat-card" style="border-left: 4px solid #6f42c1;">
                <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                <div class="stat-label">Inventory Purchases (Period)</div>
                <div class="stat-value">GH₵{{ number_format($inventoryPurchases['total'], 2) }}</div>
                <small class="text-muted">
                    Pharmacy GH₵{{ number_format($inventoryPurchases['pharmacy'], 2) }}
                    · Lab GH₵{{ number_format($inventoryPurchases['lab'], 2) }}
                    · Radiology GH₵{{ number_format($inventoryPurchases['radiology'] ?? 0, 2) }}
                    @canany(['view_expenses', 'manage_expenses'])
                    · <a href="{{ route('accounting.expenses.index', ['source' => 'inventory', 'start_date' => $startDate, 'end_date' => $endDate, 'branch_id' => $branchId]) }}">View records</a>
                    @endcanany
                </small>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><strong><i class="bi bi-grid me-1"></i> Accounting Modules</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        @can('view_revenue_reports')
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ route('accounting.revenue') }}" class="btn btn-outline-primary w-100 text-start py-3">
                                <i class="bi bi-pie-chart me-2"></i> Revenue Streams
                            </a>
                        </div>
                        @endcan
                        @canany(['view_expenses', 'manage_expenses'])
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-primary w-100 text-start py-3">
                                <i class="bi bi-wallet2 me-2"></i> Expenses
                            </a>
                        </div>
                        @endcanany
                        @can('view_balance_sheet')
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ route('accounting.balance-sheet') }}" class="btn btn-outline-primary w-100 text-start py-3">
                                <i class="bi bi-clipboard-data me-2"></i> Balance Sheet
                            </a>
                        </div>
                        @endcan
                        @can('view_cash_flow')
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ route('accounting.cash-flow') }}" class="btn btn-outline-primary w-100 text-start py-3">
                                <i class="bi bi-arrow-left-right me-2"></i> Cash Flow
                            </a>
                        </div>
                        @endcan
                        @can('view_revenue_reports')
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ route('accounting.revenue-vs-expenses') }}" class="btn btn-outline-primary w-100 text-start py-3">
                                <i class="bi bi-bar-chart-line me-2"></i> Revenue vs Expenses
                            </a>
                        </div>
                        @endcan
                        @can('view_revenue_analytics')
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ route('revenue.index') }}" class="btn btn-outline-secondary w-100 text-start py-3">
                                <i class="bi bi-graph-up me-2"></i> Revenue Analytics
                            </a>
                        </div>
                        @endcan
                        @canany(['view_invoices', 'manage_billing'])
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary w-100 text-start py-3">
                                <i class="bi bi-receipt me-2"></i> Billing & Invoices
                            </a>
                        </div>
                        @endcanany
                        @canany(['view_debtors', 'manage_debtors'])
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ route('debtors.index') }}" class="btn btn-outline-secondary w-100 text-start py-3">
                                <i class="bi bi-people-fill me-2"></i> Debtors
                            </a>
                        </div>
                        @endcanany
                        @can('view_financial_reports')
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ route('reports.financial') }}" class="btn btn-outline-secondary w-100 text-start py-3">
                                <i class="bi bi-file-earmark-bar-graph me-2"></i> Financial Report
                            </a>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-pie-chart me-1"></i> Revenue by Service (Period)</strong>
                    @can('view_revenue_reports')
                    <a href="{{ route('accounting.revenue', ['start_date' => $startDate, 'end_date' => $endDate, 'branch_id' => $branchId]) }}" class="small">Details</a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    @if(empty($revenueStreams))
                        <p class="text-muted p-3 mb-0">No revenue recorded for this period.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($revenueStreams as $stream)
                                    <tr>
                                        <td>
                                            @can('view_revenue_reports')
                                            <a href="{{ route('accounting.revenue.drill-down', array_merge(['serviceType' => $stream['service_type']], ['start_date' => $startDate, 'end_date' => $endDate, 'branch_id' => $branchId])) }}" class="text-decoration-none">
                                                {{ $stream['label'] }}
                                                <i class="bi bi-box-arrow-up-right small text-muted"></i>
                                            </a>
                                            @else
                                            {{ $stream['label'] }}
                                            @endcan
                                        </td>
                                        <td class="text-end">GH₵{{ number_format($stream['total'], 2) }}</td>
                                        <td class="text-end">{{ number_format($stream['percentage'], 1) }}%</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-header"><strong><i class="bi bi-credit-card me-1"></i> Payments Today</strong></div>
                <div class="card-body p-0">
                    @if($paymentsByMethod->isEmpty())
                        <p class="text-muted p-3 mb-0">No payments recorded today.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr><th>Method</th><th class="text-end">Total</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentsByMethod as $row)
                                    <tr>
                                        <td>{{ ucfirst(str_replace('_', ' ', $row->payment_method)) }}</td>
                                        <td class="text-end">GH₵{{ number_format($row->total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header"><strong><i class="bi bi-exclamation-circle me-1"></i> Debt Summary</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-7">Outstanding Debt</dt>
                        <dd class="col-5 text-end">GH₵{{ number_format($kpis['outstanding_debt'], 2) }}</dd>
                        <dt class="col-7">Active Debtors</dt>
                        <dd class="col-5 text-end">{{ number_format($kpis['total_debtors']) }}</dd>
                        <dt class="col-7">Revenue Transactions</dt>
                        <dd class="col-5 text-end">{{ number_format($kpis['revenue_transactions']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @canany(['view_expenses', 'manage_expenses'])
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-building me-1"></i> Expenses by Department (Period)</strong>
                    <a href="{{ route('accounting.expenses.index', ['start_date' => $startDate, 'end_date' => $endDate, 'branch_id' => $branchId]) }}" class="small">All expenses</a>
                </div>
                <div class="card-body p-0">
                    @if(empty($expensesByDepartment))
                        <p class="text-muted p-3 mb-0">No approved expenses for this period.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Department</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($expensesByDepartment as $dept)
                                    <tr>
                                        <td>
                                            <a href="{{ route('accounting.expenses.index', ['department' => $dept['department'], 'start_date' => $startDate, 'end_date' => $endDate, 'branch_id' => $branchId]) }}" class="text-decoration-none">
                                                {{ $dept['label'] }}
                                                <i class="bi bi-box-arrow-up-right small text-muted"></i>
                                            </a>
                                        </td>
                                        <td class="text-end">GH₵{{ number_format($dept['total'], 2) }}</td>
                                        <td class="text-end">{{ number_format($dept['percentage'], 1) }}%</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endcanany

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><strong>Recent Payments</strong></div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Patient</th><th>Method</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('M d') }}</td>
                                <td>{{ $payment->patient?->full_name ?? '—' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? '')) }}</td>
                                <td class="text-end">GH₵{{ number_format($payment->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No recent payments</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><strong>Recent Invoices</strong></div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Invoice</th><th>Patient</th><th>Status</th><th class="text-end">Balance</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                                <td>{{ $invoice->patient?->full_name ?? '—' }}</td>
                                <td><span class="badge bg-secondary">{{ $invoice->payment_status ?? $invoice->status }}</span></td>
                                <td class="text-end">GH₵{{ number_format($invoice->balance_amount ?? 0, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No recent invoices</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
