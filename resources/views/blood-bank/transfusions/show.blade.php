@extends('layouts.app')

@section('title', 'Transfusion Details')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between mb-4">
        <h1 class="h3 mb-0">Transfusion #{{ $transfusion->id }}</h1>
        <div><a href="{{ route('blood-bank.index', ['tab'=>'transfusions']) }}" class="btn btn-secondary">Back</a>
        <a href="{{ route('blood-bank.transfusions.edit', $transfusion) }}" class="btn btn-warning">Update</a></div>
    </div>
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Patient</dt><dd class="col-sm-9">{{ $transfusion->patient?->full_name }}</dd>
            <dt class="col-sm-3">Blood Groups</dt><dd class="col-sm-9">Patient: {{ $transfusion->blood_group_patient }} / Donor: {{ $transfusion->blood_group_donor }}</dd>
            <dt class="col-sm-3">Component</dt><dd class="col-sm-9">{{ str_replace('_',' ', $transfusion->blood_component) }}</dd>
            <dt class="col-sm-3">Volume</dt><dd class="col-sm-9">{{ $transfusion->volume_ml }} ml</dd>
            <dt class="col-sm-3">Doctor</dt><dd class="col-sm-9">{{ $transfusion->doctor?->name }}</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($transfusion->status) }}</dd>
            <dt class="col-sm-3">Indication</dt><dd class="col-sm-9">{{ $transfusion->indication ?? '—' }}</dd>
        </dl>
    </div></div>
</div>
@endsection
