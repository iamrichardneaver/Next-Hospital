<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title }}</title>
    <style>
        @page {
            margin: 10mm 12mm 15mm 12mm;
            size: A4;
        }
        
        @page :first {
            margin-top: 8mm;
        }
        
        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        /* Page numbering */
        .page-number {
            position: fixed;
            bottom: 5mm;
            right: 12mm;
            font-size: 9pt;
            color: #6c757d;
        }
        
        .page-number:before {
            content: "Page " counter(page) " of " counter(pages);
        }
        
        /* Header styles are now in the print-header component */
        
        /* Laboratory Report Title */
        .lab-report-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            text-transform: uppercase;
            margin: 12px 0 8px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            letter-spacing: 1.2px;
        }
        
        /* Report Metadata - Below Title */
        .report-metadata {
            text-align: center;
            font-size: 8.5pt;
            color: #555;
            margin: 10px 0 15px 0;
            line-height: 1.6;
        }
        
        .report-metadata-row {
            margin: 2px 0;
        }
        
        .report-metadata-label {
            font-weight: 600;
            color: #333;
            margin-right: 6px;
        }
        
        .report-metadata-value {
            color: #495057;
        }
        
        /* Report Title */
        .report-title {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 15pt;
            font-weight: bold;
            color: white;
            background: linear-gradient(135deg, {{ $branding['primary_color'] ?? '#2c5aa0' }} 0%, #1a4278 100%);
            text-align: center;
            margin: 0 0 20px 0;
            padding: 15px 20px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .test-type-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 8.5pt;
            font-weight: bold;
            margin-left: 15px;
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        
        /* Patient Information Section */
        .patient-info {
            background: linear-gradient(to right, #f8f9fa 0%, #ffffff 100%);
            padding: 12px 15px;
            border: 2px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            border-radius: 6px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .patient-info-header {
            margin: 0 0 10px 0;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            padding-bottom: 6px;
        }
        
        /* Test Information Section */
        .test-info {
            background: linear-gradient(to right, #e3f2fd 0%, #ffffff 100%);
            padding: 12px 15px;
            border: 2px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            border-radius: 6px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .test-info-header {
            margin: 0 0 10px 0;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            padding-bottom: 6px;
        }
        
        .test-info-content {
            display: table;
            width: 100%;
        }
        
        .test-info-left {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            font-weight: 600;
            color: #495057;
            font-size: 9.5pt;
        }
        
        .test-info-right {
            display: table-cell;
            width: 70%;
            vertical-align: top;
            color: #212529;
            font-size: 9.5pt;
        }
        
        .test-badge {
            display: inline-block;
            background-color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            margin: 3px 5px 3px 0;
            font-size: 8.5pt;
            font-weight: 500;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: 600;
            color: #495057;
            width: 25%;
            padding: 5px 8px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            font-size: 9pt;
        }
        
        .info-value {
            display: table-cell;
            color: #212529;
            width: 25%;
            padding: 5px 8px;
            border: 1px solid #e9ecef;
            font-size: 9pt;
        }
        
        /* Priority Badge */
        .priority-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9pt;
        }
        
        .priority-urgent {
            background-color: #dc3545;
            color: white;
        }
        
        .priority-stat {
            background-color: #dc3545;
            color: white;
        }
        
        .priority-routine {
            background-color: #6c757d;
            color: white;
        }
        
        /* Critical Alert */
        .critical-alert {
            background-color: #fff5f5;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 12px 15px;
            margin: 12px 0;
            border-radius: 4px;
            page-break-inside: avoid;
        }
        
        .critical-alert-header {
            margin: 0 0 8px 0;
            color: #dc3545;
            font-size: 14pt;
            font-weight: bold;
        }
        
        .critical-alert ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        
        .critical-alert li {
            margin: 5px 0;
        }
        
        /* Test Section */
        .test-section {
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .test-header {
            background: linear-gradient(135deg, {{ $branding['primary_color'] ?? '#2c5aa0' }} 0%, #1a4278 100%);
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 14pt;
            margin: 0;
            page-break-after: avoid;
        }
        
        .test-content {
            padding: 15px;
            background-color: #ffffff;
        }
        
        /* Results Table */
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .results-table th {
            background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
            color: #495057;
            font-weight: bold;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #dee2e6;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            page-break-after: avoid;
        }
        
        .results-table td {
            padding: 7px 10px;
            border: 1px solid #dee2e6;
            font-size: 9pt;
            vertical-align: middle;
        }
        
        .results-table tbody tr {
            page-break-inside: avoid;
        }
        
        .results-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .parameter-name {
            font-weight: 600;
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
            font-size: 9pt;
        }
        
        /* Status Flags */
        .flag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8.5pt;
            margin-left: 5px;
            text-transform: uppercase;
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
        
        /* Narrative Content */
        .narrative-content {
            background-color: #f8f9fa;
            padding: 12px;
            border-left: 3px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            border-radius: 3px;
            margin: 10px 0;
            page-break-inside: avoid;
        }
        
        .narrative-content h4 {
            margin: 0 0 8px 0;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            font-size: 14pt;
            font-weight: bold;
        }
        
        .narrative-text {
            line-height: 1.5;
            color: #495057;
            font-size: 9pt;
        }
        
        /* QR Code Section */
        .qr-section {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            page-break-inside: avoid;
        }
        
        .qr-section img {
            width: 70px;
            height: 70px;
            border: 2px solid #dee2e6;
            padding: 4px;
            background-color: white;
        }
        
        .qr-label {
            font-size: 8pt;
            color: #6c757d;
            margin-top: 6px;
        }
        
        /* Signature Section */
        .signature-section {
            margin-top: 20px;
            display: table;
            width: 100%;
            table-layout: fixed;
            page-break-inside: avoid;
        }
        
        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 12px 8px;
            vertical-align: bottom;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 6px;
            height: 35px;
        }
        
        .signature-name {
            font-weight: 600;
            color: #495057;
            font-size: 8.5pt;
            margin-bottom: 2px;
        }
        
        .signature-label {
            font-size: 8pt;
            color: #6c757d;
            font-style: italic;
        }
        
        /* End of Report */
        .end-of-report {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            margin-top: 25px;
            padding: 15px;
            border-top: 3px double {{ $branding['primary_color'] ?? '#2c5aa0' }};
            border-bottom: 3px double {{ $branding['primary_color'] ?? '#2c5aa0' }};
            letter-spacing: 2px;
            page-break-inside: avoid;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 2px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            text-align: center;
            font-size: 8pt;
            color: #6c757d;
            line-height: 1.4;
            page-break-inside: avoid;
        }
        
        .footer-divider {
            margin: 5px 0;
        }
        
        /* Utility Classes */
        .page-break {
            page-break-before: always;
        }
        
        .no-results {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 30px;
            font-size: 14pt;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    @include('components.print-header')

    <!-- Laboratory Report Title -->
    <div class="lab-report-title">
        LABORATORY REPORT
    </div>

    <!-- Report Metadata - Below Title -->
    <div class="report-metadata">
        <div class="report-metadata-row">
            <span class="report-metadata-label">Report Date:</span>
            <span class="report-metadata-value">{{ $generated_at->format('l, F j, Y \a\t g:i A') }}</span>
        </div>
        <div class="report-metadata-row">
            <span class="report-metadata-label">Branch:</span>
            <span class="report-metadata-value">{{ $branch->name ?? 'Main Branch' }}@if(isset($branch->location)), {{ $branch->location }}@endif</span>
        </div>
        <div class="report-metadata-row">
            <span class="report-metadata-label">Laboratory Report Number:</span>
            <span class="report-metadata-value">{{ $labRequest->request_number }}</span>
        </div>
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
        <h3 class="patient-info-header">Patient Information</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Patient Name:</div>
                <div class="info-value">{{ $labRequest->patient->first_name }} {{ $labRequest->patient->other_names ?? '' }} {{ $labRequest->patient->last_name }}</div>
                <div class="info-label">Gender:</div>
                <div class="info-value">{{ $labRequest->patient->gender }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Patient ID:</div>
                <div class="info-value">{{ $labRequest->patient->id }}</div>
                <div class="info-label">Request Date:</div>
                <div class="info-value">{{ $labRequest->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date of Birth:</div>
                <div class="info-value">{{ $labRequest->patient->date_of_birth ? \Carbon\Carbon::parse($labRequest->patient->date_of_birth)->format('d/m/Y') : 'Not provided' }}</div>
                <div class="info-label">Priority:</div>
                <div class="info-value">
                    <span class="priority-badge priority-{{ strtolower($labRequest->priority) }}">
                        {{ strtoupper($labRequest->priority) }}
                    </span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Age:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($labRequest->patient->date_of_birth)->age }} years</div>
                <div class="info-label">Requested By:</div>
                <div class="info-value">
                    @if($labRequest->consultation_id)
                        Dr. {{ $labRequest->doctor->first_name ?? '' }} {{ $labRequest->doctor->last_name ?? '' }}
                    @else
                        Walk-in
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Test Information -->
    <div class="test-info">
        <h3 class="test-info-header">Test Information</h3>
        <div class="test-info-content">
            <div class="test-info-left">Tests Performed:</div>
            <div class="test-info-right">
                @php
                    $testNames = [];
                    foreach($resultsByTemplate as $templateId => $templateData) {
                        if(isset($templateData['template']) && $templateData['template']) {
                            $testNames[] = $templateData['template']->template_name;
                        }
                    }
                @endphp
                @if(count($testNames) > 0)
                    @foreach($testNames as $testName)
                        <span class="test-badge">{{ $testName }}</span>
                    @endforeach
                @else
                    <span class="test-badge">{{ $template->template_name ?? 'Laboratory Test' }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Critical Results Alert -->
    @if($labRequest->results->where('result_status', 'critical')->count() > 0)
        <div class="critical-alert">
            <h4 class="critical-alert-header">⚠️ CRITICAL RESULTS ALERT</h4>
            <p><strong>The following results require immediate medical attention:</strong></p>
            <ul>
                @foreach($labRequest->results->where('result_status', 'critical') as $criticalResult)
                    @php
                        $resultTemplate = $criticalResult->template ?? $criticalResult->labRequest->template ?? null;
                        $showRefInAlert = $resultTemplate && $resultTemplate->template_type === 'quantitative';
                    @endphp
                    <li><strong>{{ $criticalResult->parameter_name }}:</strong> {{ $criticalResult->result_value }} {{ $criticalResult->unit ?? '' }}@if($showRefInAlert) (Ref: {{ $criticalResult->reference_range ?? 'N/A' }})@endif</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Test Results by Template -->
    @if(count($resultsByTemplate) > 0)
        @foreach($resultsByTemplate as $templateId => $templateData)
            @php
                $sectionTemplate = $templateData['template'] ?? null;
                $sectionType = $sectionTemplate ? ($sectionTemplate->template_type ?? 'quantitative') : 'quantitative';
                $sectionTypeLabel = ucfirst($sectionType);
            @endphp
            <div class="test-section">
                <div class="test-header">
                    {{ $sectionTemplate ? $sectionTemplate->template_name : 'Test Results' }}
                    <span style="font-size: 9pt; font-weight: normal; opacity: 0.9;"> — {{ $sectionTypeLabel }}</span>
                    @if($sectionTemplate && $sectionTemplate->category)
                        <span style="font-size: 9pt; font-weight: normal; opacity: 0.9;"> ({{ $sectionTemplate->category }})</span>
                    @endif
                </div>
                <div class="test-content">
                    @if($sectionTemplate && $sectionTemplate->template_type === 'narrative')
                        <!-- Narrative Results -->
                        @foreach($templateData['results'] as $result)
                            <div class="narrative-content">
                                <h4>{{ $result->parameter_name }}</h4>
                                <div class="narrative-text">
                                    {!! nl2br(e($result->result_value)) !!}
                                </div>
                                @if($result->clinical_interpretation)
                                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #dee2e6;">
                                        <strong style="color: {{ $branding['primary_color'] ?? '#2c5aa0' }};">Clinical Interpretation:</strong><br>
                                        <div style="margin-top: 5px;">{!! nl2br(e($result->clinical_interpretation)) !!}</div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <!-- Tabular Results: layout depends on template type to avoid misinterpreting results -->
                        @php
                            $tplType = $sectionTemplate ? ($sectionTemplate->template_type ?? 'quantitative') : 'quantitative';
                            $isQuantitative = $tplType === 'quantitative';
                            $isQualitative = $tplType === 'qualitative';
                            $isCombined = $tplType === 'combined';
                            $showRefRange = $isQuantitative || $isCombined;
                            $showUnitCol = $isQuantitative || $isCombined;
                        @endphp
                        @if($isQualitative)
                            {{-- Qualitative: Parameter | Result | Status only (no Unit/Reference Range to avoid implying numeric interpretation) --}}
                            <p class="mb-2" style="font-size: 8.5pt; color: #6c757d;"><strong>Qualitative results</strong> — Positive/Negative or categorical findings. Do not interpret as numeric values.</p>
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th style="width: 45%;">Parameter</th>
                                        <th style="width: 40%;">Result</th>
                                        <th style="width: 15%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templateData['results'] as $result)
                                        <tr>
                                            <td class="parameter-name">{{ $result->parameter_name }}</td>
                                            <td class="result-value" style="{{ $pdfService->getResultStatusStyle($result) }}">
                                                {{ $pdfService->formatResultValue($result, $result->parameter ?? $result) }}
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="flag flag-{{ $result->result_status }}">{{ strtoupper($result->result_status ?? 'N/A') }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            {{-- Quantitative or Combined: Parameter | Result | Unit | Reference Range (when applicable) | Status --}}
                            @if($isCombined)
                                <p class="mb-2" style="font-size: 8.5pt; color: #6c757d;"><strong>Combined results</strong> — Numeric parameters include units and reference ranges; categorical parameters may show N/A where not applicable.</p>
                            @endif
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th style="width: 28%;">Parameter</th>
                                        <th style="width: 18%;">Result</th>
                                        @if($showUnitCol)
                                            <th style="width: 12%;">Unit</th>
                                        @endif
                                        @if($showRefRange)
                                            <th style="width: 22%;">Reference Range</th>
                                        @endif
                                        <th style="width: 12%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templateData['results'] as $result)
                                        <tr>
                                            <td class="parameter-name">{{ $result->parameter_name }}</td>
                                            <td class="result-value" style="{{ $pdfService->getResultStatusStyle($result) }}">
                                                {{ $pdfService->formatResultValue($result, $result->parameter ?? $result) }}
                                                @if($result->abnormal_flag ?? null)
                                                    <span class="flag flag-{{ $result->result_status }}" style="{{ $pdfService->getAbnormalFlagStyle($result->abnormal_flag) }}">{{ $result->abnormal_flag }}</span>
                                                @endif
                                            </td>
                                            @if($showUnitCol)
                                                <td style="text-align: center;">{{ $result->unit ?? '-' }}</td>
                                            @endif
                                            @if($showRefRange)
                                                <td class="reference-range">{{ $pdfService->getReferenceRangeText($result, $result->parameter ?? $result) }}</td>
                                            @endif
                                            <td style="text-align: center;">
                                                <span class="flag flag-{{ $result->result_status }}">{{ strtoupper($result->result_status ?? 'N/A') }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @endif

                    <!-- Clinical Interpretation Section -->
                    @if($templateData['results']->where('clinical_interpretation')->count() > 0)
                        <div class="narrative-content">
                            <h4>Clinical Interpretation</h4>
                            @foreach($templateData['results']->where('clinical_interpretation') as $result)
                                @if($result->clinical_interpretation)
                                    <div class="narrative-text" style="margin-bottom: 10px;">
                                        <strong>{{ $result->parameter_name }}:</strong><br>
                                        {!! nl2br(e($result->clinical_interpretation)) !!}
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="no-results">
            No test results available for this request.
        </div>
    @endif

    <!-- QR Code for Verification -->
    <div class="qr-section">
        <img src="{{ $pdfService->generateQRCode($labRequest->id) }}" alt="QR Code">
        <div class="qr-label">Scan to verify results online</div>
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
            <div class="signature-label">Approved By (Pathologist)</div>
        </div>
    </div>

    <!-- End of Report -->
    <div class="end-of-report">
        ═══ END OF REPORT ═══
    </div>

    <!-- Footer -->
    <div class="footer">
        <strong>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? ($hospitalBranding['name'] ?? 'Hospital') . ' Medical Center' }}</strong>
        <div class="footer-divider">
            {{ $branding['business_address'] ?? $settings['hospital_address'] ?? '123 Medical Street, Accra, Ghana' }}
        </div>
        <div class="footer-divider">
            Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? '+233 24 123 4567' }} | 
            Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'info@nexthospital.com' }}
        </div>
        <div style="margin-top: 8px; font-style: italic;">
            This report was generated on {{ $generated_at->format('d/m/Y H:i') }} and is valid for 30 days from the date of issue.
        </div>
    </div>
    
    <!-- Page Number (Footer) -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->getFont("DejaVu Sans");
            $size = 9;
            $pageWidth = $pdf->get_width();
            $pageHeight = $pdf->get_height();
            $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $pageWidth - $textWidth - 35;
            $y = $pageHeight - 25;
            $pdf->text($x, $y, $text, $font, $size, array(0.5, 0.5, 0.5));
        }
    </script>
</body>
</html>
