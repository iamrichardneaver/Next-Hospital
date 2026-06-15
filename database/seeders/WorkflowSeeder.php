<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowTransition;

class WorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createOPDWorkflow();
        $this->createIPDWorkflow();
        $this->createLabWorkflow();
        $this->createPharmacyWorkflow();
        $this->createBillingWorkflow();
    }

    /**
     * Create OPD Visit Workflow.
     */
    protected function createOPDWorkflow(): void
    {
        $workflow = Workflow::create([
            'name' => 'OPD Visit',
            'description' => 'Complete workflow for outpatient department visits',
            'module' => 'opd',
            'is_active' => true,
        ]);

        // Define steps
        $steps = [
            [
                'step_key' => 'patient_registration',
                'step_name' => 'Patient Registration',
                'step_description' => 'Register or search for patient',
                'route_name' => 'visits.create',
                'required_permission' => 'create_visits',
                'order' => 1,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'queue_assignment',
                'step_name' => 'Queue Assignment',
                'step_description' => 'Patient added to OPD queue',
                'route_name' => 'queues.index',
                'required_permission' => 'create_visits',
                'order' => 2,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => true,
            ],
            [
                'step_key' => 'vitals_recording',
                'step_name' => 'Record Vital Signs',
                'step_description' => 'Nurse records patient vital signs',
                'route_name' => 'vitals.create',
                'required_permission' => 'record_vitals',
                'order' => 3,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => true,
            ],
            [
                'step_key' => 'consultation',
                'step_name' => 'Doctor Consultation',
                'step_description' => 'Doctor examines and diagnoses patient',
                'route_name' => 'consultations.doctor-queue',
                'required_permission' => 'create_consultations',
                'order' => 4,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'laboratory_testing',
                'step_name' => 'Laboratory Testing',
                'step_description' => 'Patient goes to lab for tests',
                'route_name' => 'lab-requests.index',
                'required_permission' => 'process_lab_requests',
                'order' => 5,
                'is_required' => false,
                'can_skip' => true,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'radiology_imaging',
                'step_name' => 'Radiology & Imaging',
                'step_description' => 'Patient goes to radiology for imaging studies',
                'route_name' => 'radiology.index',
                'required_permission' => 'view_radiology_requests',
                'order' => 5,
                'is_required' => false,
                'can_skip' => true,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'pharmacy_dispensing',
                'step_name' => 'Pharmacy Dispensing',
                'step_description' => 'Patient collects prescribed medications',
                'route_name' => 'pharmacy.prescriptions.index',
                'required_permission' => 'dispense_drugs',
                'order' => 6,
                'is_required' => false,
                'can_skip' => true,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'billing',
                'step_name' => 'Billing & Payment',
                'step_description' => 'Process payment for services',
                'route_name' => 'billing.create',
                'required_permission' => 'manage_billing',
                'order' => 7,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'visit_closure',
                'step_name' => 'Visit Closure',
                'step_description' => 'Close the visit',
                'route_name' => 'visits.index',
                'required_permission' => 'create_visits',
                'order' => 8,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
        ];

        $createdSteps = [];
        foreach ($steps as $stepData) {
            $stepData['workflow_id'] = $workflow->id;
            $stepData['route_parameters'] = ['id' => 'entity_id'];
            $createdSteps[$stepData['step_key']] = WorkflowStep::create($stepData);
        }

        // Define transitions
        $transitions = [
            [
                'from_step_key' => 'patient_registration',
                'to_step_key' => 'queue_assignment',
                'condition_type' => 'always',
                'priority' => 1,
            ],
            [
                'from_step_key' => 'queue_assignment',
                'to_step_key' => 'vitals_recording',
                'condition_type' => 'always',
                'priority' => 1,
            ],
            [
                'from_step_key' => 'vitals_recording',
                'to_step_key' => 'consultation',
                'condition_type' => 'always',
                'priority' => 1,
            ],
            [
                'from_step_key' => 'consultation',
                'to_step_key' => 'radiology_imaging',
                'condition_type' => 'conditional',
                'condition_logic' => [
                    ['field' => 'imaging_ordered', 'operator' => 'exists'],
                ],
                'priority' => 3,
            ],
            [
                'from_step_key' => 'consultation',
                'to_step_key' => 'laboratory_testing',
                'condition_type' => 'conditional',
                'condition_logic' => [
                    ['field' => 'lab_ordered', 'operator' => 'exists'],
                ],
                'priority' => 2,
            ],
            [
                'from_step_key' => 'consultation',
                'to_step_key' => 'pharmacy_dispensing',
                'condition_type' => 'conditional',
                'condition_logic' => [
                    ['field' => 'prescription_ordered', 'operator' => 'exists'],
                ],
                'priority' => 1,
            ],
            [
                'from_step_key' => 'radiology_imaging',
                'to_step_key' => 'laboratory_testing',
                'condition_type' => 'conditional',
                'condition_logic' => [
                    ['field' => 'lab_ordered', 'operator' => 'exists'],
                ],
                'priority' => 1,
            ],
            [
                'from_step_key' => 'radiology_imaging',
                'to_step_key' => 'pharmacy_dispensing',
                'condition_type' => 'conditional',
                'condition_logic' => [
                    ['field' => 'prescription_ordered', 'operator' => 'exists'],
                ],
                'priority' => 2,
            ],
            [
                'from_step_key' => 'radiology_imaging',
                'to_step_key' => 'billing',
                'condition_type' => 'conditional',
                'condition_logic' => [
                    ['field' => 'prescription_ordered', 'operator' => 'not_exists'],
                    ['field' => 'lab_ordered', 'operator' => 'not_exists'],
                ],
                'priority' => 3,
            ],
            [
                'from_step_key' => 'laboratory_testing',
                'to_step_key' => 'pharmacy_dispensing',
                'condition_type' => 'conditional',
                'condition_logic' => [
                    ['field' => 'prescription_ordered', 'operator' => 'exists'],
                ],
                'priority' => 1,
            ],
            [
                'from_step_key' => 'laboratory_testing',
                'to_step_key' => 'billing',
                'condition_type' => 'conditional',
                'condition_logic' => [
                    ['field' => 'prescription_ordered', 'operator' => 'not_exists'],
                ],
                'priority' => 2,
            ],
            [
                'from_step_key' => 'pharmacy_dispensing',
                'to_step_key' => 'billing',
                'condition_type' => 'always',
                'priority' => 1,
            ],
            [
                'from_step_key' => 'billing',
                'to_step_key' => 'visit_closure',
                'condition_type' => 'always',
                'priority' => 1,
            ],
        ];

        foreach ($transitions as $transitionData) {
            WorkflowTransition::create([
                'workflow_id' => $workflow->id,
                'from_step_id' => $createdSteps[$transitionData['from_step_key']]->id,
                'to_step_id' => $createdSteps[$transitionData['to_step_key']]->id,
                'condition_type' => $transitionData['condition_type'],
                'condition_logic' => $transitionData['condition_logic'] ?? null,
                'priority' => $transitionData['priority'],
            ]);
        }
    }

    /**
     * Create IPD Admission Workflow.
     */
    protected function createIPDWorkflow(): void
    {
        $workflow = Workflow::create([
            'name' => 'IPD Admission',
            'description' => 'Complete workflow for inpatient department admissions',
            'module' => 'ipd',
            'is_active' => true,
        ]);

        $steps = [
            [
                'step_key' => 'patient_registration',
                'step_name' => 'Patient Registration',
                'step_description' => 'Register or search for patient',
                'route_name' => 'visits.create',
                'required_permission' => 'create_visits',
                'order' => 1,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'admission',
                'step_name' => 'Admission',
                'step_description' => 'Admit patient to IPD',
                'route_name' => 'visits.show',
                'required_permission' => 'create_visits',
                'order' => 2,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => true,
            ],
            [
                'step_key' => 'bed_assignment',
                'step_name' => 'Bed Assignment',
                'step_description' => 'Assign bed to patient',
                'route_name' => 'wards.beds.index',
                'required_permission' => 'manage_wards',
                'order' => 3,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => true,
            ],
            [
                'step_key' => 'vitals_recording',
                'step_name' => 'Record Vital Signs',
                'step_description' => 'Nurse records patient vital signs',
                'route_name' => 'vitals.create',
                'required_permission' => 'record_vitals',
                'order' => 4,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => true,
            ],
            [
                'step_key' => 'consultation',
                'step_name' => 'Doctor Consultation',
                'step_description' => 'Doctor examines and diagnoses patient',
                'route_name' => 'consultations.create',
                'required_permission' => 'create_consultations',
                'order' => 5,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'treatment',
                'step_name' => 'Treatment & Monitoring',
                'step_description' => 'Ongoing treatment and monitoring',
                'route_name' => 'visits.show',
                'required_permission' => 'view_visits',
                'order' => 6,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'discharge',
                'step_name' => 'Discharge',
                'step_description' => 'Discharge patient from IPD',
                'route_name' => 'visits.index',
                'required_permission' => 'create_visits',
                'order' => 7,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
        ];

        $createdSteps = [];
        foreach ($steps as $stepData) {
            $stepData['workflow_id'] = $workflow->id;
            $stepData['route_parameters'] = ['id' => 'entity_id'];
            $createdSteps[$stepData['step_key']] = WorkflowStep::create($stepData);
        }

        // Simple linear transitions for IPD
        $transitions = [
            ['from_step_key' => 'patient_registration', 'to_step_key' => 'admission'],
            ['from_step_key' => 'admission', 'to_step_key' => 'bed_assignment'],
            ['from_step_key' => 'bed_assignment', 'to_step_key' => 'vitals_recording'],
            ['from_step_key' => 'vitals_recording', 'to_step_key' => 'consultation'],
            ['from_step_key' => 'consultation', 'to_step_key' => 'treatment'],
            ['from_step_key' => 'treatment', 'to_step_key' => 'discharge'],
        ];

        foreach ($transitions as $transitionData) {
            WorkflowTransition::create([
                'workflow_id' => $workflow->id,
                'from_step_id' => $createdSteps[$transitionData['from_step_key']]->id,
                'to_step_id' => $createdSteps[$transitionData['to_step_key']]->id,
                'condition_type' => 'always',
                'priority' => 1,
            ]);
        }
    }

    /**
     * Create Lab Test Workflow.
     */
    protected function createLabWorkflow(): void
    {
        $workflow = Workflow::create([
            'name' => 'Lab Test',
            'description' => 'Workflow for laboratory testing',
            'module' => 'lab',
            'is_active' => true,
        ]);

        $steps = [
            [
                'step_key' => 'lab_request',
                'step_name' => 'Lab Request',
                'step_description' => 'Doctor orders lab test',
                'route_name' => 'lab-requests.create',
                'required_permission' => 'create_lab_requests',
                'order' => 1,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => true,
            ],
            [
                'step_key' => 'sample_collection',
                'step_name' => 'Sample Collection',
                'step_description' => 'Collect sample from patient',
                'route_name' => 'lab-requests.index',
                'required_permission' => 'process_lab_requests',
                'order' => 2,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => true,
            ],
            [
                'step_key' => 'testing',
                'step_name' => 'Testing',
                'step_description' => 'Perform laboratory test',
                'route_name' => 'lab-requests.index',
                'required_permission' => 'process_lab_requests',
                'order' => 3,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => true,
            ],
            [
                'step_key' => 'results_entry',
                'step_name' => 'Results Entry',
                'step_description' => 'Enter test results',
                'route_name' => 'lab-results.create',
                'required_permission' => 'manage_lab_results',
                'order' => 4,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'results_review',
                'step_name' => 'Results Review',
                'step_description' => 'Doctor reviews results',
                'route_name' => 'lab-results.index',
                'required_permission' => 'view_lab_results',
                'order' => 5,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
        ];

        $createdSteps = [];
        foreach ($steps as $stepData) {
            $stepData['workflow_id'] = $workflow->id;
            $stepData['route_parameters'] = ['id' => 'entity_id'];
            $createdSteps[$stepData['step_key']] = WorkflowStep::create($stepData);
        }

        $transitions = [
            ['from_step_key' => 'lab_request', 'to_step_key' => 'sample_collection'],
            ['from_step_key' => 'sample_collection', 'to_step_key' => 'testing'],
            ['from_step_key' => 'testing', 'to_step_key' => 'results_entry'],
            ['from_step_key' => 'results_entry', 'to_step_key' => 'results_review'],
        ];

        foreach ($transitions as $transitionData) {
            WorkflowTransition::create([
                'workflow_id' => $workflow->id,
                'from_step_id' => $createdSteps[$transitionData['from_step_key']]->id,
                'to_step_id' => $createdSteps[$transitionData['to_step_key']]->id,
                'condition_type' => 'always',
                'priority' => 1,
            ]);
        }
    }

    /**
     * Create Pharmacy Dispensing Workflow.
     */
    protected function createPharmacyWorkflow(): void
    {
        $workflow = Workflow::create([
            'name' => 'Pharmacy Dispensing',
            'description' => 'Workflow for pharmacy dispensing',
            'module' => 'pharmacy',
            'is_active' => true,
        ]);

        $steps = [
            [
                'step_key' => 'prescription',
                'step_name' => 'Prescription',
                'step_description' => 'Doctor creates prescription',
                'route_name' => 'prescriptions.index',
                'required_permission' => 'create_prescriptions',
                'order' => 1,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => true,
            ],
            [
                'step_key' => 'dispensing',
                'step_name' => 'Dispensing',
                'step_description' => 'Pharmacist dispenses medications',
                'route_name' => 'pharmacy.prescriptions.index',
                'required_permission' => 'dispense_drugs',
                'order' => 2,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'billing',
                'step_name' => 'Billing',
                'step_description' => 'Process payment',
                'route_name' => 'billing.create',
                'required_permission' => 'manage_billing',
                'order' => 3,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'collection',
                'step_name' => 'Collection',
                'step_description' => 'Patient collects medications',
                'route_name' => 'pharmacy.prescriptions.index',
                'required_permission' => 'view_prescriptions',
                'order' => 4,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
        ];

        $createdSteps = [];
        foreach ($steps as $stepData) {
            $stepData['workflow_id'] = $workflow->id;
            $stepData['route_parameters'] = ['id' => 'entity_id'];
            $createdSteps[$stepData['step_key']] = WorkflowStep::create($stepData);
        }

        $transitions = [
            ['from_step_key' => 'prescription', 'to_step_key' => 'dispensing'],
            ['from_step_key' => 'dispensing', 'to_step_key' => 'billing'],
            ['from_step_key' => 'billing', 'to_step_key' => 'collection'],
        ];

        foreach ($transitions as $transitionData) {
            WorkflowTransition::create([
                'workflow_id' => $workflow->id,
                'from_step_id' => $createdSteps[$transitionData['from_step_key']]->id,
                'to_step_id' => $createdSteps[$transitionData['to_step_key']]->id,
                'condition_type' => 'always',
                'priority' => 1,
            ]);
        }
    }

    /**
     * Create Billing Workflow.
     */
    protected function createBillingWorkflow(): void
    {
        $workflow = Workflow::create([
            'name' => 'Billing',
            'description' => 'Workflow for billing and payment',
            'module' => 'billing',
            'is_active' => true,
        ]);

        $steps = [
            [
                'step_key' => 'invoice_creation',
                'step_name' => 'Invoice Creation',
                'step_description' => 'Create invoice for services',
                'route_name' => 'billing.create',
                'required_permission' => 'create_invoices',
                'order' => 1,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'payment',
                'step_name' => 'Payment',
                'step_description' => 'Process payment',
                'route_name' => 'billing.index',
                'required_permission' => 'manage_billing',
                'order' => 2,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
            [
                'step_key' => 'receipt',
                'step_name' => 'Receipt',
                'step_description' => 'Generate receipt',
                'route_name' => 'billing.index',
                'required_permission' => 'view_invoices',
                'order' => 3,
                'is_required' => true,
                'can_skip' => false,
                'auto_redirect' => false,
            ],
        ];

        $createdSteps = [];
        foreach ($steps as $stepData) {
            $stepData['workflow_id'] = $workflow->id;
            $stepData['route_parameters'] = ['id' => 'entity_id'];
            $createdSteps[$stepData['step_key']] = WorkflowStep::create($stepData);
        }

        $transitions = [
            ['from_step_key' => 'invoice_creation', 'to_step_key' => 'payment'],
            ['from_step_key' => 'payment', 'to_step_key' => 'receipt'],
        ];

        foreach ($transitions as $transitionData) {
            WorkflowTransition::create([
                'workflow_id' => $workflow->id,
                'from_step_id' => $createdSteps[$transitionData['from_step_key']]->id,
                'to_step_id' => $createdSteps[$transitionData['to_step_key']]->id,
                'condition_type' => 'always',
                'priority' => 1,
            ]);
        }
    }
}

