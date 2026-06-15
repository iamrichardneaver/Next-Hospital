<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title ?? 'Patient Radiology History' }}</title>
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
        
        .section-title {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .history-table th,
        .history-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        
        .history-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .history-table tr:nth-child(even) {
            background-color: #f8f9fa;
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
        
        .status-scheduled {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-in-progress {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .report-status {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        
        .summary-stats {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
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
        
        .page-break {
            page-break-before: always;
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
                    <strong>Report ID:</strong> {{ $reportId ?? 'N/A' }}
                </div>
            </div>
        </div>
        <div class="report-title">PATIENT RADIOLOGY HISTORY</div>
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

    <!-- Summary Statistics -->
    <div class="summary-stats no-break">
        <div class="section-title">SUMMARY STATISTICS</div>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">{{ $radiologyHistory->count() }}</div>
                <div class="stat-label">Total Studies</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $radiologyHistory->where('status', 'completed')->count() }}</div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $radiologyHistory->where('status', 'scheduled')->count() }}</div>
                <div class="stat-label">Scheduled</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $radiologyHistory->where('study.report', '!=', null)->count() }}</div>
                <div class="stat-label">With Reports</div>
            </div>
        </div>
    </div>

    <!-- Radiology History -->
    <div class="section-title">RADIOLOGY HISTORY</div>
    @if($radiologyHistory->count() > 0)
    <table class="history-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Modality</th>
                <th>Study Description</th>
                <th>Referring Doctor</th>
                <th>Status</th>
                <th>Report</th>
            </tr>
        </thead>
        <tbody>
            @foreach($radiologyHistory as $request)
            <tr>
                <td>{{ \Carbon\Carbon::parse($request->requested_date)->format('M d, Y') }}</td>
                <td>{{ $request->modality->name ?? 'N/A' }}</td>
                <td>{{ $request->study->study_description ?? $request->clinical_question ?? 'N/A' }}</td>
                <td>{{ $request->doctor->first_name ?? 'N/A' }} {{ $request->doctor->last_name ?? '' }}</td>
                <td>
                    <span class="status-badge status-{{ $request->status }}">{{ $request->status }}</span>
                    @if($request->study)
                    <div class="report-status">
                        Study: {{ $request->study->status }}
                    </div>
                    @endif
                </td>
                <td>
                    @if($request->study && $request->study->report)
                        <span class="status-badge status-{{ $request->study->report->status }}">
                            {{ $request->study->report->status }}
                        </span>
                        <div class="report-status">
                            {{ \Carbon\Carbon::parse($request->study->report->dictated_date)->format('M d, Y') }}
                        </div>
                    @else
                        <span style="color: #666; font-size: 10px;">No Report</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="text-align: center; padding: 40px; color: #666;">
        <p>No radiology studies found for this patient.</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This radiology history was generated on {{ $generated_at->format('M d, Y H:i:s') }}</p>
        <p>Patient ID: {{ $patient->id }} | Report covers {{ $radiologyHistory->count() }} studies</p>
        <p>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Next Hospital' }} - Radiology Department</p>
    </div>
</body>
</html>
