@props(['page' => null, 'title' => null, 'hints' => []])

@php
    // Define page-specific hints
    $pageHints = [
        'patients.index' => [
            'title' => 'Patient Management Guide',
            'hints' => [
                'Use the search bar to quickly find patients by name, number, or phone',
                'Click on a patient row to view their complete profile and medical history',
                'Use the "Add Patient" button to register new patients',
                'Filter by status to see active, inactive, or all patients'
            ]
        ],
        'patients.show' => [
            'title' => 'Patient Profile Guide',
            'hints' => [
                'Navigate between tabs to view different aspects of patient information',
                'Use the "Edit" button to update patient details',
                'Check the "Appointments" tab to see upcoming and past appointments',
                'View medical history and consultations in the respective tabs'
            ]
        ],
        'patients.create' => [
            'title' => 'New Patient Registration',
            'hints' => [
                'Fill in all required fields marked with asterisk (*)',
                'Upload a patient photo for better identification',
                'Add emergency contact information for safety',
                'Verify NHIS number if patient has insurance coverage'
            ]
        ],
        'appointments.index' => [
            'title' => 'Appointment Management',
            'hints' => [
                'Use filters to view appointments by status, doctor, or date range',
                'Click "Schedule Appointment" to book new appointments',
                'Use the calendar view to see appointments by day/week/month',
                'Teleconsultation appointments can be managed from this page'
            ]
        ],
        'appointments.create' => [
            'title' => 'Schedule New Appointment',
            'hints' => [
                'Select patient first, then choose appointment type',
                'For teleconsultations, ensure Jitsi settings are configured',
                'Set appropriate priority level for urgent cases',
                'Add notes to provide context for the appointment'
            ]
        ],
        'consultations.doctor-queue' => [
            'title' => 'Doctor Queue Management',
            'hints' => [
                'Click "Call Next Patient" to announce the next patient',
                'Use "Mark No Show" if patient doesn\'t respond to call',
                'View patient details and vitals before consultation',
                'Audio announcements will play for patient calling'
            ]
        ],
        'consultations.show' => [
            'title' => 'Consultation Management',
            'hints' => [
                'Record patient vitals in the vitals section',
                'Add diagnoses using ICD-10 codes for accuracy',
                'Prescribe medications and order lab tests as needed',
                'Use the notes section for detailed consultation records'
            ]
        ],
        'pharmacy.prescriptions.index' => [
            'title' => 'Prescription Management',
            'hints' => [
                'Filter prescriptions by status (pending, dispensed, completed)',
                'Search by patient name, prescription number, or doctor',
                'Priority filter shows urgent prescriptions first',
                'Click on prescription to view details and start dispensing'
            ]
        ],
        'pharmacy.prescriptions.show' => [
            'title' => 'Prescription Details & Dispensing',
            'hints' => [
                'Check stock availability before dispensing medications',
                'Use "Start Dispensing" to begin the dispensing process',
                'Add dispensing notes for each medication',
                'Print prescription for patient records'
            ]
        ],
        'pharmacy.dispensing' => [
            'title' => 'Dispensing Workflow',
            'hints' => [
                'Process prescriptions in order of priority',
                'Verify patient identity before dispensing',
                'Check drug interactions and allergies',
                'Update stock levels after dispensing'
            ]
        ],
        'pharmacy.stock' => [
            'title' => 'Stock Management',
            'hints' => [
                'Monitor low stock alerts and reorder levels',
                'Check expiry dates to prevent expired drug dispensing',
                'Update stock levels after receiving new inventory',
                'Use filters to find specific drugs or categories'
            ]
        ],
        'pharmacy.analytics' => [
            'title' => 'Pharmacy Analytics',
            'hints' => [
                'View prescription trends and patterns over time',
                'Analyze top prescribed drugs and doctor patterns',
                'Monitor stock movement and financial data',
                'Use date range filters for specific periods'
            ]
        ],
        'lab.requests.index' => [
            'title' => 'Lab Request Management',
            'hints' => [
                'Filter requests by status, priority, or date',
                'Click on request to view details and process',
                'Use priority levels for urgent tests',
                'Assign technicians to specific requests'
            ]
        ],
        'lab.results.index' => [
            'title' => 'Lab Results Management',
            'hints' => [
                'Enter test results with appropriate units',
                'Flag abnormal results for doctor attention',
                'Generate PDF reports for patient records',
                'Verify results before finalizing'
            ]
        ],
        'billing.invoices.index' => [
            'title' => 'Billing Management',
            'hints' => [
                'Filter invoices by status, patient, or date range',
                'Generate new invoices for services rendered',
                'Process payments and update invoice status',
                'View payment history and outstanding balances'
            ]
        ],
        'billing.invoices.create' => [
            'title' => 'Create New Invoice',
            'hints' => [
                'Select patient and add invoice items',
                'Set appropriate tax rates and discounts',
                'Add payment terms and due dates',
                'Save as draft or finalize invoice'
            ]
        ],
        'teleconsultations.index' => [
            'title' => 'Teleconsultation Management',
            'hints' => [
                'Join teleconsultation sessions using the join button',
                'Monitor session status and participant count',
                'Access session recordings if available',
                'Manage Jitsi settings for video quality'
            ]
        ],
        'settings.index' => [
            'title' => 'System Settings',
            'hints' => [
                'Configure system-wide settings and preferences',
                'Manage user roles and permissions',
                'Set up integrations and API keys',
                'Backup and restore system data'
            ]
        ],
        'users.index' => [
            'title' => 'User Management',
            'hints' => [
                'Create new user accounts with appropriate roles',
                'Assign permissions based on job functions',
                'Monitor user activity and login history',
                'Deactivate accounts for former employees'
            ]
        ],
        'branches.index' => [
            'title' => 'Branch Management',
            'hints' => [
                'Add new branches or clinic locations',
                'Configure branch-specific settings',
                'Assign users to specific branches',
                'Monitor branch performance and activity'
            ]
        ],
        'lab.archive.index' => [
            'title' => 'Lab Archive Management',
            'hints' => [
                'Search archived lab results by patient or date range',
                'Compare results across different time periods',
                'Export archived data for reporting purposes',
                'Access historical lab trends and patterns'
            ]
        ],
        'lab.archive.patient-history' => [
            'title' => 'Patient Lab History',
            'hints' => [
                'View complete lab history for selected patient',
                'Compare current results with previous tests',
                'Track improvement or deterioration trends',
                'Generate comprehensive lab reports'
            ]
        ],
        'lab.archive.compare-results' => [
            'title' => 'Lab Results Comparison',
            'hints' => [
                'Select multiple test results to compare',
                'View side-by-side comparison of values',
                'Identify trends and changes over time',
                'Export comparison reports for doctors'
            ]
        ],
        'pharmacy.history' => [
            'title' => 'Dispensing History',
            'hints' => [
                'Filter dispensing history by date range or patient',
                'View detailed dispensing records and notes',
                'Track medication usage patterns',
                'Generate dispensing reports for analysis'
            ]
        ],
        'billing.payments.index' => [
            'title' => 'Payment Management',
            'hints' => [
                'Process payments for outstanding invoices',
                'Record different payment methods (cash, card, mobile money)',
                'Generate payment receipts and confirmations',
                'Track payment history and outstanding balances'
            ]
        ],
        'billing.reports.index' => [
            'title' => 'Financial Reports',
            'hints' => [
                'Generate revenue reports by date range',
                'Analyze payment trends and collection rates',
                'Export financial data for accounting',
                'Monitor outstanding receivables and cash flow'
            ]
        ],
        'reports.index' => [
            'title' => 'System Reports',
            'hints' => [
                'Generate comprehensive system reports',
                'Export data in various formats (PDF, Excel)',
                'Schedule automated report generation',
                'Access audit trails and activity logs'
            ]
        ],
        'audit.index' => [
            'title' => 'Audit Trail Management',
            'hints' => [
                'Monitor all system activities and changes',
                'Track user actions and data modifications',
                'Generate compliance reports for regulations',
                'Investigate security incidents and breaches'
            ]
        ],
        'notifications.index' => [
            'title' => 'Notification Center',
            'hints' => [
                'View all system notifications and alerts',
                'Mark notifications as read or unread',
                'Configure notification preferences',
                'Manage alert settings for different events'
            ]
        ],
        'profile.show' => [
            'title' => 'User Profile Management',
            'hints' => [
                'Update your personal information and contact details',
                'Change password and security settings',
                'Upload profile photo for identification',
                'Manage notification preferences'
            ]
        ],
        'profile.edit' => [
            'title' => 'Edit Profile',
            'hints' => [
                'Keep your contact information up to date',
                'Use a strong password with mixed characters',
                'Upload a clear, professional profile photo',
                'Save changes before navigating away'
            ]
        ],
        'insurance.index' => [
            'title' => 'Insurance Dashboard',
            'hints' => [
                'View comprehensive insurance statistics and recent activity',
                'Access all insurance modules from the quick action buttons',
                'Monitor active policies and pending claims at a glance',
                'Use the statistics cards to track key performance indicators'
            ]
        ],
        'insurance.providers' => [
            'title' => 'Insurance Provider Management',
            'hints' => [
                'Add new insurance providers with complete contact information',
                'Configure provider-specific settings like coverage percentages',
                'Enable electronic claims and real-time verification features',
                'Use the search and filter options to quickly find providers',
                'Monitor provider performance metrics and approval rates'
            ]
        ],
        'insurance.policies' => [
            'title' => 'Insurance Policy Management',
            'hints' => [
                'Assign insurance policies to patients for coverage tracking',
                'Set coverage percentages and co-pay amounts per policy',
                'Configure annual and lifetime coverage limits',
                'Track policy expiration dates and renewal requirements',
                'Use the policy status to manage active and inactive policies'
            ]
        ],
        'insurance.claims' => [
            'title' => 'Insurance Claims Processing',
            'hints' => [
                'Submit new claims with detailed service items and amounts',
                'Use filters to view claims by status, provider, or date range',
                'Approve or reject claims based on coverage verification',
                'Export claim reports for insurance provider submission',
                'Track claim processing time and approval rates'
            ]
        ],
        'insurance.pre-authorizations' => [
            'title' => 'Pre-Authorization Management',
            'hints' => [
                'Submit pre-authorization requests for high-value services',
                'Set appropriate urgency levels (routine, urgent, emergency)',
                'Provide detailed clinical justification for service requests',
                'Approve or reject requests with approval amounts and expiry dates',
                'Track pre-authorization status and expiration dates'
            ]
        ],
        'insurance.analytics' => [
            'title' => 'Insurance Analytics & Reporting',
            'hints' => [
                'View comprehensive insurance performance metrics and trends',
                'Analyze provider performance and approval rates',
                'Monitor financial summaries and coverage statistics',
                'Export detailed reports in PDF format for management',
                'Use date range filters to analyze specific time periods'
            ]
        ],
        'queues.index' => [
            'title' => 'Queue Management Dashboard',
            'hints' => [
                'Monitor patient queues across all departments in real-time',
                'View queue statistics and waiting times',
                'Manage queue priorities and patient flow',
                'Access department-specific queue management tools',
                'Track queue performance and efficiency metrics'
            ]
        ],
        'visits.index' => [
            'title' => 'Patient Visits Management',
            'hints' => [
                'Check-in patients for OPD or IPD visits',
                'View current and past patient visits',
                'Manage visit types and departments',
                'Track visit status and completion',
                'Generate visit reports and analytics'
            ]
        ],
        'walk-ins.index' => [
            'title' => 'Daily Walk-ins Register',
            'hints' => [
                'Register walk-in patients without appointments',
                'Track daily walk-in statistics and patterns',
                'Manage walk-in queue and priority',
                'Export walk-in reports for analysis',
                'Monitor walk-in to appointment conversion rates'
            ]
        ],
        'radiology.index' => [
            'title' => 'Radiology Requests Management',
            'hints' => [
                'View all imaging requests sorted by priority (STAT, Urgent, Routine)',
                'Filter by modality (X-ray, CT, MRI, Ultrasound, etc.) to focus on specific studies',
                'Click "New Request" to order imaging studies for patients',
                'Use search to find requests by patient name or request number',
                'Click "View" to see study details and access DICOM viewer',
                'Track request status from Requested → Scheduled → In Progress → Completed'
            ]
        ],
        'radiology.create' => [
            'title' => 'Create Radiology Request',
            'hints' => [
                'Select patient first, then choose the imaging modality needed',
                'Set priority: STAT (immediate), Urgent (< 2 hours), or Routine',
                'Provide clinical history and specific clinical question',
                'Add relevant indication (reason for imaging study)',
                'Special instructions help technicians prepare the patient',
                'System automatically generates unique request number'
            ]
        ],
        'radiology.show' => [
            'title' => 'Radiology Study Details',
            'hints' => [
                'Review complete study information and patient demographics',
                'Click "View Images" to open DICOM viewer for image review',
                'Click "Create Report" to start radiologist reporting',
                'View associated billing information and status',
                'Download PDF report once radiologist has signed off',
                'Check radiation dose and contrast administration details'
            ]
        ],
        'radiology.viewer' => [
            'title' => 'DICOM Image Viewer Guide',
            'hints' => [
                'Click series thumbnails on the left to switch between image sets',
                'Use Window tool (active by default) with presets: Lung, Bone, Brain, Abdomen',
                'Pan tool lets you move the image around the screen',
                'Zoom tool for magnifying regions of interest',
                'Measure tool for length measurements, Angle tool for angular measurements',
                'Use Play button for cine mode (animate through images)',
                'Fullscreen mode provides distraction-free viewing',
                'All tools are one-click - no complex menus!',
                'Reset button returns image to original view',
                'Click "Create Report" when ready to document findings'
            ]
        ],
        'radiology.studies' => [
            'title' => 'Radiology Studies & Images',
            'hints' => [
                'View all imaging studies with acquisition status',
                'Click study to view images in DICOM viewer',
                'Upload images from imaging equipment or PACS',
                'Studies awaiting reports are highlighted in yellow',
                'Completed studies with reports show in green',
                'Filter by modality, date range, or status'
            ]
        ],
        'radiology.reports' => [
            'title' => 'Radiology Reports Management',
            'hints' => [
                'View all radiology reports sorted by date',
                'Draft reports (yellow) need to be completed and signed',
                'Preliminary reports (blue) are interim findings',
                'Final reports (green) are signed and complete',
                'Click report to view full details or make amendments',
                'Generate PDF reports for referring physicians',
                'Use search to find reports by patient or study type'
            ]
        ],
        'radiology.reports.create' => [
            'title' => 'Create Radiology Report',
            'hints' => [
                'Review images in DICOM viewer before starting report',
                'Use structured sections: Technique, Findings, Impression, Recommendations',
                'Findings section is for detailed observations (use CKEditor for formatting)',
                'Impression section is for concise diagnostic conclusions',
                'Add recommendations for follow-up or additional imaging if needed',
                'Save as Draft to continue later, or Sign to finalize',
                'Signed reports become final and are sent to referring physician',
                'Critical findings should be communicated immediately to doctor'
            ]
        ],
        'radiology.reports.edit' => [
            'title' => 'Edit Radiology Report',
            'hints' => [
                'Review existing findings and make necessary changes',
                'Add amendment reason if editing a signed report',
                'Use CKEditor tools for text formatting and structure',
                'Compare with prior studies if available',
                'Update impression if findings have changed',
                'Save as Draft or Sign when complete',
                'Amended reports track version history for audit'
            ]
        ]
    ];

    // Get hints for current page
    $currentHints = $pageHints[$page] ?? null;
    
    // If no hints defined for page, don't show component
    if (!$currentHints) {
        return;
    }
