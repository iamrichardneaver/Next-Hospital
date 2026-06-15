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
        
        .report-title {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 23pt;
            font-weight: bold;
            color: #2c5aa0;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .test-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            margin-left: 10px;
            background-color: #d1ecf1;
            color: #0c5460;
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
        
        .test-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .test-header {
            background-color: #2c5aa0;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 0;
        }
        
        .test-content {
            border: 1px solid #dee2e6;
            border-top: none;
            padding: 15px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .results-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: bold;
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #dee2e6;
            font-size: 9pt;
        }
        
        .results-table td {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            font-size: 9pt;
            vertical-align: top;
        }
        
        .parameter-name {
            font-weight: bold;
            color: #495057;
        }
        
        .result-value {
            text-align: center;
            font-weight: bold;
        }
        
        .reference-range {
            text-align: center;
            color: #6c757d;
            font-size: 8pt;
        }
        
        .flag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 8pt;
            margin-left: 5px;
        }
        
        .flag-critical {
            background-color: #dc3545;
            color: white;
        }
        
        .flag-abnormal {
            background-color: #fd7e14;
            color: white;
        }
        
        .flag-normal {
            background-color: #28a745;
            color: white;
        }
        
        .critical-alert {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        
        .critical-alert h4 {
            margin: 0 0 10px 0;
            color: #721c24;
            font-size: 14pt;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
            color: #6c757d;
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
        
        .no-results {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }
        
        .methodology-section {
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #dee2e6;
            margin-top: 15px;
            font-size: 8pt;
        }
        
        .methodology-section h4 {
            margin: 0 0 5px 0;
            color: #2c5aa0;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    @include('components.print-header')
    
    <!-- Report Information -->
    <div class="report-info-right">
        <div style="font-size: 9pt; color: #666; text-align: right;">
            <strong>Report Date:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
            <strong>Branch:</strong> {{ $branch->name ?? 'Main Branch' }}<br>
            <strong>Report ID:</strong> {{ $labRequest->request_number }}
        </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
        {{ $template->template_name }} - QUANTITATIVE RESULTS
        <span class="test-type-badge">QUANTITATIVE</span>
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
                    <span class="info-value" style="{{ $pdfService->getPriorityStyle($labRequest->priority) }}">{{ strtoupper($labRequest->priority) }}</span>
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

    <!-- Critical Results Alert -->
    @if($labRequest->results->where('result_status', 'critical')->count() > 0)
        <div class="critical-alert">
            <h4>⚠️ CRITICAL RESULTS ALERT</h4>
            <p>The following results require immediate attention:</p>
            <ul>
                @foreach($labRequest->results->where('result_status', 'critical') as $criticalResult)
                    <li><strong>{{ $criticalResult->parameter_name }}:</strong> {{ $criticalResult->result_value }} {{ $criticalResult->unit ?? '' }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Test Results -->
    <div class="test-section">
        <div class="test-header">
            {{ $template->template_name }} - Quantitative Analysis
        </div>
        <div class="test-content">
            @if(count($resultsByParameter) > 0)
                <table class="results-table">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Parameter</th>
                            <th style="width: 20%;">Result</th>
                            <th style="width: 15%;">Unit</th>
                            <th style="width: 20%;">Reference Range</th>
                            <th style="width: 15%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resultsByParameter as $parameterId => $parameterData)
                            @php $result = $parameterData['result']; @endphp
                            <tr>
                                <td class="parameter-name">{{ $result->parameter_name }}</td>
                                <td class="result-value" style="{{ $pdfService->getResultStatusStyle($result) }}">
                                    {{ $pdfService->formatResultValue($result, $result->parameter) }}
                                    @if($result->abnormal_flag)
                                        <span class="flag flag-{{ $result->result_status }}" style="{{ $pdfService->getAbnormalFlagStyle($result->abnormal_flag) }}">
                                            {{ $result->abnormal_flag }}
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: center;">{{ $result->unit ?? '-' }}</td>
                                <td class="reference-range">{{ $pdfService->getReferenceRangeText($result, $result->parameter) }}</td>
                                <td style="text-align: center;">
                                    <span class="flag flag-{{ $result->result_status }}">
                                        {{ strtoupper($result->result_status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="no-results">
                    No quantitative results available for this template.
                </div>
            @endif

            <!-- Methodology Information -->
            @if($template->methodology)
                <div class="methodology-section">
                    <h4>Methodology</h4>
                    <p><strong>Method:</strong> {{ $template->methodology }}</p>
                    @if($template->equipment_required)
                        <p><strong>Equipment:</strong> {{ $template->equipment_required }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- QR Code for Verification -->
    <div class="qr-code">
        <img src="{{ $pdfService->generateQRCode($labRequest->id) }}" alt="QR Code">
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
        <div style="text-align: center;">
            <strong>{{ $settings['hospital_name'] ?? 'Hospital Name' }}</strong><br>
            {{ $settings['hospital_address'] ?? 'Hospital Address' }} | 
            Tel: {{ $settings['hospital_phone'] ?? 'Phone Number' }} | 
            Email: {{ $settings['hospital_email'] ?? 'Email Address' }}<br>
            <em>This report was generated on {{ $generated_at->format('d/m/Y H:i') }} and is valid for 30 days from the date of issue.</em>
        </div>
    </div>
</body>
</html>
