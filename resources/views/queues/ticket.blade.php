<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Queue Ticket - {{ $queue->ticket_number }}</title>
    <style>
        /* Thermal Printer Optimized (58mm or 80mm width) */
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            padding: 5mm;
            background: white;
        }
        
        .ticket {
            text-align: center;
        }
        
        .hospital-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .branch-name {
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        
        .ticket-number-box {
            border: 2px solid #000;
            padding: 15px 10px;
            margin: 10px 0;
            background: #f0f0f0;
        }
        
        .ticket-label {
            font-size: 12px;
            font-weight: bold;
        }
        
        .ticket-number {
            font-size: 32px;
            font-weight: bold;
            font-family: Arial, sans-serif;
            margin: 5px 0;
            letter-spacing: 2px;
        }
        
        .queue-type {
            font-size: 18px;
            font-weight: bold;
            margin: 8px 0;
            padding: 5px;
            background: #000;
            color: #fff;
        }
        
        .patient-info {
            text-align: left;
            margin: 10px 0;
            font-size: 11px;
        }
        
        .info-row {
            margin: 3px 0;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 80px;
        }
        
        .priority-badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 3px;
        }
        
        .priority-critical {
            background: #dc3545;
            color: white;
        }
        
        .priority-urgent {
            background: #ffc107;
            color: #000;
        }
        
        .priority-routine {
            background: #6c757d;
            color: white;
        }
        
        .instructions {
            font-size: 10px;
            margin: 10px 0;
            text-align: left;
            line-height: 1.4;
        }
        
        .timestamp {
            font-size: 9px;
            margin-top: 10px;
            color: #666;
        }
        
        .footer {
            font-size: 10px;
            margin-top: 10px;
            font-style: italic;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- Hospital Header -->
        @include('components.print-header')
        
        <div class="divider"></div>
        
        <!-- Queue Type -->
        <div class="queue-type">{{ strtoupper($queue->queue_type) }} QUEUE</div>
        
        <!-- Ticket Number -->
        <div class="ticket-number-box">
            <div class="ticket-label">YOUR QUEUE NUMBER</div>
            <div class="ticket-number">{{ $queue->short_ticket }}</div>
            <div style="font-size: 10px; color: #666;">{{ $queue->ticket_number }}</div>
        </div>
        
        <div class="divider"></div>
        
        <!-- Patient Information -->
        <div class="patient-info">
            <div class="info-row">
                <span class="info-label">Patient:</span>
                <span>{{ $queue->patient->first_name }} {{ $queue->patient->last_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Patient ID:</span>
                <span>{{ $queue->patient->patient_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Visit Token:</span>
                <span>{{ $queue->visit->visit_token ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Position:</span>
                <span>#{{ $queue->position }} in line</span>
            </div>
            <div class="info-row">
                <span class="info-label">Priority:</span>
                <span class="priority-badge priority-{{ $queue->priority }}">
                    {{ strtoupper($queue->priority) }}
                </span>
            </div>
            @if($queue->estimated_wait_time)
            <div class="info-row">
                <span class="info-label">Est. Wait:</span>
                <span>~{{ $queue->estimated_wait_time }} minutes</span>
            </div>
            @endif
        </div>
        
        <div class="divider"></div>
        
        <!-- Instructions -->
        <div class="instructions">
            <strong>IMPORTANT INSTRUCTIONS:</strong><br>
            ✓ Keep this ticket with you<br>
            ✓ Listen for your number to be called<br>
            ✓ Wait in the designated waiting area<br>
            ✓ Your number will appear on the display screen<br>
            @if($queue->queue_type === 'Emergency')
            <br><strong>⚠ EMERGENCY PRIORITY</strong><br>
            Critical cases may be seen first.
            @endif
        </div>
        
        <!-- Timestamp -->
        <div class="timestamp">
            Issued: {{ $queue->queued_at->format('d/m/Y h:i A') }}
        </div>
        
        <div class="divider"></div>
        
        <!-- Footer -->
        <div class="footer">
            Thank you for your patience!<br>
            For inquiries, please contact reception.
        </div>
    </div>
    
    <!-- Auto-print script -->
    <script>
        window.onload = function() {
            // Auto-print when page loads
            window.print();
            
            // Close window after printing (optional)
            setTimeout(function() {
                // window.close(); // Uncomment if you want auto-close
            }, 1000);
        };
    </script>
</body>
</html>

