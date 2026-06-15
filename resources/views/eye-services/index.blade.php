@extends('layouts.app')

@section('title', 'Eye Services')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-eye me-2"></i>Eye Services</h1>
            <p class="text-muted mb-0">Catalog of ophthalmology services and pricing.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Total</small><h4>{{ $statistics['total'] }}</h4></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Active</small><h4>{{ $statistics['active'] }}</h4></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">NHIS Covered</small><h4>{{ $statistics['nhis_covered'] }}</h4></div></div></div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Service</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>NHIS</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        <tr>
                            <td>{{ $service->service_code }}</td>
                            <td>{{ $service->service_name }}</td>
                            <td>{{ $service->category ?? '-' }}</td>
                            <td>{{ $service->currency ?? 'GHS' }} {{ number_format($service->base_price ?? 0, 2) }}</td>
                            <td>{{ $service->nhis_covered ? 'Yes' : 'No' }}</td>
                            <td><span class="badge bg-{{ $service->is_active ? 'success' : 'secondary' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td><a href="{{ route('eye-services.show', $service) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No eye services configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($services->hasPages())
            <div class="card-footer">{{ $services->links() }}</div>
        @endif
    </div>
</div>
@endsection
