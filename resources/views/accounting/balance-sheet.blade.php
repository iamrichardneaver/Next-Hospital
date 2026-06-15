@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-clipboard-data me-2"></i>Balance Sheet
            </h1>
            <p class="text-secondary mb-0">Simplified hospital financial position as of {{ \Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            @include('accounting.partials.export-buttons')
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Print</button>
            <a href="{{ route('accounting.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Hub</a>
        </div>
    </div>

    @include('accounting.partials.filters', ['branches' => $branches, 'branchId' => $branchId, 'showAsOf' => true, 'asOfDate' => $asOfDate])

    <div class="row" id="balance-sheet-print">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white"><strong>Assets</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            <tr>
                                <td>Cash & Cash Equivalents</td>
                                <td class="text-end fw-semibold">GH₵{{ number_format($balanceSheet['assets']['cash'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Accounts Receivable</td>
                                <td class="text-end fw-semibold">GH₵{{ number_format($balanceSheet['assets']['accounts_receivable'], 2) }}</td>
                            </tr>
                            <tr class="table-light">
                                <th>Total Assets</th>
                                <th class="text-end">GH₵{{ number_format($balanceSheet['assets']['total_assets'], 2) }}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white"><strong>Liabilities & Equity</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            <tr class="table-light"><td colspan="2"><em>Liabilities</em></td></tr>
                            <tr>
                                <td>Accounts Payable (Expenses)</td>
                                <td class="text-end">GH₵{{ number_format($balanceSheet['liabilities']['accounts_payable'], 2) }}</td>
                            </tr>
                            <tr class="table-light">
                                <th>Total Liabilities</th>
                                <th class="text-end">GH₵{{ number_format($balanceSheet['liabilities']['total_liabilities'], 2) }}</th>
                            </tr>
                            <tr class="table-light"><td colspan="2"><em>Equity</em></td></tr>
                            <tr>
                                <td>Retained Earnings (Revenue − Expenses)</td>
                                <td class="text-end">GH₵{{ number_format($balanceSheet['equity']['retained_earnings'], 2) }}</td>
                            </tr>
                            <tr class="table-light">
                                <th>Total Equity</th>
                                <th class="text-end">GH₵{{ number_format($balanceSheet['equity']['total_equity'], 2) }}</th>
                            </tr>
                            <tr class="table-primary">
                                <th>Total Liabilities & Equity</th>
                                <th class="text-end">GH₵{{ number_format($balanceSheet['totals']['total_liabilities_equity'], 2) }}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($balanceSheet['totals']['balanced'])
        <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> Assets equal liabilities plus equity.</div>
    @else
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i> Simplified balance — minor variance may occur without full general ledger.</div>
    @endif

    <div class="card">
        <div class="card-body small text-muted">
            <ul class="mb-0">
                @foreach($balanceSheet['notes'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    .sidebar, .navbar, .btn, form, .alert-warning { display: none !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
}
</style>
@endpush
