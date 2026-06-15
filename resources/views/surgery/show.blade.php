@extends('layouts.app')

@section('title', 'Surgery Details')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-scissors me-2"></i>{{ $surgery->surgery_number ?? 'Surgery #' . $surgery->id }}</h1>
            <p class="text-muted mb-0">{{ $surgery->procedure?->name ?? 'Procedure' }} — {{ optional($surgery->surgery_date)->format('Y-m-d') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('surgery.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            @can('edit_surgery_schedules')
            <a href="{{ route('surgery.edit', $surgery) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Status</small><h5><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $surgery->status)) }}</span></h5></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Priority</small><h5>{{ ucfirst($surgery->priority ?? 'elective') }}</h5></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Theatre</small><h5>{{ $surgery->theatre?->name ?? '—' }}</h5></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Duration</small><h5>{{ $surgery->estimated_duration }} mins</h5></div></div></div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><strong>Surgery Information</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Patient</dt><dd class="col-sm-8">{{ $surgery->patient?->full_name ?? '—' }} ({{ $surgery->patient?->patient_number }})</dd>
                        <dt class="col-sm-4">Procedure</dt><dd class="col-sm-8">{{ $surgery->procedure?->name ?? '—' }}</dd>
                        <dt class="col-sm-4">Lead Surgeon</dt><dd class="col-sm-8">{{ $surgery->surgeon?->name ?? '—' }}</dd>
                        <dt class="col-sm-4">Date & Time</dt><dd class="col-sm-8">{{ optional($surgery->surgery_date)->format('Y-m-d') }} {{ $surgery->surgery_time ? \Carbon\Carbon::parse($surgery->surgery_time)->format('H:i') : '' }}</dd>
                        <dt class="col-sm-4">Anesthesia</dt><dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $surgery->anesthesia_type ?? '—')) }}</dd>
                        <dt class="col-sm-4">Surgery Type</dt><dd class="col-sm-8">{{ ucfirst($surgery->surgery_type ?? '—') }}</dd>
                        @if($surgery->notes)
                        <dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $surgery->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if($surgery->team->isNotEmpty())
            <div class="card shadow-sm mb-4">
                <div class="card-header"><strong>Surgical Team</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Role</th><th>Staff Member</th></tr></thead>
                        <tbody>
                            @foreach($surgery->team as $member)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $member->role)) }}</td>
                                    <td>{{ $member->user?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($surgery->status === 'scheduled')
            @can('edit_surgery_schedules')
            <form action="{{ route('surgery.start', $surgery) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Start this surgery?')"><i class="bi bi-play-fill"></i> Start Surgery</button>
            </form>
            @endcan
            @elseif($surgery->status === 'in_progress')
            @can('edit_surgery_schedules')
            <div class="card shadow-sm">
                <div class="card-header"><strong>Complete Surgery</strong></div>
                <div class="card-body">
                    <form action="{{ route('surgery.complete', $surgery) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Post-Op Notes</label>
                            <textarea name="post_op_notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Actual Duration (mins)</label>
                            <input type="number" name="actual_duration" class="form-control" min="1">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Mark Completed</button>
                    </form>
                </div>
            </div>
            @endcan
            @endif
        </div>
    </div>
</div>
@endsection
