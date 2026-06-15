<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title }}</title>
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
        
        .document-title {
            font-size: 23pt;
            font-weight: bold;
            color: #2c5aa0;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .section {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        
        .section h3 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
            font-size: 14pt;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .info-row {
            margin-bottom: 8px;
            display: table;
            width: 100%;
        }
        
        .info-label {
            font-weight: bold;
            display: table-cell;
            width: 35%;
        }
        
        .info-value {
            display: table-cell;
            width: 65%;
        }
        
        .consultations-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .consultations-table th,
        .consultations-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        
        .consultations-table th {
            background-color: #2c5aa0;
            color: white;
            font-weight: bold;
            font-size: 9pt;
        }
        
        .consultations-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .consultations-table td {
            font-size: 9pt;
        }
        
        .text-content {
            line-height: 1.6;
            margin: 10px 0;
        }
        
        .signature-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        
        .signature-block {
            display: table-cell;
            width: 50%;
            padding: 10px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            text-align: center;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
        
        .highlight {
            background-color: #fff3cd;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    @include('components.print-header')
    
    <!-- Discharge Information -->
    <div class="discharge-info-right">
        <div style="font-size: 9pt; color: #666; text-align: right;">
            <strong>Discharge Date:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
            @if($branch)
                <strong>Branch:</strong> {{ $branch->name }}<br>
            @endif
            <strong>Admission #:</strong> {{ $admission->admission_number ?? 'N/A' }}
        </div>
    </div>

    <!-- Document Title -->
    <div class="document-title">
        DISCHARGE SUMMARY
    </div>

    <!-- Patient Information -->
    <div class="section">
        <h3>PATIENT INFORMATION</h3>
        <div class="info-row">
            <span class="info-label">Patient ID:</span>
            <span class="info-value">{{ $patient->patient_id }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Patient Name:</span>
            <span class="info-value">{{ $patient->firstname }} {{ $patient->lastname }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date of Birth:</span>
            <span class="info-value">{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d/m/Y') . ' (' . \Carbon\Carbon::parse($patient->date_of_birth)->age . ' years)' : 'Not provided' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Gender:</span>
            <span class="info-value">{{ $patient->gender }}</span>
        </div>
        @if($patient->contact)
        <div class="info-row">
            <span class="info-label">Contact:</span>
            <span class="info-value">{{ $patient->contact }}</span>
        </div>
        @endif
    </div>

    <!-- Admission Information -->
    <div class="section">
        <h3>ADMISSION DETAILS</h3>
        <div class="info-row">
            <span class="info-label">Admission Number:</span>
            <span class="info-value">{{ $admission->admission_number ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Admission Date:</span>
            <span class="info-value">{{ isset($admission->admission_date) ? \Carbon\Carbon::parse($admission->admission_date)->format('d/m/Y H:i') : 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Discharge Date:</span>
            <span class="info-value">{{ isset($admission->discharge_date) ? \Carbon\Carbon::parse($admission->discharge_date)->format('d/m/Y H:i') : $generated_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Length of Stay:</span>
            <span class="info-value">
                @if(isset($admission->admission_date) && isset($admission->discharge_date))
                    {{ \Carbon\Carbon::parse($admission->admission_date)->diffInDays(\Carbon\Carbon::parse($admission->discharge_date)) }} days
                @else
                    N/A
                @endif
            </span>
        </div>
        @if(isset($admission->ward))
        <div class="info-row">
            <span class="info-label">Ward/Room:</span>
            <span class="info-value">{{ $admission->ward->name ?? 'N/A' }} - Bed {{ $admission->bed->bed_number ?? 'N/A' }}</span>
        </div>
        @endif
        @if(isset($admission->attending_doctor))
        <div class="info-row">
            <span class="info-label">Attending Doctor:</span>
            <span class="info-value">Dr. {{ $admission->attending_doctor->firstname ?? '' }} {{ $admission->attending_doctor->lastname ?? '' }}</span>
        </div>
        @endif
    </div>

    <!-- Admission Reason -->
    @if(isset($admission->admission_reason) && $admission->admission_reason)
    <div class="section">
        <h3>REASON FOR ADMISSION</h3>
        <div class="text-content">
            {{ $admission->admission_reason }}
        </div>
    </div>
    @endif

    <!-- Clinical Summary / Consultations -->
    @if(count($consultations) > 0)
    <div class="section">
        <h3>CLINICAL SUMMARY</h3>
        <table class="consultations-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Doctor</th>
                    <th>Diagnosis</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($consultations as $consultation)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($consultation->consultation_date)->format('d/m/Y') }}</td>
                    <td>Dr. {{ $consultation->doctor->firstname ?? '' }} {{ $consultation->doctor->lastname ?? '' }}</td>
                    <td>{{ $consultation->diagnosis ?? '-' }}</td>
                    <td>{{ $consultation->clinical_notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Discharge Diagnosis -->
    @if(isset($admission->discharge_diagnosis) && $admission->discharge_diagnosis)
    <div class="section">
        <h3>DISCHARGE DIAGNOSIS</h3>
        <div class="text-content">
            <strong class="highlight">{{ $admission->discharge_diagnosis }}</strong>
        </div>
    </div>
    @endif

    <!-- Treatment Summary -->
    @if(isset($admission->treatment_summary) && $admission->treatment_summary)
    <div class="section">
        <h3>TREATMENT SUMMARY</h3>
        <div class="text-content">
            {{ $admission->treatment_summary }}
        </div>
    </div>
    @endif

    <!-- Discharge Medications -->
    @if(isset($admission->discharge_medications) && $admission->discharge_medications)
    <div class="section">
        <h3>DISCHARGE MEDICATIONS</h3>
        <div class="text-content">
            {{ $admission->discharge_medications }}
        </div>
    </div>
    @endif

    <!-- Follow-up Instructions -->
    @if(isset($admission->follow_up_instructions) && $admission->follow_up_instructions)
    <div class="section">
        <h3>FOLLOW-UP INSTRUCTIONS</h3>
        <div class="text-content">
            {{ $admission->follow_up_instructions }}
        </div>
    </div>
    @endif

    <!-- Discharge Condition -->
    @if(isset($admission->discharge_condition))
    <div class="section">
        <h3>CONDITION AT DISCHARGE</h3>
        <div class="text-content">
            <strong>{{ ucfirst($admission->discharge_condition) }}</strong>
        </div>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div style="display: table; width: 100%;">
            <div class="signature-block">
                <div class="signature-line">
                    <strong>Discharging Doctor</strong><br>
                    @if(isset($admission->attending_doctor))
                        Dr. {{ $admission->attending_doctor->firstname ?? '' }} {{ $admission->attending_doctor->lastname ?? '' }}<br>
                        {{ $admission->attending_doctor->license_number ?? '' }}
                    @endif
                </div>
            </div>
            <div class="signature-block">
                <div class="signature-line">
                    <strong>Date</strong><br>
                    {{ $generated_at->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This is a computer-generated discharge summary document.</p>
        <p>{{ $branding['business_name'] ?? 'Next Hospital' }} | {{ $branding['business_phone'] ?? '' }} | {{ $branding['business_email'] ?? '' }}</p>
    </div>
</body>
</html>

