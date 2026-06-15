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
        
        /* Header styles are now in the print-header component */
        
        .test-title {
            font-size: 23pt;
            font-weight: bold;
            color: #2c5aa0;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border: 2px solid #2c5aa0;
            border-radius: 8px;
        }
        
        .test-type-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14pt;
            font-weight: bold;
            margin-left: 15px;
        }
        
        .test-type-quantitative {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .test-type-qualitative {
            background-color: #d4edda;
            color: #155724;
        }
        
        .test-type-narrative {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .test-type-combined {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .patient-info {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        
        .patient-info h3 {
            margin: 0 0 15px 0;
            color: #2c5aa0;
            font-size: 14pt;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
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
            display: flex;
        }
        
        .info-label {
            font-weight: bold;
            color: #495057;
            width: 120px;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #212529;
            flex: 1;
        }
        
        .test-details {
            background-color: #e9ecef;
            padding: 15px;
            border-left: 4px solid #2c5aa0;
            margin-bottom: 20px;
        }
        
        .test-details h4 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
            font-size: 14pt;
        }
        
        .results-section {
            margin-bottom: 30px;
        }
        
        .results-header {
            background-color: #2c5aa0;
            color: white;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 14pt;
            border-radius: 6px 6px 0 0;
        }
        
        .results-content {
            border: 1px solid #dee2e6;
            border-top: none;
            padding: 20px;
            border-radius: 0 0 6px 6px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .results-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: bold;
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #dee2e6;
            font-size: 9pt;
        }
        
        .results-table td {
            padding: 12px 15px;
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
            font-size: 14pt;
        }
        
        .reference-range {
            text-align: center;
            color: #6c757d;
            font-size: 8pt;
        }
        
        .flag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8pt;
            margin-left: 8px;
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
        
        .narrative-content {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        
        .narrative-content h4 {
            margin: 0 0 15px 0;
            color: #2c5aa0;
            font-size: 14pt;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
        }
        
        .narrative-text {
            line-height: 1.6;
            color: #495057;
        }
        
        .critical-alert {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        
        .critical-alert h4 {
            margin: 0 0 15px 0;
            color: #721c24;
            font-size: 14pt;
        }
        
        .methodology-section {
            background-color: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }
        
        .methodology-section h4 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 14pt;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            font-size: 8pt;
            color: #6c757d;
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
            border: 1px solid #dee2e6;
            margin: 0 5px;
            border-radius: 6px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 8px;
            height: 50px;
        }
        
        .signature-label {
            font-size: 9pt;
            color: #6c757d;
            font-weight: bold;
        }
        
        .signature-name {
            font-size: 8pt;
            color: #495057;
            margin-top: 5px;
        }
        
        .qr-code {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }
        
        .qr-code img {
            width: 100px;
            height: 100px;
        }
        
        .no-results {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }
        
        .test-description {
            background-color: #fff3cd;
            padding: 15px;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .test-description h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 14pt;
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

    <!-- Test Title -->
    <div class="test-title">
        {{ $template->template_name }}
        @if(isset($testType) && $testType)
            <span class="test-type-info">
                ({{ $testType->test_name }} - {{ $testType->test_code }})
            </span>
        @endif
        <span class="test-type-badge test-type-{{ $template->template_type }}">
            {{ strtoupper($template->template_type) }}
        </span>
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

    <!-- Test Details -->
    <div class="test-details">
        <h4>TEST INFORMATION</h4>
        <div class="info-row">
            <span class="info-label">Test Code:</span>
            <span class="info-value">{{ $template->template_code }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Category:</span>
            <span class="info-value">{{ $template->category }}</span>
        </div>
        @if($template->subcategory)
        <div class="info-row">
            <span class="info-label">Subcategory:</span>
            <span class="info-value">{{ $template->subcategory }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Specimen Type:</span>
            <span class="info-value">{{ $template->specimen_type }}</span>
        </div>
        @if($template->methodology)
        <div class="info-row">
            <span class="info-label">Methodology:</span>
            <span class="info-value">{{ $template->methodology }}</span>
        </div>
        @endif
    </div>

    <!-- Test Description -->
    @if($template->description)
        <div class="test-description">
            <h4>Test Description</h4>
            <p>{{ $template->description }}</p>
        </div>
    @endif

    <!-- Critical Results Alert -->
    @if($results->where('result_status', 'critical')->count() > 0)
        <div class="critical-alert">
            <h4>⚠️ CRITICAL RESULTS ALERT</h4>
            <p>The following results require immediate attention:</p>
            <ul>
                @foreach($results->where('result_status', 'critical') as $criticalResult)
                    <li><strong>{{ $criticalResult->parameter_name }}:</strong> {{ $criticalResult->result_value }} {{ $criticalResult->unit ?? '' }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Test Results -->
    <div class="results-section">
        <div class="results-header">
            TEST RESULTS
        </div>
        <div class="results-content">
            @if($template->template_type === 'narrative')
                <!-- Narrative Results -->
                @foreach($results as $result)
                    <div class="narrative-content">
                        <h4>{{ $result->parameter_name }}</h4>
                        <div class="narrative-text">
                            {!! $result->result_value !!}
                        </div>
                        @if($result->clinical_interpretation)
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                                <strong>Clinical Interpretation:</strong><br>
                                {!! $result->clinical_interpretation !!}
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <!-- Tabular Results -->
                @php
                    $showReferenceRange = $template && $template->template_type === 'quantitative';
                    $parameterColumnWidth = $showReferenceRange ? '30%' : '40%';
                    $resultColumnWidth = $showReferenceRange ? '20%' : '30%';
                    $unitColumnWidth = $showReferenceRange ? '15%' : '20%';
                    $statusColumnWidth = $showReferenceRange ? '15%' : '10%';
                @endphp
                <table class="results-table">
                    <thead>
                        <tr>
                            <th style="width: {{ $parameterColumnWidth }};">Parameter</th>
                            <th style="width: {{ $resultColumnWidth }};">Result</th>
                            <th style="width: {{ $unitColumnWidth }};">Unit</th>
                            @if($showReferenceRange)
                                <th style="width: 20%;">Reference Range</th>
                            @endif
                            <th style="width: {{ $statusColumnWidth }};">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
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
                                @if($showReferenceRange)
                                    <td class="reference-range">{{ $pdfService->getReferenceRangeText($result, $result->parameter) }}</td>
                                @endif
                                <td style="text-align: center;">
                                    <span class="flag flag-{{ $result->result_status }}">
                                        {{ strtoupper($result->result_status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <!-- Clinical Comments -->
            @if($results->where('clinical_interpretation')->count() > 0)
                <div class="narrative-content">
                    <h4>Clinical Interpretation</h4>
                    @foreach($results->where('clinical_interpretation') as $result)
                        @if($result->clinical_interpretation)
                            <div class="narrative-text">
                                <strong>{{ $result->parameter_name }}:</strong><br>
                                {!! $result->clinical_interpretation !!}
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Methodology Information -->
    @if($template->methodology || $template->equipment_required)
        <div class="methodology-section">
            <h4>Methodology & Equipment</h4>
            @if($template->methodology)
                <p><strong>Methodology:</strong> {{ $template->methodology }}</p>
            @endif
            @if($template->equipment_required)
                <p><strong>Equipment Used:</strong> {{ $template->equipment_required }}</p>
            @endif
        </div>
    @endif

    <!-- QR Code for Verification -->
    <div class="qr-code">
        <img src="{{ $pdfService->generateQRCode($labRequest->id) }}" alt="QR Code">
        <div style="font-size: 9pt; color: #6c757d; margin-top: 8px;">
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
            <strong>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}</strong><br>
            {{ $branding['business_address'] ?? $settings['hospital_address'] ?? 'Hospital Address' }} | 
            Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? 'Phone Number' }} | 
            Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'Email Address' }}<br>
            <em>This report was generated on {{ $generated_at->format('d/m/Y H:i') }} and is valid for 30 days from the date of issue.</em>
        </div>
    </div>
</body>
</html>