@endphp

@if($currentHints && $page !== 'dashboard')
<div class="hint-guide-section mb-4">
    <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="card-header border-0" style="background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);">
            <div class="d-flex align-items-center">
                <div class="hint-icon me-3">
                    <i class="bi bi-lightbulb-fill"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-0 text-white fw-semibold">{{ $currentHints['title'] }}</h5>
                    <small class="text-white-50">Quick guide to help you navigate this page</small>
                </div>
                <button type="button" class="btn btn-light btn-sm rounded-pill px-3" 
                        data-bs-toggle="collapse" data-bs-target="#hintContent{{ str_replace('.', '', $page) }}" 
                        aria-expanded="false" aria-controls="hintContent{{ str_replace('.', '', $page) }}">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
        </div>
        <div class="collapse" id="hintContent{{ str_replace('.', '', $page) }}">
            <div class="card-body p-4">
                <div class="row g-3">
                    @foreach($currentHints['hints'] as $index => $hint)
                    <div class="col-md-6 col-lg-4">
                        <div class="hint-item d-flex align-items-start p-3 rounded-3" 
                             style="background: rgba(30, 58, 95, 0.05); border-left: 4px solid #1e3a5f;">
                            <div class="hint-number me-3">
                                <span class="badge rounded-pill" style="background: #1e3a5f; color: white; font-size: 0.8rem; min-width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">{{ $index + 1 }}</span>
                            </div>
                            <div class="hint-text">
                                <p class="mb-0 text-dark fw-medium" style="font-size: 0.95rem; line-height: 1.5;">{{ $hint }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-3" style="border-top: 1px solid rgba(30, 58, 95, 0.1);">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2" style="color: #1e3a5f;"></i>
                        <span class="text-muted fw-medium" style="font-size: 0.9rem;">
                            <strong style="color: #1e3a5f;">Pro Tip:</strong> Click the arrow above to hide/show these hints. These guides help you navigate and use each page effectively.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hint-guide-section .card {
    box-shadow: 0 4px 12px rgba(30, 58, 95, 0.15);
    border-radius: 12px;
    overflow: hidden;
}

.hint-guide-section .card-header {
    padding: 1.25rem 1.5rem;
    border-radius: 0;
}

.hint-guide-section .hint-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
}

