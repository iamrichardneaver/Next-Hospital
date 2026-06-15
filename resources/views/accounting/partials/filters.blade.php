@php
    $showPeriod = $showPeriod ?? false;
    $showAsOf = $showAsOf ?? false;
@endphp
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            @if($showAsOf)
            <div class="col-md-3">
                <label class="form-label small text-muted">As of Date</label>
                <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate ?? now()->toDateString() }}">
            </div>
            @else
            <div class="col-md-3">
                <label class="form-label small text-muted">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate ?? now()->startOfMonth()->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate ?? now()->toDateString() }}">
            </div>
            @endif
            @if(auth()->user()->hasRole('super_admin') && isset($branches))
            <div class="col-md-3">
                <label class="form-label small text-muted">Branch</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string)($branchId ?? '') === (string)$branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($showPeriod)
            <div class="col-md-2">
                <label class="form-label small text-muted">Grouping</label>
                <select name="period" class="form-select form-select-sm">
                    <option value="monthly" {{ ($period ?? 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly" {{ ($period ?? '') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                </select>
            </div>
            @endif
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>
