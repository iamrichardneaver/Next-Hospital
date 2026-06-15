@extends('layouts.app')

@section('title', 'Discharge from ICU')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Discharge {{ $icu->patient?->full_name }}</h1>
    <div class="col-lg-6">
        <div class="card shadow-sm"><div class="card-body">
            <form action="{{ route('icu.discharge', $icu) }}" method="POST">@csrf
                <div class="mb-3"><label class="form-label">Discharge Time</label><input type="datetime-local" name="discharge_time" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                <div class="mb-3"><label class="form-label">Destination</label><input type="text" name="discharge_destination" class="form-control" placeholder="Ward, home, transfer..."></div>
                <div class="mb-3"><label class="form-label">Discharge Notes</label><textarea name="discharge_notes" class="form-control" rows="4"></textarea></div>
                <button class="btn btn-success">Confirm Discharge</button>
                <a href="{{ route('icu.show', $icu) }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div></div>
    </div>
</div>
@endsection
