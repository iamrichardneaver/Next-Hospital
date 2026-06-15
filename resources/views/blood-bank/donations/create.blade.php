@extends('layouts.app')

@section('title', 'Record Donation')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Record Blood Donation</h1>
    <div class="col-lg-8"><div class="card"><div class="card-body">
        <form action="{{ route('blood-bank.donations.store') }}" method="POST">@csrf
            <div class="mb-3"><label class="form-label">Registered Donor (optional)</label>
                <select name="donor_id" class="form-select"><option value="">Walk-in donor</option>@foreach($patients as $p)<option value="{{ $p->id }}">{{ $p->full_name }}</option>@endforeach</select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Donor Name *</label><input name="donor_name" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input name="donor_phone" class="form-control"></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Blood Group *</label>
                    <select name="blood_group" class="form-select" required>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)<option>{{ $bg }}</option>@endforeach</select>
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">Volume (ml) *</label><input type="number" name="volume_ml" class="form-control" value="450" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Date *</label><input type="date" name="donation_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Blood Bag Number</label><input name="blood_bag_number" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Screening Notes</label><textarea name="screening_notes" class="form-control" rows="2"></textarea></div>
            <button class="btn btn-primary">Save Donation</button>
            <a href="{{ route('blood-bank.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div></div></div>
</div>
@endsection
