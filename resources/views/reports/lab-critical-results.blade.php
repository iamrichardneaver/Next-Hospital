<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title }}</title>
    <style>
        @page {
            margin: 15mm;
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
            border-bottom: 4px solid {{ $branding['primary_color'] ?? '#dc3545' }};
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
            font-size: 23pt;
            font-weight: bold;
            color: {{ $branding['primary_color'] ?? '#dc3545' }};
            margin-bottom: 5px;
        }
        
        .hospital-details {
            font-size: 9pt;
            color: #666;
            line-height: 1.3;
        }
        
        .alert-title {
            font-size: 20pt;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8d7da;
            border: 3px solid #dc3545;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .alert-icon {
            font-size: 24pt;
            margin-right: 15px;
        }
        
        .patient-info {
            background-color: #fff3cd;
            padding: 20px;
            border: 2px solid #ffc107;
            margin-bottom: 25px;
            border-radius: 8px;
        }
        
        .patient-info h3 {
            margin: 0 0 15px 0;
            color: #856404;
            font-size: 14pt;
            border-bottom: 2px solid #ffc107;
            padding-bottom: 10px;
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
            margin-bottom: 10px;
            display: flex;
        }
        
        .info-label {
            font-weight: bold;
            color: #856404;
            width: 140px;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #212529;
            flex: 1;
            font-weight: bold;
        }
        
        .critical-results {
            background-color: #f8d7da;
            border: 3px solid #dc3545;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        
        .critical-results h3 {
            margin: 0 0 20px 0;
            color: #721c24;
            font-size: 23pt;
            text-align: center;
            border-bottom: 2px solid #dc3545;
            padding-bottom: 15px;
        }
        
        .critical-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .critical-table th {
            background-color: #dc3545;
            color: white;
            font-weight: bold;
            padding: 15px 12px;
            text-align: left;
            border: 1px solid #dc3545;
            font-size: 14pt;
        }
        
        .critical-table td {
            padding: 15px 12px;
            border: 1px solid #dc3545;
            font-size: 14pt;
            vertical-align: top;
            background-color: white;
        }
        
        .parameter-name {
            font-weight: bold;
            color: #721c24;
            font-size: 14pt;
        }
        
        .result-value {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            color: #dc3545;
        }
        
        .reference-range {
            text-align: center;
            color: #6c757d;
            font-size: 9pt;
        }
        
        .critical-flag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 9pt;
            margin-left: 8px;
            background-color: #dc3545;
            color: white;
        }
        
        .alert-actions {
            background-color: #e7f3ff;
            padding: 20px;
            border: 2px solid #007bff;
            margin: 25px 0;
            border-radius: 8px;
        }
        
        .alert-actions h4 {
            margin: 0 0 15px 0;
            color: #007bff;
            font-size: 14pt;
        }
        
        .action-list {
            margin: 0;
            padding-left: 20px;
        }
        
        .action-list li {
            margin-bottom: 8px;
            color: #495057;
        }
        
        .contact-info {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            margin: 25px 0;
            border-radius: 8px;
        }
        
        .contact-info h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 14pt;
        }
        
        .contact-grid {
            display: table;
            width: 100%;
        }
        
        .contact-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 25px 15px;
            border: 2px solid #dc3545;
            margin: 0 5px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        
        .signature-line {
            border-bottom: 2px solid #333;
            margin-bottom: 10px;
            height: 50px;
        }
        
        .signature-label {
            font-size: 14pt;
            color: #dc3545;
            font-weight: bold;
        }
        
        .signature-name {
            font-size: 9pt;
            color: #495057;
            margin-top: 8px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 3px solid #dc3545;
            font-size: 9pt;
            color: #6c757d;
            text-align: center;
        }
        
        .urgency-notice {
            background-color: #dc3545;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 20px 0;
            border-radius: 8px;
        }
        
        .timestamp {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: center;
            font-weight: bold;
            color: #495057;
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
                <div style="font-size: 14pt; color: #dc3545; font-weight: bold;">
                    <strong>CRITICAL ALERT</strong><br>
                    <strong>Report Date:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
                    <strong>Branch:</strong> {{ $branch->name ?? 'Main Branch' }}<br>
                    <strong>Alert ID:</strong> {{ $labRequest->request_number }}
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Alert Title -->
    <div class="alert-title">
        <span class="alert-icon">⚠️</span>
        CRITICAL RESULTS ALERT
    </div>

    <!-- Urgency Notice -->
    <div class="urgency-notice">
        IMMEDIATE ATTENTION REQUIRED - CONTACT REQUESTING PHYSICIAN IMMEDIATELY
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
        <h3>PATIENT INFORMATION</h3>
        <div class="info-grid">
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Patient Name:</span>
                    <span class="info-value">{{ $labRequest->patient->first_name }} {{ $labRequest->patient->last_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Patient ID:</span>
                    <span class="info-value">{{ $labRequest->patient->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Birth:</span>
                    <span class="info-value">{{ $labRequest->patient->date_of_birth ? \Carbon\Carbon::parse($labRequest->patient->date_of_birth)->format('d/m/Y') : 'Not provided' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Age:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($labRequest->patient->date_of_birth)->age }} years</span>
                </div>
            </div>
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Gender:</span>
                    <span class="info-value">{{ $labRequest->patient->gender }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Request Date:</span>
                    <span class="info-value">{{ $labRequest->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Priority:</span>
                    <span class="info-value" style="background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px;">{{ strtoupper($labRequest->priority) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Requested By:</span>
                    <span class="info-value">
                        @if($labRequest->consultation_id)
                            Dr. {{ $labRequest->doctor->first_name ?? '' }} {{ $labRequest->doctor->last_name ?? '' }}
                        @else
                            Walk-in
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Results -->
    <div class="critical-results">
        <h3>CRITICAL LABORATORY RESULTS</h3>
        <table class="critical-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Parameter</th>
                    <th style="width: 25%;">Critical Result</th>
                    <th style="width: 15%;">Unit</th>
                    <th style="width: 20%;">Reference Range</th>
                    <th style="width: 10%;">Flag</th>
                </tr>
            </thead>
            <tbody>
                @foreach($criticalResults as $result)
                    <tr>
                        <td class="parameter-name">{{ $result->parameter_name }}</td>
                        <td class="result-value">{{ $result->result_value }}</td>
                        <td style="text-align: center;">{{ $result->unit ?? '-' }}</td>
                        <td class="reference-range">{{ $result->reference_range ?? 'N/A' }}</td>
                        <td style="text-align: center;">
                            <span class="critical-flag">{{ $result->abnormal_flag ?? 'CRITICAL' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Alert Actions -->
    <div class="alert-actions">
        <h4>REQUIRED ACTIONS</h4>
        <ol class="action-list">
            <li>Contact the requesting physician immediately</li>
            <li>Verify patient identity and results</li>
            <li>Document the critical result notification</li>
            <li>Follow up to ensure appropriate patient care</li>
            <li>Complete critical result documentation</li>
        </ol>
    </div>

    <!-- Contact Information -->
    <div class="contact-info">
        <h4>CONTACT INFORMATION</h4>
        <div class="contact-grid">
            <div class="contact-column">
                <div class="info-row">
                    <span class="info-label">Laboratory:</span>
                    <span class="info-value">{{ $settings['hospital_phone'] ?? 'Phone Number' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Emergency:</span>
                    <span class="info-value">{{ $settings['emergency_phone'] ?? 'Emergency Number' }}</span>
                </div>
            </div>
            <div class="contact-column">
                <div class="info-row">
                    <span class="info-label">Lab Manager:</span>
                    <span class="info-value">{{ $settings['lab_manager_phone'] ?? 'Lab Manager Phone' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $settings['hospital_email'] ?? 'Email Address' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Timestamp -->
    <div class="timestamp">
        Critical Alert Generated: {{ $generated_at->format('d/m/Y H:i:s') }}
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Laboratory Technician</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Lab Supervisor</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Physician Notified</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>
            <strong>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}</strong><br>
            {{ $branding['business_address'] ?? $settings['hospital_address'] ?? 'Hospital Address' }} | 
            Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? 'Phone Number' }} | 
            Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'Email Address' }}<br>
            <em>This critical alert was generated on {{ $generated_at->format('d/m/Y H:i:s') }} and requires immediate action.</em>
        </div>
    </div>
</body>
</html>
