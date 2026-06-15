<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Patient Report - {{ $patient->first_name }} {{ $patient->last_name }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }
        
        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 14pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        /* Header styles are now in the print-header component */
        
        .patient-info {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .patient-info h3 {
            margin: 0 0 10px 0;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            font-size: 14pt;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        
        .info-row {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #495057;
            display: inline-block;
            width: 120px;
        }
        
        .info-value {
            color: #212529;
        }
        
        .section-title {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 14pt;
            font-weight: bold;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            margin-top: 25px;
            margin-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .consultation-item {
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: #fff;
        }
        
        .consultation-header {
            font-weight: bold;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            margin-bottom: 8px;
            font-size: 14pt;
        }
        
        .consultation-details {
            font-size: 9pt;
            color: #666;
            margin-bottom: 10px;
        }
        
        .consultation-content {
            font-size: 14pt;
            line-height: 1.4;
        }
        
        .lab-result-item {
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: #fff;
        }
        
        .lab-result-header {
            font-weight: bold;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            margin-bottom: 8px;
            font-size: 14pt;
        }
        
        .lab-result-details {
            font-size: 9pt;
            color: #666;
            margin-bottom: 10px;
        }
        
        .lab-result-content {
            font-size: 14pt;
            line-height: 1.4;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
            color: #6c757d;
            text-align: center;
        }
        
        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    @include('components.print-header')
    
    <!-- Report Information -->
    <div class="report-info-right">
        <div style="font-size: 9pt; color: #666; text-align: right;">
            <strong>Report Date:</strong> {{ $generatedAt ?? now()->format('d/m/Y H:i') }}<br>
            <strong>Report Type:</strong> Patient Medical Report<br>
            <strong>Report ID:</strong> {{ 'PAT_' . $patient->id . '_' . now()->format('YmdHis') }}
        </div>
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
        <h3>PATIENT INFORMATION</h3>
        <div class="info-grid">
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Patient Name:</span>
                    <span class="info-value">{{ $patient->first_name }} {{ $patient->last_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Patient ID:</span>
                    <span class="info-value">{{ $patient->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Birth:</span>
                    <span class="info-value">{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d/m/Y') : 'Not provided' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Age:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($patient->date_of_birth)->age }} years</span>
                </div>
            </div>
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Gender:</span>
                    <span class="info-value">{{ $patient->gender }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact:</span>
                    <span class="info-value">{{ $patient->phone ?? $patient->contact ?? 'Not provided' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value">{{ $patient->address ?? 'Not provided' }}</span>
                </div>
                @if($patient->nhis_number)
                    <div class="info-row">
                        <span class="info-label">NHIS Number:</span>
                        <span class="info-value">{{ $patient->nhis_number }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Consultations Section -->
    @if(isset($consultations) && count($consultations) > 0)
        <div class="section-title">MEDICAL CONSULTATIONS</div>
        @foreach($consultations as $consultation)
            <div class="consultation-item">
                <div class="consultation-header">
                    Consultation #{{ $consultation->id }} - {{ \Carbon\Carbon::parse($consultation->created_at)->format('d/m/Y H:i') }}
                </div>
                <div class="consultation-details">
                    Doctor: {{ $consultation->doctor->first_name ?? '' }} {{ $consultation->doctor->last_name ?? '' }} | 
                    Type: {{ ucfirst($consultation->consultation_type ?? 'General') }}
                </div>
                <div class="consultation-content">
                    @if($consultation->chief_complaint)
                        <strong>Chief Complaint:</strong> {{ $consultation->chief_complaint }}<br><br>
                    @endif
                    @if($consultation->history_of_present_illness)
                        <strong>History:</strong> {{ $consultation->history_of_present_illness }}<br><br>
                    @endif
                    @if($consultation->clinical_findings)
                        <strong>Clinical Findings:</strong> {{ $consultation->clinical_findings }}<br><br>
                    @endif
                    @if($consultation->diagnosis)
                        <strong>Diagnosis:</strong> {{ $consultation->diagnosis }}<br><br>
                    @endif
                    @if($consultation->treatment_plan)
                        <strong>Treatment Plan:</strong> {{ $consultation->treatment_plan }}
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="section-title">MEDICAL CONSULTATIONS</div>
        <div class="no-data">No consultation records found for this patient.</div>
    @endif

    <!-- Lab Results Section -->
    @if(isset($labResults) && count($labResults) > 0)
        <div class="section-title">LABORATORY RESULTS</div>
        @foreach($labResults as $result)
            <div class="lab-result-item">
                <div class="lab-result-header">
                    {{ $result->test_name ?? 'Lab Test' }} - {{ \Carbon\Carbon::parse($result->created_at)->format('d/m/Y H:i') }}
                </div>
                <div class="lab-result-details">
                    Request #: {{ $result->request_number ?? $result->id }} | 
                    Status: {{ ucfirst($result->status ?? 'Completed') }}
                </div>
                <div class="lab-result-content">
                    @if($result->results)
                        @foreach($result->results as $testResult)
                            <strong>{{ $testResult->parameter_name ?? 'Parameter' }}:</strong> 
                            {{ $testResult->result_value ?? 'Pending' }}
                            @if($testResult->unit)
                                {{ $testResult->unit }}
                            @endif
                            @if($testResult->reference_range)
                                (Reference: {{ $testResult->reference_range }})
                            @endif
                            <br>
                        @endforeach
                    @endif
                    @if($result->clinical_interpretation)
                        <br><strong>Clinical Interpretation:</strong> {{ $result->clinical_interpretation }}
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="section-title">LABORATORY RESULTS</div>
        <div class="no-data">No laboratory results found for this patient.</div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div>
            <strong>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}</strong><br>
            {{ $branding['business_address'] ?? $settings['hospital_address'] ?? 'Hospital Address' }} | 
            Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? 'Phone Number' }} | 
            Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'Email Address' }}<br>
            <em>This report was generated on {{ $generatedAt ?? now()->format('d/m/Y H:i') }} and contains confidential medical information.</em>
        </div>
    </div>
</body>
</html>