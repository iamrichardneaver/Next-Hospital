<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title ?? 'Radiology Study Summary' }}</title>
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
            page-break-inside: avoid;
        }
        
        .info-row {
            margin-bottom: 8px;
            clear: both;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #495057;
            float: left;
        }
        
        .info-value {
            color: #333;
            margin-left: 160px;
            word-wrap: break-word;
        }
        
        .study-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .section-title {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .study-parameters {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .parameter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .parameter-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .parameter-label {
            font-weight: bold;
            color: #495057;
        }
        
        .parameter-value {
            color: #333;
        }
        
        .series-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .series-table th,
        .series-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        
        .series-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-in-progress {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-scheduled {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .no-break {
            page-break-inside: avoid;
        }
        
        .avoid-page-break {
            page-break-after: avoid;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .image-gallery {
            margin: 15px 0;
            clear: both;
        }
        
        .study-image {
            max-width: 180px;
            max-height: 120px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            object-fit: contain;
            margin: 5px;
            float: left;
        }
        
        .content-text {
            line-height: 1.5;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .series-images {
            margin-top: 10px;
        }
        
        .series-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
            font-size: 11px;
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
                    <strong>Summary ID:</strong> {{ $summaryId ?? 'N/A' }}
                </div>
            </div>
        </div>
        <div class="report-title">RADIOLOGY STUDY SUMMARY</div>
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
    </div>

    <!-- Study Information -->
    <div class="study-info no-break">
        <div class="info-row">
            <div class="info-label">Study Date:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($study->study_date)->format('M d, Y H:i') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Completed Date:</div>
            <div class="info-value">{{ $study->completed_date ? \Carbon\Carbon::parse($study->completed_date)->format('M d, Y H:i') : 'Not Completed' }}</div>
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
            <div class="info-value">{{ $technician->user->first_name ?? 'N/A' }} {{ $technician->user->last_name ?? '' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Radiologist:</div>
            <div class="info-value">{{ $radiologist->first_name ?? 'N/A' }} {{ $radiologist->last_name ?? '' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Referring Doctor:</div>
            <div class="info-value">{{ $doctor->first_name ?? 'N/A' }} {{ $doctor->last_name ?? '' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Study Status:</div>
            <div class="info-value">
                <span class="status-badge status-{{ $study->status }}">{{ $study->status }}</span>
            </div>
        </div>
    </div>

    <!-- Clinical History -->
    @if($study->request->clinical_history)
    <div class="section-title">CLINICAL HISTORY</div>
    <div class="study-parameters">
        <div class="content-text">{!! $study->request->clinical_history !!}</div>
    </div>
    @endif

    <!-- Study Parameters -->
    @if($study->study_parameters)
    <div class="section-title">STUDY PARAMETERS</div>
    <div class="study-parameters">
        <div class="parameter-grid">
            @foreach($study->study_parameters as $key => $value)
            <div class="parameter-item">
                <span class="parameter-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                <span class="parameter-value">{{ is_array($value) ? json_encode($value) : $value }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Technique Notes -->
    @if($study->technique_notes)
    <div class="section-title">TECHNIQUE NOTES</div>
    <div class="study-parameters">
        <div class="content-text">{!! $study->technique_notes !!}</div>
    </div>
    @endif

    <!-- DICOM Series Information -->
    @if($study->series && $study->series->count() > 0)
    <div class="section-title avoid-page-break">DICOM SERIES INFORMATION</div>
    <table class="series-table">
        <thead>
            <tr>
                <th>Series Number</th>
                <th>Series Description</th>
                <th>Body Part</th>
                <th>View Position</th>
                <th>Images</th>
            </tr>
        </thead>
        <tbody>
            @foreach($study->series as $series)
            <tr class="no-break">
                <td>{{ $series->series_number }}</td>
                <td>{{ $series->series_description }}</td>
                <td>{{ $series->body_part_examined ?? 'N/A' }}</td>
                <td>{{ $series->view_position ?? 'N/A' }}</td>
                <td>{{ $series->images->count() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <!-- Study Images -->
    @if(!empty($studyImagesBySeries))
    <div class="section-title avoid-page-break">STUDY IMAGES</div>
    <div class="image-gallery">
        @foreach($studyImagesBySeries as $seriesGroup)
            <div class="series-images">
                <div class="series-title">Series {{ $seriesGroup['series_number'] }}: {{ $seriesGroup['series_description'] }}</div>
                @foreach($seriesGroup['images'] as $image)
                    <img src="{{ $image['base64'] }}"
                         alt="{{ $image['label'] }}"
                         class="study-image" />
                @endforeach
                @if($seriesGroup['remaining_count'] > 0)
                    <div style="font-size: 10px; color: #666; font-style: italic; clear: both;">
                        ... and {{ $seriesGroup['remaining_count'] }} more images
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    @endif
    @endif

    <!-- Report Status -->
    @if($study->report)
    <div class="section-title">REPORT STATUS</div>
    <div class="study-parameters">
        <div class="info-row">
            <div class="info-label">Report Status:</div>
            <div class="info-value">
                <span class="status-badge status-{{ $study->report->status }}">{{ $study->report->status }}</span>
            </div>
        </div>
        @if($study->report->dictated_date)
        <div class="info-row">
            <div class="info-label">Dictated Date:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($study->report->dictated_date)->format('M d, Y H:i') }}</div>
        </div>
        @endif
        @if($study->report->signed_date)
        <div class="info-row">
            <div class="info-label">Signed Date:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($study->report->signed_date)->format('M d, Y H:i') }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This study summary was generated on {{ $generated_at->format('M d, Y H:i:s') }}</p>
        <p>Study ID: {{ $study->id }} | Request ID: {{ $study->request_id }}</p>
        <p>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Next Hospital' }} - Radiology Department</p>
    </div>
</body>
</html>
