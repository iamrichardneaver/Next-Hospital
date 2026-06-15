@extends('layouts.app')

@section('title', 'Start Stock Count')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Start Stock Count</h1>
    <div class="col-lg-6">
        <div class="card shadow-sm"><div class="card-body">
            <form action="{{ route('stock-count.store') }}" method="POST">@csrf
                <div class="mb-3"><label class="form-label">Department</label>
                    <select name="department" class="form-select" required>
                        <option value="pharmacy">Pharmacy</option>
                        <option value="lab">Lab</option>
                        <option value="radiology">Radiology</option>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
                <button class="btn btn-primary">Create Draft Count</button>
                <a href="{{ route('stock-count.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div></div>
    </div>
</div>
@endsection
