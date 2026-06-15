@extends('layouts.app')

@section('title', 'Teleconsultations Debug')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Teleconsultations Debug</h1>
                </div>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Debug Information</span>
                    </h3>
                </div>
                <div class="card-body py-3">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Teleconsultations Data:</h4>
                            <p><strong>Total Count:</strong> {{ $teleconsultations->total() }}</p>
                            <p><strong>Current Page:</strong> {{ $teleconsultations->currentPage() }}</p>
                            <p><strong>Per Page:</strong> {{ $teleconsultations->perPage() }}</p>
                            
                            @if($teleconsultations->count() > 0)
                                <h5>First Teleconsultation:</h5>
                                <ul>
                                    <li><strong>ID:</strong> {{ $teleconsultations->first()->id }}</li>
                                    <li><strong>Patient:</strong> {{ $teleconsultations->first()->patient ? $teleconsultations->first()->patient->first_name . ' ' . $teleconsultations->first()->patient->last_name : 'NULL' }}</li>
                                    <li><strong>Doctor:</strong> {{ $teleconsultations->first()->doctor ? $teleconsultations->first()->doctor->first_name . ' ' . $teleconsultations->first()->doctor->last_name : 'NULL' }}</li>
                                    <li><strong>Scheduled At:</strong> {{ $teleconsultations->first()->scheduled_at ? $teleconsultations->first()->scheduled_at->format('Y-m-d H:i:s') : 'NULL' }}</li>
                                    <li><strong>Status:</strong> {{ $teleconsultations->first()->status }}</li>
                                    <li><strong>Consultation Type:</strong> {{ $teleconsultations->first()->consultation_type }}</li>
                                </ul>
                            @else
                                <p>No teleconsultations found</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h4>Doctors Data:</h4>
                            <p><strong>Total Doctors:</strong> {{ $doctors->count() }}</p>
                            @if($doctors->count() > 0)
                                <ul>
                                    @foreach($doctors->take(3) as $doctor)
                                        <li>{{ $doctor->first_name }} {{ $doctor->last_name }} ({{ $doctor->email }})</li>
                                    @endforeach
                                </ul>
                            @endif
                            
                            <h4>Patients Data:</h4>
                            <p><strong>Total Patients:</strong> {{ $patients->count() }}</p>
                            @if($patients->count() > 0)
                                <ul>
                                    @foreach($patients->take(3) as $patient)
                                        <li>{{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->phone }})</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
