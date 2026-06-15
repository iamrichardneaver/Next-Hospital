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
        
        .period-info {
            background-color: #d1ecf1;
            padding: 12px;
            border: 1px solid #bee5eb;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            color: #0c5460;
        }
        
        .section {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        
        .section h3 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
            font-size: 14pt;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .data-table th,
        .data-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        
        .data-table th {
            background-color: #2c5aa0;
            color: white;
            font-weight: bold;
            font-size: 9pt;
        }
        
        .data-table tr:nth-child(even) {
            background-color: white;
        }
        
        .data-table td {
            font-size: 9pt;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        
        .summary-item {
            display: table-cell;
            padding: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
            background-color: white;
        }
        
        .summary-label {
            font-weight: bold;
            color: #666;
            font-size: 9pt;
        }
        
        .summary-value {
            font-size: 23pt;
            font-weight: bold;
            color: #2c5aa0;
            margin-top: 5px;
        }
        
        .info-row {
            margin-bottom: 8px;
            display: table;
            width: 100%;
        }
        
        .info-label {
            font-weight: bold;
            display: table-cell;
            width: 40%;
        }
        
        .info-value {
            display: table-cell;
            width: 60%;
        }
        
        .signature-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        
        .signature-block {
            display: table-cell;
            width: 50%;
            padding: 10px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            text-align: center;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
        
        .note-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .note-box strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <!-- Header Section - Cross-Platform Compatible -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                @if(isset($branding['logo_base64']) && $branding['logo_base64'])
                    <div style="margin-bottom: 10px;">
                        <img src="{{ $branding['logo_base64'] }}" alt="{{ $branding['business_name'] ?? 'Logo' }}" style="height: 40px; max-width: 200px; object-fit: contain;" />
                    </div>
                @elseif(isset($branding['logo_absolute_path']) && $branding['logo_absolute_path'] && file_exists($branding['logo_absolute_path']))
                    <div style="margin-bottom: 10px;">
                        <img src="{{ $branding['logo_absolute_path'] }}" alt="{{ $branding['business_name'] ?? 'Logo' }}" style="height: 40px; max-width: 200px; object-fit: contain;" />
                    </div>
                @endif
                <div class="hospital-name">{{ $branding['business_name'] ?? 'Hospital Name' }}</div>
                <div class="hospital-details">
                    {{ $branding['business_address'] ?? 'Hospital Address' }}<br>
                    Tel: {{ $branding['business_phone'] ?? 'Phone Number' }} | 
                    Email: {{ $branding['business_email'] ?? 'Email Address' }}<br>
                    @if(isset($branding['business_website']) && $branding['business_website'])
                        Website: {{ $branding['business_website'] }}
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div style="font-size: 9pt; color: #666;">
                    <strong>Report Generated:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
                    @if($branch)
                        <strong>Branch:</strong> {{ $branch->name }}<br>
                    @endif
                    <strong>Report Type:</strong> GHS Health Report
                </div>
            </div>
        </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
        GHANA HEALTH SERVICE (GHS) HEALTH REPORT
    </div>

    <!-- Period Information -->
    <div class="period-info">
        Report Period: {{ $period['start'] ?? 'N/A' }} to {{ $period['end'] ?? 'N/A' }}
    </div>

    <!-- Facility Information -->
    <div class="section">
        <h3>FACILITY INFORMATION</h3>
        <div class="info-row">
            <span class="info-label">Facility Name:</span>
            <span class="info-value">{{ $branding['business_name'] ?? 'Hospital Name' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Facility Address:</span>
            <span class="info-value">{{ $branding['business_address'] ?? 'Hospital Address' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Contact:</span>
            <span class="info-value">{{ $branding['business_phone'] ?? 'Phone Number' }}</span>
        </div>
        @if($branch)
        <div class="info-row">
            <span class="info-label">Branch:</span>
            <span class="info-value">{{ $branch->name }}</span>
        </div>
        @endif
    </div>

    <!-- Disease Statistics -->
    @if(isset($data['disease_stats']) && count($data['disease_stats']) > 0)
    <div class="section">
        <h3>DISEASE STATISTICS</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Disease/Condition</th>
                    <th style="width: 15%;">ICD-10 Code</th>
                    <th style="width: 15%;">Male Cases</th>
                    <th style="width: 15%;">Female Cases</th>
                    <th style="width: 10%;">Total Cases</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['disease_stats'] as $index => $disease)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $disease['name'] ?? 'N/A' }}</td>
                    <td>{{ $disease['icd_code'] ?? '-' }}</td>
                    <td style="text-align: center;">{{ $disease['male_count'] ?? 0 }}</td>
                    <td style="text-align: center;">{{ $disease['female_count'] ?? 0 }}</td>
                    <td style="text-align: center;"><strong>{{ $disease['total_count'] ?? 0 }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Patient Demographics -->
    @if(isset($data['demographics']))
    <div class="section">
        <h3>PATIENT DEMOGRAPHICS</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Patients</div>
                <div class="summary-value">{{ $data['demographics']['total_patients'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Male Patients</div>
                <div class="summary-value">{{ $data['demographics']['male_patients'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Female Patients</div>
                <div class="summary-value">{{ $data['demographics']['female_patients'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Service Statistics -->
    @if(isset($data['service_stats']))
    <div class="section">
        <h3>SERVICE STATISTICS</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">OPD Visits</div>
                <div class="summary-value">{{ $data['service_stats']['opd_visits'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">IPD Admissions</div>
                <div class="summary-value">{{ $data['service_stats']['ipd_admissions'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Lab Tests</div>
                <div class="summary-value">{{ $data['service_stats']['lab_tests'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Imaging Studies</div>
                <div class="summary-value">{{ $data['service_stats']['imaging_studies'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Maternal Health (if available) -->
    @if(isset($data['maternal_health']))
    <div class="section">
        <h3>MATERNAL HEALTH STATISTICS</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Indicator</th>
                    <th style="text-align: center;">Count</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Antenatal Care (ANC) Visits</td>
                    <td style="text-align: center;">{{ $data['maternal_health']['anc_visits'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td>Deliveries</td>
                    <td style="text-align: center;">{{ $data['maternal_health']['deliveries'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td>Postnatal Care (PNC) Visits</td>
                    <td style="text-align: center;">{{ $data['maternal_health']['pnc_visits'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td>Maternal Deaths</td>
                    <td style="text-align: center;">{{ $data['maternal_health']['maternal_deaths'] ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Child Health (if available) -->
    @if(isset($data['child_health']))
    <div class="section">
        <h3>CHILD HEALTH STATISTICS</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Indicator</th>
                    <th style="text-align: center;">Count</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Immunizations Given</td>
                    <td style="text-align: center;">{{ $data['child_health']['immunizations'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td>Under-5 Consultations</td>
                    <td style="text-align: center;">{{ $data['child_health']['under5_consultations'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td>Child Deaths (Under 5)</td>
                    <td style="text-align: center;">{{ $data['child_health']['child_deaths'] ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Important Note -->
    <div class="note-box">
        <strong>Note:</strong> This report is prepared in compliance with Ghana Health Service (GHS) reporting requirements. 
        All data is accurate as of the report generation date.
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div style="display: table; width: 100%;">
            <div class="signature-block">
                <div class="signature-line">
                    <strong>Prepared By</strong><br>
                    Medical Records Officer
                </div>
            </div>
            <div class="signature-block">
                <div class="signature-line">
                    <strong>Date</strong><br>
                    {{ $generated_at->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This is an official GHS health report generated by {{ $branding['business_name'] ?? 'Next Hospital' }} computerized system.</p>
        <p>{{ $branding['business_name'] ?? 'Next Hospital' }} | {{ $branding['business_phone'] ?? '' }} | {{ $branding['business_email'] ?? '' }}</p>
    </div>
</body>
</html>

