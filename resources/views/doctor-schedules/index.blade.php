@extends('layouts.app')

@section('title', 'My Schedule' . (auth()->user()->hasRole('doctor') ? '' : ' - Doctor Schedules'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">{{ auth()->user()->hasRole('doctor') ? 'My Schedule' : 'Doctor Schedules' }}</h2>
                    <p class="text-muted mb-0">Manage your weekly availability for appointments</p>
                </div>
                <a href="{{ route('doctor-schedules.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add Schedule
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters (only for admins) -->
    @if(!auth()->user()->hasRole('doctor'))
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('doctor-schedules.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">All Doctors</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                {{ $doctor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Day of Week</label>
                    <select name="day_of_week" class="form-select">
                        <option value="">All Days</option>
                        <option value="monday" {{ request('day_of_week') == 'monday' ? 'selected' : '' }}>Monday</option>
                        <option value="tuesday" {{ request('day_of_week') == 'tuesday' ? 'selected' : '' }}>Tuesday</option>
                        <option value="wednesday" {{ request('day_of_week') == 'wednesday' ? 'selected' : '' }}>Wednesday</option>
                        <option value="thursday" {{ request('day_of_week') == 'thursday' ? 'selected' : '' }}>Thursday</option>
                        <option value="friday" {{ request('day_of_week') == 'friday' ? 'selected' : '' }}>Friday</option>
                        <option value="saturday" {{ request('day_of_week') == 'saturday' ? 'selected' : '' }}>Saturday</option>
                        <option value="sunday" {{ request('day_of_week') == 'sunday' ? 'selected' : '' }}>Sunday</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Availability</label>
                    <select name="is_available" class="form-select">
                        <option value="">All</option>
                        <option value="1" {{ request('is_available') === '1' ? 'selected' : '' }}>Available</option>
                        <option value="0" {{ request('is_available') === '0' ? 'selected' : '' }}>Unavailable</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary me-2">Filter</button>
                    <a href="{{ route('doctor-schedules.index') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Schedules Table -->
    <div class="card">
        <div class="card-body">
            @if($schedules->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            @if(!auth()->user()->hasRole('doctor'))
                            <th>Doctor</th>
                            @endif
                            <th>Day</th>
                            <th>Time</th>
                            <th>Break</th>
                            <th>Branch</th>
                            <th>Slot Duration</th>
                            <th>Max Appointments</th>
                            <th>Status</th>
                            <th>Effective Period</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $schedule)
                        <tr>
                            @if(!auth()->user()->hasRole('doctor'))
                            <td>{{ $schedule->doctor->name }}</td>
                            @endif
                            <td>
                                <span class="badge bg-primary">{{ ucfirst($schedule->day_of_week) }}</span>
                            </td>
                            <td>
                                <strong>{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}</strong> - 
                                <strong>{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</strong>
                            </td>
                            <td>
                                @if($schedule->break_start_time && $schedule->break_end_time)
                                    {{ \Carbon\Carbon::parse($schedule->break_start_time)->format('H:i') }} - 
                                    {{ \Carbon\Carbon::parse($schedule->break_end_time)->format('H:i') }}
                                @else
                                    <span class="text-muted">No break</span>
                                @endif
                            </td>
                            <td>{{ $schedule->branch->name }}</td>
                            <td>{{ $schedule->slot_duration }} min</td>
                            <td>{{ $schedule->max_appointments_per_slot }}</td>
                            <td>
                                @if($schedule->is_available)
                                    <span class="badge bg-success">Available</span>
                                @else
                                    <span class="badge bg-danger">Unavailable</span>
                                @endif
                            </td>
                            <td>
                                @if($schedule->effective_from || $schedule->effective_until)
                                    {{ $schedule->effective_from ? \Carbon\Carbon::parse($schedule->effective_from)->format('M d, Y') : 'N/A' }} - 
                                    {{ $schedule->effective_until ? \Carbon\Carbon::parse($schedule->effective_until)->format('M d, Y') : 'Ongoing' }}
                                @else
                                    <span class="text-muted">Ongoing</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('doctor-schedules.show', $schedule->id) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('doctor-schedules.edit', $schedule->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('doctor-schedules.destroy', $schedule->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $schedules->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">No schedules found. Create your first schedule to start accepting appointments.</p>
                <a href="{{ route('doctor-schedules.create') }}" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle me-2"></i>Add Schedule
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
