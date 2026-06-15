<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title ?? 'Eye Test Results Report' }}</title>
    <style>
        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            border-bottom: 3px solid {{ $branding['primary_color'] ?? '#007bff' }};
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
            color: {{ $branding['primary_color'] ?? '#007bff' }};
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
            color: {{ $branding['primary_color'] ?? '#007bff' }};
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .patient-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .patient-info h3 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 14px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 30%;
            padding: 2px 0;
        }
        
        .info-value {
            display: table-cell;
            padding: 2px 0;
        }
        
        .test-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .test-info h3 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 14px;
        }
        
        .results-section {
            margin-bottom: 30px;
        }
        
        .results-section h3 {
            color: #007bff;
            border-bottom: 1px solid #007bff;
            padding-bottom: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .results-table th,
        .results-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .results-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .results-table .parameter-name {
            font-weight: bold;
            width: 30%;
        }
        
        .results-table .result-value {
            width: 20%;
        }
        
        .results-table .reference-range {
            width: 20%;
            color: #666;
        }
        
        .results-table .status {
            width: 15%;
            text-align: center;
        }
        
        .results-table .notes {
            width: 15%;
        }
        
        .status-normal {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-abnormal {
            color: #ffc107;
            font-weight: bold;
        }
        
        .status-critical {
            color: #dc3545;
            font-weight: bold;
        }
        
        .images-section {
            margin-bottom: 30px;
        }
        
        .images-section h3 {
            color: #007bff;
            border-bottom: 1px solid #007bff;
            padding-bottom: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .image-grid {
            display: table;
            width: 100%;
        }
        
        .image-row {
            display: table-row;
        }
        
        .image-cell {
            display: table-cell;
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        
        .image-placeholder {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            color: #666;
            background-color: #f8f9fa;
        }
        
        .interpretation {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .interpretation h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 13px;
        }
        
        .recommendations {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .recommendations h4 {
            margin: 0 0 10px 0;
            color: #0c5460;
            font-size: 13px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        
        .signature-section {
            margin-top: 30px;
        }
        
        .signature-row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .signature-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 20px;
        }
        
        .signature-label {
            font-size: 10px;
            color: #666;
        }
        
        .report-meta {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-results {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
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
                    <strong>Branch:</strong> {{ $branch->name ?? 'Main Branch' }}<br>
                    <strong>Report #:</strong> {{ $report_number }}
                </div>
            </div>
        </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
        EYE EXAMINATION REPORT
    </div>
    
    <!-- Report Meta -->
    <div class="report-meta">
        <p><strong>Report Number:</strong> {{ $report_number }}</p>
        <p><strong>Generated:</strong> {{ $generated_at->format('d/m/Y H:i') }}</p>
    </div>
    
    <!-- Patient Information -->
    <div class="patient-info">
        <h3>Patient Information</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Patient Name:</div>
                <div class="info-value">{{ $patient->first_name }} {{ $patient->last_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Patient ID:</div>
                <div class="info-value">{{ $patient->patient_id }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date of Birth:</div>
                <div class="info-value">{{ $patient->date_of_birth ? $patient->date_of_birth->format('d/m/Y') : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Gender:</div>
                <div class="info-value">{{ $patient->gender ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Contact:</div>
                <div class="info-value">{{ $patient->contact ?? 'N/A' }}</div>
            </div>
        </div>
    </div>
    
    <!-- Test Information -->
    <div class="test-info">
        <h3>Examination Details</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Test Date:</div>
                <div class="info-value">{{ $testRequest->created_at->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Test Time:</div>
                <div class="info-value">{{ $testRequest->created_at->format('H:i') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Service:</div>
                <div class="info-value">{{ $service->service_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Test Type:</div>
                <div class="info-value">{{ $template->template_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Doctor:</div>
                <div class="info-value">{{ $doctor->firstname }} {{ $doctor->lastname }}</div>
            </div>
            @if($optometrist)
            <div class="info-row">
                <div class="info-label">Optometrist:</div>
                <div class="info-value">{{ $optometrist->firstname }} {{ $optometrist->lastname }}</div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Test Results -->
    @if(count($results) > 0)
        @foreach($results as $category => $categoryResults)
            @if(count($categoryResults) > 0)
                <div class="results-section">
                    <h3>{{ ucfirst($category) }} Results</h3>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Result</th>
                                <th>Reference Range</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryResults as $result)
                                <tr>
                                    <td class="parameter-name">{{ $result->parameter_name }}</td>
                                    <td class="result-value">{{ $result->display_value }}</td>
                                    <td class="reference-range">{{ $result->getReferenceRangeWithUnit() }}</td>
                                    <td class="status status-{{ $result->result_status }}">
                                        {{ $result->formatted_status }}
                                        @if($result->abnormal_flag)
                                            <br><small>({{ $result->abnormal_flag_display }})</small>
                                        @endif
                                    </td>
                                    <td class="notes">{{ $result->technical_notes ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    @else
        <div class="results-section">
            <h3>Test Results</h3>
            <div class="no-results">No results available for this test.</div>
        </div>
    @endif
    
    <!-- Images Section -->
    @if(count($images) > 0)
        <div class="images-section">
            <h3>Examination Images</h3>
            <div class="image-grid">
                @foreach($images as $imageType => $imageList)
                    <div class="image-row">
                        <div class="image-cell">
                            <h4>{{ ucfirst(str_replace('_', ' ', $imageType)) }}</h4>
                            @foreach($imageList as $image)
                                <div class="image-placeholder">
                                    <p>{{ $image->description ?? 'Image: ' . $image->original_filename }}</p>
                                    <p><small>Uploaded: {{ $image->created_at->format('d/m/Y H:i') }}</small></p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    <!-- Clinical Interpretation -->
    @if($testRequest->testResults->where('clinical_interpretation', '!=', null)->count() > 0)
        <div class="interpretation">
            <h4>Clinical Interpretation</h4>
            @foreach($testRequest->testResults->where('clinical_interpretation', '!=', null) as $result)
                <p><strong>{{ $result->parameter_name }}:</strong> {{ $result->clinical_interpretation }}</p>
            @endforeach
        </div>
    @endif
    
    <!-- Recommendations -->
    @if($testRequest->comments->where('comment_type', 'recommendation')->count() > 0)
        <div class="recommendations">
            <h4>Recommendations</h4>
            @foreach($testRequest->comments->where('comment_type', 'recommendation') as $comment)
                <p>{{ $comment->comment_content }}</p>
            @endforeach
        </div>
    @endif
    
    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature-row">
            <div class="signature-cell">
                <div class="signature-line"></div>
                <div class="signature-label">Optometrist/Ophthalmologist</div>
                @if($optometrist)
                    <p>{{ $optometrist->firstname }} {{ $optometrist->lastname }}</p>
                @endif
            </div>
            <div class="signature-cell">
                <div class="signature-line"></div>
                <div class="signature-label">Verifying Doctor</div>
                @if($verifier)
                    <p>{{ $verifier->firstname }} {{ $verifier->lastname }}</p>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div style="text-align: center;">
            <strong>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}</strong><br>
            {{ $branding['business_address'] ?? $settings['hospital_address'] ?? 'Hospital Address' }}<br>
            Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? 'Phone Number' }} | 
            Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'Email Address' }}<br>
            <em>This report was generated on {{ $generated_at->format('d/m/Y H:i') }}</em><br>
            <strong>Disclaimer:</strong> This report is for medical purposes only and should be interpreted by qualified healthcare professionals.
        </div>
    </div>
</body>
</html>
