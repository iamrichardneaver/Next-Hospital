<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - {{ $prescription->prescription_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        /* Header styles are now in the print-header component */
        
        .prescription-info-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .prescription-info-header h1 {
            color: #1e3a5f;
            margin: 0;
            font-size: 28px;
        }
        .prescription-info-header p {
            margin: 5px 0;
            color: #666;
        }
        .prescription-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-section {
            flex: 1;
            margin: 0 10px;
        }
        .info-section h3 {
            color: #1e3a5f;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #333;
        }
        .medications-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .medications-table th,
        .medications-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .medications-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #1e3a5f;
        }
        .medications-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .prescription-footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .doctor-signature {
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin: 20px auto 5px;
        }
        .prescription-notes {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #1e3a5f;
            margin: 20px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-dispensed { background-color: #17a2b8; color: #fff; }
        .status-completed { background-color: #28a745; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    @include('components.print-header')
    
    <!-- Prescription Information -->
    <div class="prescription-info-header">
        <h1>PRESCRIPTION</h1>
        <p><strong>Prescription Number:</strong> {{ $prescription->prescription_number }}</p>
        <p><strong>Date:</strong> {{ $prescription->prescription_date->format('F d, Y') }}</p>
        <p><strong>Status:</strong> 
            <span class="status-badge status-{{ $prescription->status }}">
                {{ ucfirst($prescription->status) }}
            </span>
        </p>
    </div>

    <div class="prescription-info">
        <div class="info-section">
            <h3>Patient Information</h3>
            @if($prescription->patient)
            <div class="info-item">
                <span class="info-label">Name:</span> 
                {{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}
            </div>
            <div class="info-item">
                <span class="info-label">Patient Number:</span> 
                {{ $prescription->patient->patient_number }}
            </div>
            <div class="info-item">
                <span class="info-label">Phone:</span> 
                {{ $prescription->patient->phone ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Date of Birth:</span> 
                {{ $prescription->patient->date_of_birth ? $prescription->patient->date_of_birth->format('M d, Y') : 'N/A' }}
            </div>
            @else
            <div class="info-item">
                <span class="info-label">Name:</span> 
                Unknown Patient
            </div>
            <div class="info-item">
                <span class="info-label">Patient Number:</span> 
                N/A
            </div>
            <div class="info-item">
                <span class="info-label">Phone:</span> 
                N/A
            </div>
            <div class="info-item">
                <span class="info-label">Date of Birth:</span> 
                N/A
            </div>
            @endif
        </div>

        <div class="info-section">
            <h3>Doctor Information</h3>
            @if($prescription->doctor)
            <div class="info-item">
                <span class="info-label">Name:</span> 
                Dr. {{ $prescription->doctor->first_name }} {{ $prescription->doctor->last_name }}
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span> 
                {{ $prescription->doctor->email }}
            </div>
            @else
            <div class="info-item">
                <span class="info-label">Name:</span> 
                N/A
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span> 
                N/A
            </div>
            @endif
            @if($prescription->branch)
            <div class="info-item">
                <span class="info-label">Branch:</span> 
                {{ $prescription->branch->name }}
            </div>
            @endif
        </div>
    </div>

    @if($prescription->notes)
    <div class="prescription-notes">
        <h4>Prescription Notes:</h4>
        <p>{{ $prescription->notes }}</p>
    </div>
    @endif

    @if($prescription->orders->count() > 0)
    <h3>Medications Prescribed</h3>
    <table class="medications-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Drug Name</th>
                <th>Dosage Instructions</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>Quantity</th>
                <th>Dispensed</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($prescription->orders as $index => $order)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $order->drug->name }}</strong>
                    @if($order->drug->generic_name)
                        <br><small>({{ $order->drug->generic_name }})</small>
                    @endif
                    <br><small>{{ $order->drug->strength }} {{ $order->drug->unit }}</small>
                </td>
                <td>{{ $order->dosage_instructions ?? 'N/A' }}</td>
                <td>{{ $order->frequency ?? 'N/A' }}</td>
                <td>{{ $order->duration ?? 'N/A' }}</td>
                <td>{{ $order->quantity }}</td>
                <td>{{ $order->quantity_dispensed }}</td>
                <td>
                    <span class="status-badge status-{{ $order->status }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="alert alert-info">
        <h4>No medications prescribed</h4>
        <p>This prescription does not contain any medications.</p>
    </div>
    @endif

    <div class="prescription-footer">
        <div>
            <p><strong>Printed on:</strong> {{ now()->format('F d, Y \a\t H:i') }}</p>
            <p><strong>Printed by:</strong> {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
        </div>
        
        <div class="doctor-signature">
            <div class="signature-line"></div>
            <p><strong>@if($prescription->doctor)Dr. {{ $prescription->doctor->first_name }} {{ $prescription->doctor->last_name }}@else N/A @endif</strong></p>
            <p>Prescribing Doctor</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button onclick="window.print()" class="btn btn-primary">Print Prescription</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>
