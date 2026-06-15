@extends('layouts.app')

@section('title', 'New Transfusion Order')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Create Transfusion Order</h1>
    <div class="col-lg-8"><div class="card"><div class="card-body">
        <form action="{{ route('blood-bank.transfusions.store') }}" method="POST">@csrf
            <div class="mb-3"><label class="form-label">Patient *</label>
                <select name="patient_id" class="form-select" required>@foreach($patients as $p)<option value="{{ $p->id }}">{{ $p->full_name }}</option>@endforeach</select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Patient Blood Group *</label>
                    <select name="blood_group_patient" class="form-select" required>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)<option>{{ $bg }}</option>@endforeach</select>
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">Donor Blood Group *</label>
                    <select name="blood_group_donor" class="form-select" required>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)<option>{{ $bg }}</option>@endforeach</select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Component *</label>
                    <select name="blood_component" class="form-select" required>@foreach(['whole_blood','packed_cells','plasma','platelets','cryoprecipitate'] as $c)<option value="{{ $c }}">{{ str_replace('_',' ', ucfirst($c)) }}</option>@endforeach</select>
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">Volume (ml) *</label><input type="number" name="volume_ml" class="form-control" value="450" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Ordering Doctor *</label>
                <select name="doctor_id" class="form-select" required>@foreach($doctors as $d)<option value="{{ $d->id }}">Dr. {{ $d->name }}</option>@endforeach</select>
            </div>
            <div class="mb-3"><label class="form-label">Indication</label><textarea name="indication" class="form-control" rows="2"></textarea></div>
            <button class="btn btn-primary">Create Order</button>
            <a href="{{ route('blood-bank.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div></div></div>
</div>
@endsection
