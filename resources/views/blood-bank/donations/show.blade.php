@extends('layouts.app')

@section('title', 'Donation Details')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between mb-4">
        <h1 class="h3 mb-0">Donation {{ $donation->donation_id ?? '#'.$donation->id }}</h1>
        <div><a href="{{ route('blood-bank.index', ['tab'=>'donations']) }}" class="btn btn-secondary">Back</a>
        <a href="{{ route('blood-bank.donations.edit', $donation) }}" class="btn btn-warning">Edit / Screen</a></div>
    </div>
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Donor</dt><dd class="col-sm-9">{{ $donation->donor_name ?? $donation->donor?->full_name }}</dd>
            <dt class="col-sm-3">Blood Group</dt><dd class="col-sm-9">{{ $donation->blood_group }}</dd>
            <dt class="col-sm-3">Volume</dt><dd class="col-sm-9">{{ $donation->volume_ml }} ml</dd>
            <dt class="col-sm-3">Date</dt><dd class="col-sm-9">{{ optional($donation->donation_date)->format('Y-m-d') }}</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($donation->status) }}</dd>
            <dt class="col-sm-3">HIV/HBV/HCV/Syphilis</dt><dd class="col-sm-9">{{ $donation->hiv_test ?? '—' }} / {{ $donation->hbv_test ?? '—' }} / {{ $donation->hcv_test ?? '—' }} / {{ $donation->syphilis_test ?? '—' }}</dd>
        </dl>
    </div></div>
</div>
@endsection
