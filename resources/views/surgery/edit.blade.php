@extends('layouts.app')

@section('title', 'Edit Surgery Schedule')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-scissors me-2"></i>Edit Surgery Schedule</h1>
        <a href="{{ route('surgery.show', $surgery) }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('surgery.update', $surgery) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Procedure <span class="text-danger">*</span></label>
                            <select name="procedure_id" class="form-select" required>
                                @foreach($procedures as $procedure)
                                    <option value="{{ $procedure->id }}" @selected(old('procedure_id', $surgery->procedure_id) == $procedure->id)>{{ $procedure->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lead Surgeon</label>
                                <select name="surgeon_id" class="form-select" required>
                                    @foreach($surgeons as $surgeon)
                                        <option value="{{ $surgeon->id }}" @selected(old('surgeon_id', $surgery->surgeon_id) == $surgeon->id)>Dr. {{ $surgeon->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Theatre</label>
                                <select name="theatre_id" class="form-select" required>
                                    @foreach($theatres as $theatre)
                                        <option value="{{ $theatre->id }}" @selected(old('theatre_id', $surgery->theatre_id) == $theatre->id)>{{ $theatre->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assistant Surgeon</label>
                                <select name="assistant_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($surgeons as $surgeon)
                                        <option value="{{ $surgeon->id }}" @selected(old('assistant_id', $assistantId) == $surgeon->id)>Dr. {{ $surgeon->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Anaesthetist</label>
                                <select name="anaesthetist_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($surgeons as $surgeon)
                                        <option value="{{ $surgeon->id }}" @selected(old('anaesthetist_id', $anaesthetistId) == $surgeon->id)>Dr. {{ $surgeon->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Surgery Date</label>
                                <input type="date" name="surgery_date" class="form-control" value="{{ old('surgery_date', optional($surgery->surgery_date)->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Surgery Time</label>
                                <input type="time" name="surgery_time" class="form-control" value="{{ old('surgery_time', $surgery->surgery_time ? \Carbon\Carbon::parse($surgery->surgery_time)->format('H:i') : '') }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Duration (mins)</label>
                                <input type="number" name="estimated_duration" class="form-control" value="{{ old('estimated_duration', $surgery->estimated_duration) }}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    @foreach(['elective','urgent','emergency'] as $p)
                                        <option value="{{ $p }}" @selected(old('priority', $surgery->priority) === $p)>{{ ucfirst($p) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Surgery Type</label>
                                <select name="surgery_type" class="form-select">
                                    @foreach(['major','minor','diagnostic','therapeutic'] as $t)
                                        <option value="{{ $t }}" @selected(old('surgery_type', $surgery->surgery_type) === $t)>{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    @foreach(['scheduled','in_progress','completed','cancelled','postponed'] as $s)
                                        <option value="{{ $s }}" @selected(old('status', $surgery->status) === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Anesthesia Type</label>
                            <select name="anesthesia_type" class="form-select">
                                @foreach(['general','regional','local','conscious_sedation'] as $a)
                                    <option value="{{ $a }}" @selected(old('anesthesia_type', $surgery->anesthesia_type) === $a)>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $surgery->notes) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            @can('delete_surgery_schedules')
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="bi bi-trash"></i> Delete</button>
                            @else
                            <span></span>
                            @endcan
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@can('delete_surgery_schedules')
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('surgery.destroy', $surgery) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header"><h5 class="modal-title">Delete Surgery Schedule</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">Are you sure you want to delete this surgery schedule?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection
