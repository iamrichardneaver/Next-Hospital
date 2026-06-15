<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Lab Result - {{ $patient->first_name }} {{ $patient->last_name }}</title>
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
        
        .header {
            border-bottom: 3px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-content {
            display: table;
            width: 100%;
        }
        
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        
        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }
        
        .hospital-name {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 23pt;
            font-weight: bold;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            margin-bottom: 5px;
        }
        
        .hospital-details {
            font-size: 9pt;
            color: #666;
            line-height: 1.3;
        }
        
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
        
        .lab-result {
            background-color: #fff;
            border: 2px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .result-header {
            background-color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            color: white;
            padding: 10px 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 3px 3px 0 0;
        }
        
        .result-header h4 {
            margin: 0;
            font-size: 14pt;
        }
        
        .result-value {
            font-size: 18px;
            font-weight: bold;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            text-align: center;
            margin: 15px 0;
        }
        
        .normal-range {
            color: #28a745;
            text-align: center;
            font-size: 14pt;
            margin-bottom: 15px;
        }
        
        .abnormal-range {
            color: #dc3545;
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .result-note {
            background-color: #f8f9fa;
            padding: 10px;
            border-left: 4px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            margin: 15px 0;
            font-size: 9pt;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
            color: #6c757d;
            text-align: center;
        }
        
        .signature-section {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 20px 10px;
            border: 1px solid #dee2e6;
            margin: 0 5px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 40px;
        }
        
        .signature-label {
            font-size: 8pt;
            color: #6c757d;
        }
        
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-code img {
            width: 80px;
            height: 80px;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                @if(isset($branding['logo_base64']) && $branding['logo_base64'])
                    <div style="margin-bottom: 10px;">
                        <img src="{{ $branding['logo_base64'] }}" alt="{{ $branding['business_name'] ?? 'Logo' }}" style="height: 40px; max-width: 200px; object-fit: contain;" />
                    </div>
                @endif
                <div class="hospital-name">{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}</div>
                <div class="hospital-details">
                    {{ $branding['business_address'] ?? $settings['hospital_address'] ?? 'Hospital Address' }}<br>
                    Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? 'Phone Number' }} | 
                    Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'Email Address' }}<br>
                    @if(isset($branding['business_website']) && $branding['business_website'])
                        Website: {{ $branding['business_website'] }}
                    @elseif(isset($settings['hospital_website']))
                        Website: {{ $settings['hospital_website'] }}
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div style="font-size: 9pt; color: #666;">
                    <strong>Report Date:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
                    @if(isset($branch))
                        <strong>Branch:</strong> {{ $branch->name }}<br>
                    @endif
                    <strong>Report ID:</strong> {{ $labRequest->request_number ?? 'N/A' }}
                </div>
            </div>
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
                    <span class="info-label">Request Date:</span>
                    <span class="info-value">{{ $labRequest->created_at->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Priority:</span>
                    <span class="info-value" style="{{ $labRequest->priority === 'urgent' ? 'color: #dc3545; font-weight: bold;' : '' }}">{{ strtoupper($labRequest->priority ?? 'routine') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Requested By:</span>
                    <span class="info-value">
                        @if($labRequest->consultation_id && isset($labRequest->doctor))
                            Dr. {{ $labRequest->doctor->first_name ?? '' }} {{ $labRequest->doctor->last_name ?? '' }}
                        @else
                            Walk-in
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Lab Results -->
    @if(isset($results) && count($results) > 0)
        @foreach($results as $result)
            <div class="lab-result">
                <div class="result-header">
                    <h4>{{ $result->parameter_name ?? 'Test Parameter' }}</h4>
                </div>
                
                <div class="result-value">
                    {{ $result->result_value ?? 'Pending' }}
                    @if(isset($result->unit))
                        {{ $result->unit }}
                    @endif
                </div>
                
                @if(isset($result->reference_range))
                    <div class="{{ $result->result_status === 'normal' ? 'normal-range' : 'abnormal-range' }}">
                        Reference Range: {{ $result->reference_range }}
                        @if(isset($result->abnormal_flag))
                            ({{ $result->abnormal_flag }})
                        @endif
                    </div>
                @endif
                
                @if(isset($result->clinical_interpretation) && $result->clinical_interpretation)
                    <div class="result-note">
                        <strong>Clinical Interpretation:</strong><br>
                        {{ $result->clinical_interpretation }}
                    </div>
                @endif
                
                @if(isset($result->methodology) && $result->methodology)
                    <div class="result-note">
                        <strong>Methodology:</strong> {{ $result->methodology }}
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="lab-result">
            <div class="result-header">
                <h4>Test Results</h4>
            </div>
            <div class="result-value" style="color: #6c757d;">
                No results available
            </div>
        </div>
    @endif

    <!-- QR Code for Verification -->
    <div class="qr-code">
        <img src="{{ $pdfService->generateQRCode($labRequest->id ?? $patient->id) ?? '' }}" alt="QR Code">
        <div style="font-size: 8pt; color: #6c757d; margin-top: 5px;">
            Scan to verify results online
        </div>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Laboratory Technician</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Verified By</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Approved By</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>
            <strong>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}</strong><br>
            {{ $branding['business_address'] ?? $settings['hospital_address'] ?? 'Hospital Address' }} | 
            Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? 'Phone Number' }} | 
            Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'Email Address' }}<br>
            <em>This report was generated on {{ $generated_at->format('d/m/Y H:i') }} and is valid for 30 days from the date of issue.</em>
        </div>
    </div>
</body>
</html>