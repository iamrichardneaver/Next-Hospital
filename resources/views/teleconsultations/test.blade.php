@extends('layouts.app')

@section('title', 'Teleconsultations Test')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Teleconsultations Test</h1>
                </div>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Test Data</span>
                    </h3>
                </div>
                <div class="card-body py-3">
                    <p><strong>Total teleconsultations:</strong> {{ $teleconsultations->total() }}</p>
                    <p><strong>Count on current page:</strong> {{ $teleconsultations->count() }}</p>
                    
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teleconsultations as $teleconsultation)
                                <tr>
                                    <td>{{ $teleconsultation->id }}</td>
                                    <td>
                                        @if($teleconsultation->patient)
                                            {{ $teleconsultation->patient->first_name }} {{ $teleconsultation->patient->last_name }}
                                        @else
                                            No Patient
                                        @endif
                                    </td>
                                    <td>
                                        @if($teleconsultation->doctor)
                                            {{ $teleconsultation->doctor->first_name }} {{ $teleconsultation->doctor->last_name }}
                                        @else
                                            No Doctor
                                        @endif
                                    </td>
                                    <td>{{ $teleconsultation->status }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-10">
                                        <div class="text-muted fs-6">No teleconsultations found.</div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
