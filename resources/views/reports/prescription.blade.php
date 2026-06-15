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
        
        .prescription-title {
            font-size: 23pt;
            font-weight: bold;
            color: #2c5aa0;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .patient-info {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .patient-info h3 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
            font-size: 14pt;
        }
        
        .info-row {
            margin-bottom: 8px;
            display: table;
            width: 100%;
        }
        
        .info-label {
            font-weight: bold;
            display: table-cell;
            width: 30%;
        }
        
        .info-value {
            display: table-cell;
            width: 70%;
        }
        
        .medications-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .medications-table th,
        .medications-table td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
        }
        
        .medications-table th {
            background-color: #2c5aa0;
            color: white;
            font-weight: bold;
        }
        
        .medications-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .instructions {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .instructions h4 {
            margin: 0 0 10px 0;
            color: #856404;
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
    </style>
</head>
<body>
    <!-- Header Section -->
    @include('components.print-header')
    
    <!-- Prescription Information -->
    <div class="prescription-info-right">
        <div style="font-size: 9pt; color: #666; text-align: right;">
            <strong>Date:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
            @if($branch)
                <strong>Branch:</strong> {{ $branch->name }}<br>
            @endif
            <strong>Prescription #:</strong> {{ $prescription->prescription_number ?? 'N/A' }}
        </div>
    </div>

    <!-- Prescription Title -->
    <div class="prescription-title">
        PRESCRIPTION
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
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

    <!-- Medications Table -->
    <table class="medications-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Medication</th>
                <th style="width: 15%;">Dosage</th>
                <th style="width: 15%;">Frequency</th>
                <th style="width: 10%;">Duration</th>
                <th style="width: 30%;">Instructions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($medications as $index => $medication)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $medication->drug_name ?? $medication->name ?? 'N/A' }}</strong></td>
                <td>{{ $medication->dosage ?? 'N/A' }}</td>
                <td>{{ $medication->frequency ?? 'N/A' }}</td>
                <td>{{ $medication->duration ?? 'N/A' }}</td>
                <td>{{ $medication->instructions ?? $medication->notes ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #999;">No medications prescribed</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- General Instructions -->
    @if(isset($prescription->notes) && $prescription->notes)
    <div class="instructions">
        <h4>GENERAL INSTRUCTIONS</h4>
        <p style="margin: 0;">{{ $prescription->notes }}</p>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div style="display: table; width: 100%;">
            <div class="signature-block">
                <div class="signature-line">
                    <strong>Prescribing Doctor</strong><br>
                    @if(isset($prescription->doctor))
                        {{ $prescription->doctor->firstname ?? '' }} {{ $prescription->doctor->lastname ?? '' }}<br>
                        {{ $prescription->doctor->license_number ?? '' }}
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
        <p>This is a computer-generated prescription. Please verify all medications with a licensed pharmacist before use.</p>
        <p>{{ $branding['business_name'] ?? 'Next Hospital' }} | {{ $branding['business_phone'] ?? '' }} | {{ $branding['business_email'] ?? '' }}</p>
    </div>
</body>
</html>

