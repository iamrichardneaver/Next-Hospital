@extends('layouts.app')

@section('title', 'Update Transfusion')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Update Transfusion</h1>
    <div class="col-lg-6"><div class="card"><div class="card-body">
        <form action="{{ route('blood-bank.transfusions.update', $transfusion) }}" method="POST">@csrf @method('PUT')
            <div class="mb-3"><label class="form-label">Status</label>
                <select name="status" class="form-select">@foreach(['pending','completed','cancelled'] as $s)<option value="{{ $s }}" @selected($transfusion->status===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
            </div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2">{{ $transfusion->notes }}</textarea></div>
            <div class="mb-3"><label class="form-label">Adverse Reactions</label><textarea name="adverse_reactions" class="form-control" rows="2">{{ $transfusion->adverse_reactions }}</textarea></div>
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('blood-bank.transfusions.show', $transfusion) }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div></div></div>
</div>
@endsection
