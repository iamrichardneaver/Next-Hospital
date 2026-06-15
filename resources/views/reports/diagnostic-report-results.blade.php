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
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        .header {
            border-bottom: 2px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            padding-bottom: 10px;
            margin-bottom: 15px;
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
        
        .company-name {
            font-size: 23pt;
            font-weight: bold;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .company-details {
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
        }
        
        .report-title {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .patient-info {
            border: 1px solid #000;
            padding: 8px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        
        .patient-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .patient-info td {
            padding: 2px 5px;
            font-size: 9pt;
            vertical-align: top;
        }
        
        .patient-info .label {
            font-weight: bold;
            width: 30%;
        }
        
        .report-info {
            border: 1px solid #000;
            padding: 8px;
            margin-bottom: 15px;
            background-color: #f0f0f0;
        }
        
        .report-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .report-info td {
            padding: 2px 5px;
            font-size: 9pt;
            vertical-align: top;
        }
        
        .report-info .label {
            font-weight: bold;
            width: 30%;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #000;
        }
        
        .results-table th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            padding: 6px 4px;
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
        }
        
        .results-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 9pt;
            text-align: center;
        }
        
        .results-table .parameter-name {
            text-align: left;
            font-weight: bold;
        }
        
        .results-table .result-value {
            font-weight: bold;
        }
        
        .flag-high {
            color: #d32f2f;
            font-weight: bold;
        }
        
        .flag-low {
            color: #1976d2;
            font-weight: bold;
        }
        
        .flag-critical {
            color: #d32f2f;
            font-weight: bold;
            background-color: #ffebee;
        }
        
        .notes-section {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #000;
            background-color: #f9f9f9;
        }
        
        .notes-section h4 {
            margin: 0 0 8px 0;
            font-size: 14pt;
            font-weight: bold;
        }
        
        .notes-section p {
            margin: 0 0 5px 0;
            font-size: 9pt;
            line-height: 1.3;
        }
        
        .footer {
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 20px;
        }
        
        .signature-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .signature-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            width: 200px;
            margin-bottom: 5px;
        }
        
        .signature-label {
            font-size: 8pt;
            font-weight: bold;
        }
        
        .disclaimer {
            margin-top: 15px;
            padding: 8px;
            border: 1px solid #000;
            background-color: #fff3cd;
            font-size: 8pt;
            line-height: 1.2;
        }
        
        .end-of-report {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .qr-code {
            text-align: center;
            margin: 10px 0;
        }
        
        .barcode {
            text-align: center;
            margin: 5px 0;
            font-family: 'Courier New', monospace;
            font-size: 8pt;
            letter-spacing: 1px;
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
                <div class="company-name">{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}</div>
                <div class="company-details">
                    {{ $branding['business_address'] ?? $settings['hospital_address'] ?? 'Hospital Address' }}<br>
                    @if(isset($branding['business_website']) && $branding['business_website'])
                        Website: {{ $branding['business_website'] }}
                    @elseif(isset($settings['hospital_website']))
                        Website: {{ $settings['hospital_website'] }}
                    @endif<br>
                    Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'Email Address' }}<br>
                    @if(isset($branding['business_digital_address']) && $branding['business_digital_address'])
                        Digital Address: {{ $branding['business_digital_address'] }}<br>
                    @endif
                    Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? 'Phone Number' }}<br>
                    @if(isset($branding['business_mobile']) && $branding['business_mobile'])
                        Mob: {{ $branding['business_mobile'] }}
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div style="font-size: 8pt; line-height: 1.2;">
                    <strong>Report Date:</strong> {{ $pdfService->formatDate($pdfService->getReportTime($labRequest), 'd/m/Y') }}<br>
                    <strong>Report Time:</strong> {{ $pdfService->formatTime($pdfService->getReportTime($labRequest), 'H:i A') }}<br>
                    @if($branch)
                        <strong>Branch:</strong> {{ $branch->name }}<br>
                    @endif
                    <strong>Report ID:</strong> {{ $labRequest->request_number }}
                </div>
            </div>
        </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
        {{ $page_title }}
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
        <table>
            <tr>
                <td class="label">Patient Name:</td>
                <td>{{ strtoupper($labRequest->patient->first_name . ' ' . $labRequest->patient->last_name) }}</td>
                <td class="label">Age / Gender:</td>
                <td>{{ $pdfService->getPatientAge($labRequest->patient->date_of_birth) }} years / {{ ucfirst($labRequest->patient->gender) }}</td>
            </tr>
            <tr>
                <td class="label">Date of Birth:</td>
                <td>{{ $pdfService->formatDate($labRequest->patient->date_of_birth, 'd/m/Y') ?: '-' }}</td>
                <td class="label">Contact Number:</td>
                <td>{{ $labRequest->patient->phone ?: $labRequest->patient->contact }}</td>
            </tr>
            <tr>
                <td class="label">Referred By:</td>
                <td>{{ $pdfService->getReferredBy($labRequest) }}</td>
                <td class="label">Source:</td>
                <td>{{ $pdfService->getSource($labRequest) }}</td>
            </tr>
        </table>
    </div>

    <!-- Report Administration -->
    <div class="report-info">
        <table>
            <tr>
                <td class="label">Reg. No:</td>
                <td>{{ $pdfService->getRegistrationNumber($labRequest) }}</td>
                <td class="label">Collected At:</td>
                <td>{{ $pdfService->formatDate($pdfService->getSampleCollectionTime($labRequest), 'd/m/Y') }}, {{ $pdfService->formatTime($pdfService->getSampleCollectionTime($labRequest), 'H:i A') }}</td>
            </tr>
            <tr>
                <td class="label">Reported At:</td>
                <td>{{ $pdfService->formatDate($pdfService->getReportTime($labRequest), 'd/m/Y') }}, {{ $pdfService->formatTime($pdfService->getReportTime($labRequest), 'H:i A') }}</td>
                <td class="label">Sample ID:</td>
                <td>{{ $pdfService->getSampleId($labRequest) }}</td>
            </tr>
        </table>
        
        <!-- Barcode -->
        <div class="barcode">
            {{ $pdfService->getSampleId($labRequest) }}
        </div>
    </div>

    <!-- Test Results Table -->
    @if(count($resultsByParameter) > 0)
        @php
            $showReferenceRange = $template && $template->template_type === 'quantitative';
            $parameterColumnWidth = $showReferenceRange ? '40%' : '50%';
            $flagColumnWidth = $showReferenceRange ? '10%' : '10%';
            $resultColumnWidth = $showReferenceRange ? '20%' : '30%';
            $unitColumnWidth = $showReferenceRange ? '10%' : '10%';
        @endphp
        <table class="results-table">
            <thead>
                <tr>
                    <th style="width: {{ $parameterColumnWidth }};">Parameter</th>
                    <th style="width: {{ $flagColumnWidth }};">FLAG</th>
                    <th style="width: {{ $resultColumnWidth }};">Results</th>
                    @if($showReferenceRange)
                        <th style="width: 20%;">Reference Range</th>
                    @endif
                    <th style="width: {{ $unitColumnWidth }};">Unit(s)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resultsByParameter as $code => $result)
                    @php
                        $formattedResult = $pdfService->formatResultsByType([$code => $result], $template->template_type ?? 'quantitative')[$code];
                        $flag = $formattedResult['abnormal_flag'];
                        $flagClass = '';
                        
                        if ($flag === 'H') {
                            $flagClass = 'flag-high';
                        } elseif ($flag === 'L') {
                            $flagClass = 'flag-low';
                        } elseif ($flag === 'CRITICAL') {
                            $flagClass = 'flag-critical';
                        }
                    @endphp
                    <tr>
                        <td class="parameter-name">{{ $formattedResult['parameter_name'] }}</td>
                        <td class="{{ $flagClass }}">{{ $flag }}</td>
                        <td class="result-value {{ $flagClass }}">{{ $formattedResult['result_value'] }}</td>
                        @if($showReferenceRange)
                            <td>{{ $formattedResult['reference_range'] ?: '-' }}</td>
                        @endif
                        <td>{{ $formattedResult['unit'] ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Notes Section -->
    <div class="notes-section">
        <h4>Additional Notes:</h4>
        @if($template && $template->methodology)
            <p><strong>Methodology:</strong> {{ $template->methodology }}</p>
        @endif
        
        @if($template && $template->equipment_required)
            <p><strong>Equipment:</strong> {{ $template->equipment_required }}</p>
        @endif
        
        @foreach($resultsByParameter as $code => $result)
            @if($result->clinical_interpretation)
                <p><strong>{{ $result->parameter_name }}:</strong> {{ $result->clinical_interpretation }}</p>
            @endif
        @endforeach
        
        @if($template && $template->description)
            <p>{{ $template->description }}</p>
        @endif
    </div>

    <!-- End of Report -->
    <div class="end-of-report">
        END OF REPORT
    </div>

    <!-- Disclaimer -->
    <div class="disclaimer">
        <strong>Disclaimer:</strong> If test results are alarming or unexpected, client is advised to contact the laboratory immediately for possible remedial action.
    </div>

    <!-- QR Code Placeholder -->
    <div class="qr-code">
        <!-- QR Code would be generated here -->
        <div style="width: 80px; height: 80px; border: 1px solid #000; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 8pt;">
            QR CODE
        </div>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-left">
            <div class="signature-line"></div>
            <div class="signature-label">{{ $pdfService->getTechnicianName($labRequest) }}</div>
            <div style="font-size: 8pt;">Lab Technician</div>
        </div>
        <div class="signature-right">
            <div class="signature-line" style="margin-left: auto;"></div>
            <div class="signature-label">{{ $pdfService->getReviewerName($labRequest) }}</div>
            <div style="font-size: 8pt;">{{ $pdfService->getReviewerTitle($labRequest) }}</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div style="text-align: center; font-size: 8pt; color: #666;">
            Generated on {{ $generated_at->format('d/m/Y H:i:s') }} | 
            Report ID: {{ $labRequest->request_number }} | 
            {{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}
        </div>
    </div>
</body>
</html>