.hint-guide-section .hint-item {
    transition: all 0.3s ease;
    border: 1px solid rgba(30, 58, 95, 0.1);
}

.hint-guide-section .hint-item:hover {
    background: rgba(30, 58, 95, 0.08) !important;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(30, 58, 95, 0.1);
}

.hint-guide-section .hint-number .badge {
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(30, 58, 95, 0.3);
}

.hint-guide-section .hint-text p {
    color: #2d3748;
    font-weight: 500;
}

.hint-guide-section .collapse {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.hint-guide-section .btn-light {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #1e3a5f;
    font-weight: 600;
    transition: all 0.3s ease;
}

.hint-guide-section .btn-light:hover {
    background: white;
    color: #1e3a5f;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.hint-guide-section .collapse.show .btn-light i {
    transform: rotate(180deg);
}

.hint-guide-section .btn-light i {
    transition: transform 0.3s ease;
}

.hint-guide-section .card-body {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

/* Improved typography for better readability */
.hint-guide-section h5 {
    font-size: 1.1rem;
    letter-spacing: -0.025em;
}

.hint-guide-section .hint-text p {
    font-size: 0.95rem;
    line-height: 1.6;
    letter-spacing: -0.01em;
}

/* Better spacing and visual hierarchy */
.hint-guide-section .row.g-3 > * {
    padding: 0.75rem;
}

/* Subtle animation for the entire component */
.hint-guide-section {
    animation: slideInDown 0.6s ease-out;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hint-guide-section .card-header {
        padding: 1rem;
    }
    
    .hint-guide-section .hint-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
    
    .hint-guide-section h5 {
        font-size: 1rem;
    }
    
    .hint-guide-section .hint-text p {
        font-size: 0.9rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide hints after 30 seconds for new users (only if they've expanded hints)
    const hintSections = document.querySelectorAll('.hint-guide-section');
    const isNewUser = !localStorage.getItem('hint_guide_seen');
    
    if (isNewUser && hintSections.length > 0) {
        setTimeout(function() {
            hintSections.forEach(function(section) {
                const collapseElement = section.querySelector('.collapse');
                const pageId = collapseElement.id;
                const savedState = localStorage.getItem('hint_state_' + pageId);
                
                // Only auto-hide if the hint was manually expanded by the user
                if (collapseElement && collapseElement.classList.contains('show') && savedState === 'shown') {
                    const toggleButton = section.querySelector('[data-bs-toggle="collapse"]');
                    if (toggleButton) {
                        toggleButton.click();
                    }
                }
            });
            localStorage.setItem('hint_guide_seen', 'true');
        }, 30000); // 30 seconds
    }
    
    // Remember user's preference for showing/hiding hints
    hintSections.forEach(function(section) {
        const collapseElement = section.querySelector('.collapse');
        const toggleButton = section.querySelector('[data-bs-toggle="collapse"]');
        const pageId = section.querySelector('.collapse').id;
        
        if (toggleButton && collapseElement) {
            // Check if user has a saved preference for this page
            const savedState = localStorage.getItem('hint_state_' + pageId);
            if (savedState === 'shown') {
                collapseElement.classList.add('show');
                toggleButton.setAttribute('aria-expanded', 'true');
            } else {
                // Default to collapsed state
                collapseElement.classList.remove('show');
                toggleButton.setAttribute('aria-expanded', 'false');
            }
            
            // Save state when toggled
            collapseElement.addEventListener('shown.bs.collapse', function() {
                localStorage.setItem('hint_state_' + pageId, 'shown');
                // Update button icon
                const icon = toggleButton.querySelector('i');
                if (icon) {
                    icon.style.transform = 'rotate(180deg)';
                }
            });
            
            collapseElement.addEventListener('hidden.bs.collapse', function() {
                localStorage.setItem('hint_state_' + pageId, 'hidden');
                // Update button icon
                const icon = toggleButton.querySelector('i');
                if (icon) {
                    icon.style.transform = 'rotate(0deg)';
                }
            });
        }
    });
});
</script>
@endif
