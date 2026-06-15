<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title ?? 'Radiology Report' }}</title>
    <style>
        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
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
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-top: 15px;
        }
        
        .patient-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #495057;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        .study-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .section-title {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .report-content {
            margin: 20px 0;
        }
        
        .findings, .impression, .recommendations {
            margin-bottom: 20px;
        }
        
        .content-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .content-text {
            background-color: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #007bff;
            border-radius: 0 5px 5px 0;
            white-space: pre-wrap;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            height: 40px;
            margin-bottom: 5px;
        }
        
        .signature-label {
            font-size: 10px;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-final {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-preliminary {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-draft {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-break {
            page-break-inside: avoid;
        }

        .image-gallery {
            margin: 15px 0;
            clear: both;
        }

        .report-image {
            max-width: 240px;
            max-height: 180px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            object-fit: contain;
            margin: 5px;
            float: left;
        }

        .image-caption {
            font-size: 10px;
            color: #666;
            clear: both;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                @if(isset($branding['logo_base64']) && $branding['logo_base64'])
                    <div style="margin-bottom: 10px;">
                        <img src="{{ $branding['logo_base64'] }}" alt="{{ $branding['business_name'] ?? 'Logo' }}" style="height: 40px; max-width: 200px; object-fit: contain;" />
                    </div>
                @endif
                <div class="hospital-name">{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Next Hospital' }}</div>
                <div class="hospital-details">
                    {{ $branding['business_address'] ?? $settings['hospital_address'] ?? '123 Medical Street, Accra' }}<br>
                    Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? '+233 24 123 4567' }} | 
                    Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'info@nexthospital.com' }}
                </div>
            </div>
            <div class="header-right">
                <div style="font-size: 9pt; color: #666;">
                    <strong>Report Date:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
                    <strong>Branch:</strong> {{ $branch->name ?? 'Main Branch' }}<br>
                    <strong>Report ID:</strong> {{ $report->id ?? 'N/A' }}
                </div>
            </div>
        </div>
        <div class="report-title">RADIOLOGY REPORT</div>
    </div>

    <!-- Patient Information -->
    <div class="patient-info no-break">
        <div class="info-row">
            <div class="info-label">Patient Name:</div>
            <div class="info-value">{{ $patient->first_name }} {{ $patient->last_name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Patient ID:</div>
            <div class="info-value">{{ $patient->patient_number ?? $patient->id }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Date of Birth:</div>
            <div class="info-value">{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('M d, Y') : 'Not provided' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Gender:</div>
            <div class="info-value">{{ ucfirst($patient->gender) }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Phone:</div>
            <div class="info-value">{{ $patient->phone }}</div>
        </div>
        @if($patient->email)
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value">{{ $patient->email }}</div>
        </div>
        @endif
    </div>

    <!-- Study Information -->
    <div class="study-info no-break">
        <div class="info-row">
            <div class="info-label">Study Date:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($study->study_date)->format('M d, Y H:i') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Modality:</div>
            <div class="info-value">{{ $modality->name ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Study Description:</div>
            <div class="info-value">{{ $study->study_description ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Equipment:</div>
            <div class="info-value">{{ $equipment->name ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Technician:</div>
            <div class="info-value">{{ ($technician && $technician->user) ? ($technician->user->first_name ?? 'N/A') . ' ' . ($technician->user->last_name ?? '') : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Referring Doctor:</div>
            <div class="info-value">{{ $doctor->first_name ?? 'N/A' }} {{ $doctor->last_name ?? '' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Report Status:</div>
            <div class="info-value">
                <span class="status-badge status-{{ $report->status }}">{{ $report->status }}</span>
            </div>
        </div>
    </div>

    <!-- Clinical History -->
    @if($study->request && $study->request->clinical_history)
    <div class="section-title">CLINICAL HISTORY</div>
    <div class="report-content">
        <div class="content-text">{{ $study->request->clinical_history }}</div>
    </div>
    @endif

    <!-- Technique -->
    @if($study->technique_notes)
    <div class="section-title">TECHNIQUE</div>
    <div class="report-content">
        <div class="content-text">{{ $study->technique_notes }}</div>
    </div>
    @endif

    <!-- Findings -->
    <div class="section-title">FINDINGS</div>
    <div class="report-content findings">
        <div class="content-text">{!! $report->findings !!}</div>
    </div>

    <!-- Impression -->
    <div class="section-title">IMPRESSION</div>
    <div class="report-content impression">
        <div class="content-text">{!! $report->impression !!}</div>
    </div>

    <!-- Recommendations -->
    @if($report->recommendations)
    <div class="section-title">RECOMMENDATIONS</div>
    <div class="report-content recommendations">
        <div class="content-text">{!! $report->recommendations !!}</div>
    </div>
    @endif

    <!-- Attached Study Images -->
    @if(!empty($reportImages))
    <div class="section-title page-break">ATTACHED IMAGES</div>
    <div class="image-gallery no-break">
        @foreach($reportImages as $image)
            <img src="{{ $image['base64'] }}"
                 alt="{{ $image['label'] }}"
                 class="report-image" />
        @endforeach
    </div>
    <div class="image-caption">
        {{ count($reportImages) }} image(s) attached to this report.
        @if(!empty($report->selected_images) && count($report->selected_images) > count($reportImages))
            Some selected images could not be embedded (unsupported format or missing file).
        @endif
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section no-break">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Radiologist Signature</div>
            <div style="margin-top: 5px; font-size: 10px;">
                {{ $radiologist->first_name ?? 'Dr.' }} {{ $radiologist->last_name ?? 'Radiologist' }}
            </div>
            <div style="font-size: 9px; color: #666;">
                {{ $report->signed_date ? \Carbon\Carbon::parse($report->signed_date)->format('M d, Y') : 'Not Signed' }}
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Date</div>
            <div style="margin-top: 5px; font-size: 10px;">
                {{ \Carbon\Carbon::parse($report->dictated_date)->format('M d, Y') }}
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This report was generated on {{ $generated_at->format('M d, Y H:i:s') }}</p>
        <p>Report ID: {{ $report->id }} | Study ID: {{ $study->id }}</p>
        <p>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Next Hospital' }} - Radiology Department</p>
    </div>
</body>
</html>
