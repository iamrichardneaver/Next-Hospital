@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-graph-up me-2"></i>Reports</h1>
            <p class="text-muted mb-0">Access financial, regulatory, clinical, and operational reports scoped to your role.</p>
        </div>
    </div>

    @forelse($reportGroups as $group)
        <div class="mb-4">
            <h2 class="h5 text-muted mb-3">
                <i class="bi bi-folder2-open me-2"></i>{{ $group['category'] }}
            </h2>
            <div class="row g-4">
                @foreach($group['reports'] as $report)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                        <i class="bi {{ $report['icon'] }} text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1">{{ $report['title'] }}</h5>
                                        <p class="card-text text-muted small mb-0">{{ $report['description'] }}</p>
                                    </div>
                                </div>
                                <div class="mt-auto">
                                    @if(!empty($report['route']))
                                        <a href="{{ route($report['route']) }}" class="btn btn-primary btn-sm">
                                            Open Report <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    @elseif(!empty($report['url']))
                                        <a href="{{ $report['url'] }}" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">
                                            Open Report <i class="bi bi-box-arrow-up-right ms-1"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="alert alert-info mb-0">No reports available for your role. Contact an administrator if you need access.</div>
    @endforelse
</div>
@endsection
