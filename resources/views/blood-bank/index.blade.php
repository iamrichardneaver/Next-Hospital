@extends('layouts.app')

@section('title', 'Blood Bank')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-droplet-fill me-2"></i>Blood Bank</h1>
        <div class="d-flex gap-2">
            @if($tab === 'donations' || !request('tab'))
            <a href="{{ route('blood-bank.donations.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Record Donation</a>
            @elseif($tab === 'transfusions')
            <a href="{{ route('blood-bank.transfusions.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Transfusion</a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Donations</small><h4>{{ $statistics['donations'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Available Units</small><h4>{{ number_format($statistics['inventory_units'], 0) }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Transfusions</small><h4>{{ $statistics['transfusions'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Low Stock Groups</small><h4>{{ $statistics['low_stock'] }}</h4></div></div></div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link {{ $tab === 'donations' ? 'active' : '' }}" href="?tab=donations">Donations</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'inventory' ? 'active' : '' }}" href="?tab=inventory">Inventory</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'transfusions' ? 'active' : '' }}" href="?tab=transfusions">Transfusions</a></li>
    </ul>

    @if($tab === 'inventory')
        <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Blood Group</th><th>Component</th><th>Available</th><th>Reserved</th><th>Min Level</th><th></th></tr></thead><tbody>
            @forelse($inventory as $item)
                <tr><td>{{ $item->blood_group }}</td><td>{{ $item->blood_component ?? '-' }}</td><td>{{ $item->available_units }}</td><td>{{ $item->reserved_units }}</td><td>{{ $item->minimum_stock_level }}</td><td><a href="{{ route('blood-bank.inventory.show', $item) }}" class="btn btn-sm btn-outline-primary">View</a></td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No inventory records.</td></tr>
            @endforelse
        </tbody></table></div></div>
    @elseif($tab === 'transfusions')
        <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Patient</th><th>Blood Group</th><th>Units</th><th>Status</th><th></th></tr></thead><tbody>
            @forelse($transfusions as $tx)
                <tr><td>{{ optional($tx->transfusion_date)->format('Y-m-d') ?? '-' }}</td><td>{{ $tx->patient?->full_name ?? '-' }}</td><td>{{ $tx->blood_group_patient ?? '-' }}</td><td>{{ $tx->volume_ml ?? '-' }}</td><td>{{ $tx->status ?? '-' }}</td><td><a href="{{ route('blood-bank.transfusions.show', $tx) }}" class="btn btn-sm btn-outline-primary">View</a></td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No transfusion records.</td></tr>
            @endforelse
        </tbody></table></div></div>
    @else
        <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Donor</th><th>Blood Group</th><th>Volume (ml)</th><th>Status</th><th></th></tr></thead><tbody>
            @forelse($donations as $donation)
                <tr><td>{{ optional($donation->donation_date)->format('Y-m-d') ?? '-' }}</td><td>{{ $donation->donor_name ?? $donation->donor?->full_name ?? '-' }}</td><td>{{ $donation->blood_group }}</td><td>{{ $donation->volume_ml }}</td><td>{{ $donation->status ?? '-' }}</td><td><a href="{{ route('blood-bank.donations.show', $donation) }}" class="btn btn-sm btn-outline-primary">View</a></td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No donation records.</td></tr>
            @endforelse
        </tbody></table></div></div>
    @endif
</div>
@endsection
