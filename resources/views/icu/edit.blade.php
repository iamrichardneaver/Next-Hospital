@extends('layouts.app')

@section('title', 'Edit ICU Record')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Edit ICU Record</h1>
    <div class="col-lg-8">
        <div class="card shadow-sm"><div class="card-body">
            <form action="{{ route('icu.update', $icu) }}" method="POST">@csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Attending Doctor</label>
                        <select name="attending_doctor_id" class="form-select" required>@foreach($doctors as $d)<option value="{{ $d->id }}" @selected($icu->attending_doctor_id == $d->id)>Dr. {{ $d->first_name }} {{ $d->last_name }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Assigned Nurse</label>
                        <select name="assigned_nurse_id" class="form-select"><option value="">None</option>@foreach($nurses as $n)<option value="{{ $n->id }}" @selected($icu->assigned_nurse_id == $n->id)>{{ $n->first_name }} {{ $n->last_name }}</option>@endforeach</select>
                    </div>
                </div>
                <div class="mb-3"><label class="form-label">ICU Bed</label>
                    <select name="bed_id" class="form-select"><option value="">Unassigned</option>@foreach($beds as $bed)<option value="{{ $bed->id }}" @selected($icu->bed_id == $bed->id)>{{ $bed->ward?->name }} — Bed {{ $bed->bed_number }}</option>@endforeach</select>
                </div>
                <div class="mb-3"><label class="form-label">Patient Condition</label>
                    <select name="patient_condition" class="form-select">@foreach(['stable','serious','critical'] as $c)<option value="{{ $c }}" @selected($icu->patient_condition === $c)>{{ ucfirst($c) }}</option>@endforeach</select>
                </div>
                <div class="form-check mb-3"><input type="checkbox" name="on_ventilator" value="1" class="form-check-input" id="vent" @checked($icu->on_ventilator)><label class="form-check-label" for="vent">On Ventilator</label></div>
                <div class="mb-3"><label class="form-label">Admission Diagnosis</label><textarea name="admission_diagnosis" class="form-control" rows="2">{{ $icu->admission_diagnosis }}</textarea></div>
                <div class="mb-3"><label class="form-label">Doctor Notes</label><textarea name="doctor_notes" class="form-control" rows="2">{{ $icu->doctor_notes }}</textarea></div>
                <button class="btn btn-primary">Update</button>
                <a href="{{ route('icu.show', $icu) }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div></div>
    </div>
</div>
@endsection
