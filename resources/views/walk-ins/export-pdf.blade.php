<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walk-Ins Register - {{ $date }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 landscape;
        }
        
        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            border-bottom: 3px solid #2c5aa0;
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
            color: #2c5aa0;
            margin-bottom: 5px;
        }
        
        .hospital-details {
            font-size: 8pt;
            color: #666;
            line-height: 1.3;
        }
        
        .report-title {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 14pt;
            font-weight: bold;
            color: #2c5aa0;
            text-align: center;
            margin: 15px 0;
            padding: 8px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .statistics {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
        }
        
        .stat-item {
            display: table-cell;
            width: 16.66%;
            text-align: center;
            padding: 5px;
        }
        
        .stat-value {
            font-size: 23pt;
            font-weight: bold;
            color: #2c5aa0;
        }
        
        .stat-label {
            font-size: 8pt;
            color: #666;
            margin-top: 3px;
        }
        
        .visits-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .visits-table th {
            background-color: #2c5aa0;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 8pt;
            font-weight: bold;
            border: 1px solid #1e4070;
        }
        
        .visits-table td {
            padding: 6px 5px;
            border: 1px solid #dee2e6;
            font-size: 8pt;
        }
        
        .visits-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .badge {
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
            display: inline-block;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .badge-primary {
            background-color: #2c5aa0;
            color: white;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #666;
            padding: 10px 0;
            border-top: 1px solid #dee2e6;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
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
                @elseif(isset($branding['logo_absolute_path']) && $branding['logo_absolute_path'] && file_exists($branding['logo_absolute_path']))
                    <div style="margin-bottom: 10px;">
                        <img src="{{ $branding['logo_absolute_path'] }}" alt="{{ $branding['business_name'] ?? 'Logo' }}" style="height: 40px; max-width: 200px; object-fit: contain;" />
                    </div>
                @endif
                <div class="hospital-name">{{ $branding['business_name'] ?? 'Next Hospital' }}</div>
                <div class="hospital-details">
                    {{ $branding['business_address'] ?? 'Hospital Address' }}<br>
                    Tel: {{ $branding['business_phone'] ?? 'Phone Number' }} | 
                    Email: {{ $branding['business_email'] ?? 'Email Address' }}
                </div>
            </div>
            <div class="header-right">
                <div style="font-size: 9pt; color: #666;">
                    <strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}<br>
                    <strong>Generated By:</strong> {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                </div>
            </div>
        </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
        Daily Walk-Ins Register - {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
    </div>

    <!-- Statistics Section -->
    <div class="statistics">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value">{{ $stats['total_visits'] }}</div>
                <div class="stat-label">Total Visits</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['active_visits'] }}</div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['completed_visits'] }}</div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['opd_visits'] }}</div>
                <div class="stat-label">OPD</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['emergency_visits'] }}</div>
                <div class="stat-label">Emergency</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['urgent_cases'] }}</div>
                <div class="stat-label">Urgent Cases</div>
            </div>
        </div>
    </div>

    <!-- Visits Table -->
    @if($visits->count() > 0)
        <table class="visits-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 10%;">Time In</th>
                    <th style="width: 12%;">Patient ID</th>
                    <th style="width: 18%;">Patient Name</th>
                    <th style="width: 8%;">Age</th>
                    <th style="width: 8%;">Gender</th>
                    <th style="width: 10%;">Visit Type</th>
                    <th style="width: 15%;">Doctor Assigned</th>
                    <th style="width: 7%;">Priority</th>
                    <th style="width: 7%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($visits as $index => $visit)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($visit->check_in_time)->format('h:i A') }}</td>
                        <td>{{ $visit->patient->patient_number ?? 'N/A' }}</td>
                        <td>{{ $visit->patient->first_name ?? '' }} {{ $visit->patient->last_name ?? '' }}</td>
                        <td>
                            @if($visit->patient->date_of_birth)
                                {{ \Carbon\Carbon::parse($visit->patient->date_of_birth)->age }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $visit->patient->gender ?? 'N/A' }}</td>
                        <td>
                            @if($visit->visit_type === 'OPD')
                                <span class="badge badge-primary">OPD</span>
                            @elseif($visit->visit_type === 'Emergency')
                                <span class="badge badge-danger">Emergency</span>
                            @elseif($visit->visit_type === 'IPD')
                                <span class="badge badge-info">IPD</span>
                            @elseif($visit->visit_type === 'LabOnly')
                                <span class="badge badge-warning">Lab Only</span>
                            @elseif($visit->visit_type === 'PharmacyOnly')
                                <span class="badge badge-success">Pharmacy</span>
                            @else
                                <span class="badge badge-secondary">{{ $visit->visit_type }}</span>
                            @endif
                        </td>
                        <td>
                            @if($visit->assignedDoctor)
                                {{ $visit->assignedDoctor->firstname }} {{ $visit->assignedDoctor->lastname }}
                            @else
                                Not Assigned
                            @endif
                        </td>
                        <td>
                            @if($visit->priority === 'urgent')
                                <span class="badge badge-danger">Urgent</span>
                            @elseif($visit->priority === 'normal')
                                <span class="badge badge-info">Normal</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($visit->priority ?? 'N/A') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($visit->status === 'active')
                                <span class="badge badge-warning">Active</span>
                            @elseif($visit->status === 'completed')
                                <span class="badge badge-success">Completed</span>
                            @elseif($visit->status === 'cancelled')
                                <span class="badge badge-danger">Cancelled</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($visit->status) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Footer -->
        <div style="margin-top: 20px; padding: 10px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
            <div style="font-size: 9pt;">
                <strong>Summary:</strong><br>
                Total Visits: {{ $stats['total_visits'] }} | 
                Active: {{ $stats['active_visits'] }} | 
                Completed: {{ $stats['completed_visits'] }} | 
                OPD: {{ $stats['opd_visits'] }} | 
                Emergency: {{ $stats['emergency_visits'] }} | 
                IPD: {{ $stats['ipd_visits'] }} | 
                Lab Only: {{ $stats['lab_only_visits'] }} | 
                Pharmacy Only: {{ $stats['pharmacy_only_visits'] }}
            </div>
            <div style="margin-top: 8px; font-size: 8pt; color: #666;">
                Waiting in Queue: {{ $stats['waiting_in_queue'] }} | 
                Being Served: {{ $stats['being_served'] }} | 
                Urgent Cases: {{ $stats['urgent_cases'] }}
            </div>
        </div>
    @else
        <div class="no-data">
            No walk-in visits recorded for {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This is a system-generated document. For inquiries, contact {{ $branding['business_phone'] ?? 'Hospital' }}</p>
        <p>Page Generated: {{ now()->format('d M Y, h:i A') }}</p>
    </div>
</body>
</html>

