@extends('layouts.app')

@section('title', 'Sync Permissions - System Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Sync Permissions</h1>
            <p class="text-secondary mb-0">Upsert permission records from <code>config/permissions.php</code> and codebase discovery</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Settings
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start">
            <i class="bi bi-info-circle-fill fs-4 me-3 mt-1"></i>
            <div>
                <h5 class="alert-heading mb-2">What this does</h5>
                <ul class="mb-0 small">
                    <li>Reads the canonical registry from <code>config/permissions.php</code> and scans routes, controllers, views, and seeders for permission names.</li>
                    <li>Creates any missing rows in the <code>permissions</code> table using <code>firstOrCreate</code> — safe to run repeatedly.</li>
                    <li>Does <strong>not</strong> remove permissions or change role/user assignments.</li>
                    <li>Equivalent to running <code>php artisan permissions:sync</code> on the server.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-list-check"></i></div>
                <div class="stat-label">Registry Total</div>
                <div class="stat-value">{{ $registryTotal }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-file-earmark-code"></i></div>
                <div class="stat-label">From Config</div>
                <div class="stat-value">{{ $configTotal }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-search"></i></div>
                <div class="stat-label">Discovered in Code</div>
                <div class="stat-value">{{ $discoveredTotal }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-database"></i></div>
                <div class="stat-label">In Database</div>
                <div class="stat-value">{{ $dbTotal }}</div>
            </div>
        </div>
    </div>

    @if($configDuplicates !== [])
    <div class="alert alert-warning mb-4">
        <div class="d-flex align-items-start">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-3 mt-1"></i>
            <div>
                <h6 class="mb-2">Config duplicate names ({{ count($configDuplicates) }})</h6>
                <p class="small mb-2">These permission names appear in more than one config module. Sync uses the first module entry and will not create duplicate database rows.</p>
                <ul class="small mb-0">
                    @foreach(array_slice($configDuplicates, 0, 8, true) as $name => $modules)
                    <li><code>{{ $name }}</code> — {{ implode(', ', $modules) }}</li>
                    @endforeach
                    @if(count($configDuplicates) > 8)
                    <li class="text-muted">…and {{ count($configDuplicates) - 8 }} more</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Last Manual Sync</h5>
            <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-shield-check me-1"></i> View All Permissions
            </a>
        </div>
        <div class="card-body">
            @if($lastSync)
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="text-muted small">Synced at</div>
                    <div class="fw-semibold">{{ \Carbon\Carbon::parse($lastSync['synced_at'])->format('M j, Y g:i A') }}</div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="text-muted small">Triggered by</div>
                    <div class="fw-semibold">{{ $lastSync['user_name'] ?? 'Unknown' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Result</div>
                    <div class="fw-semibold">
                        {{ $lastSync['created'] ?? 0 }} created,
                        {{ $lastSync['existing'] ?? 0 }} already existed
                        ({{ $lastSync['total'] ?? 0 }} in registry)
                    </div>
                </div>
            </div>
            @else
            <p class="text-muted mb-0">
                No manual sync recorded yet. Permissions may still have been auto-synced on application boot.
            </p>
            @endif
        </div>
    </div>

    <div class="card border-primary">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="mb-2"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Resync from codebase</h5>
                    <p class="text-muted small mb-0">
                        Use after deploying code that adds new permissions. Existing assignments are preserved.
                    </p>
                </div>
                <form action="{{ route('settings.permissions-sync.run') }}" method="POST" id="permissionsSyncForm" class="flex-shrink-0">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-arrow-repeat me-2"></i> Resync Permissions from Codebase
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('permissionsSyncForm')?.addEventListener('submit', function (e) {
    if (!confirm('Resync permissions from config and codebase scan? This is safe to run and will not remove existing role grants.')) {
        e.preventDefault();
    }
});
</script>
@endpush
