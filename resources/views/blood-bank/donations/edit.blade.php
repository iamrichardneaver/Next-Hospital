@extends('layouts.app')

@section('title', 'Update Donation')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Update Donation Screening</h1>
    <div class="col-lg-8"><div class="card"><div class="card-body">
        <form action="{{ route('blood-bank.donations.update', $donation) }}" method="POST">@csrf @method('PUT')
            <div class="mb-3"><label class="form-label">Status</label>
                <select name="status" class="form-select">@foreach(['pending','tested','approved','rejected','used','expired'] as $s)<option value="{{ $s }}" @selected($donation->status===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
            </div>
            @foreach(['hiv_test','hbv_test','hcv_test','syphilis_test'] as $test)
            <div class="mb-3"><label class="form-label">{{ strtoupper(str_replace('_test','',$test)) }}</label>
                <select name="{{ $test }}" class="form-select"><option value="">—</option>@foreach(['negative','positive','pending'] as $r)<option value="{{ $r }}" @selected($donation->$test===$r)>{{ ucfirst($r) }}</option>@endforeach</select>
            </div>
            @endforeach
            <div class="mb-3"><label class="form-label">Screening Notes</label><textarea name="screening_notes" class="form-control" rows="3">{{ $donation->screening_notes }}</textarea></div>
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('blood-bank.donations.show', $donation) }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div></div></div>
</div>
@endsection
