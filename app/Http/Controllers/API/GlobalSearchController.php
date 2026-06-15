<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Consultation;
use App\Models\Visit;
use App\Models\Drug;
use App\Models\StoreItem;
use App\Models\LabRequest;
use App\Models\LabTestResult;
use App\Models\LabTestTemplate;
use App\Models\Prescription;
use App\Models\Invoice;
use App\Models\Complaint;
use App\Models\Branch;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\InsuranceProvider;
use App\Models\InsurancePolicy;
use App\Models\InsuranceClaim;
use App\Models\RadiologyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GlobalSearchController extends Controller
{
    /**
     * Perform intelligent global search across all system modules
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Search query must be at least 2 characters',
                'data' => []
            ], 400);
        }

        try {
            $results = [
                'patients' => $this->searchPatients($query),
                'appointments' => $this->searchAppointments($query),
                'users' => $this->searchUsers($query),
                'consultations' => $this->searchConsultations($query),
                'visits' => $this->searchVisits($query),
                'drugs' => $this->searchDrugs($query),
                'store_items' => $this->searchStoreItems($query),
                'lab_requests' => $this->searchLabRequests($query),
                'lab_results' => $this->searchLabResults($query),
                'lab_templates' => $this->searchLabTemplates($query),
                'prescriptions' => $this->searchPrescriptions($query),
                'invoices' => $this->searchInvoices($query),
                'complaints' => $this->searchComplaints($query),
                'branches' => $this->searchBranches($query),
                'wards' => $this->searchWards($query),
                'beds' => $this->searchBeds($query),
                'insurance_providers' => $this->searchInsuranceProviders($query),
                'insurance_policies' => $this->searchInsurancePolicies($query),
                'radiology_requests' => $this->searchRadiologyRequests($query),
            ];

            // Calculate total results
            $totalResults = array_sum(array_map('count', $results));

            // Get top 5 results from each category for quick display
            $quickResults = [];
            foreach ($results as $category => $items) {
                if (!empty($items)) {
                    $quickResults[$category] = array_slice($items, 0, 5);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'quick_results' => $quickResults,
                    'all_results' => $results,
                    'total_results' => $totalResults,
                    'query' => $query
                ],
                'message' => "Found {$totalResults} results for '{$query}'"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Search patients
     */
    private function searchPatients($query)
    {
        return Patient::where(function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('patient_number', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('nhis_number', 'like', "%{$query}%");
            })
            ->with(['branch'])
            ->limit(20)
            ->get()
            ->map(function($patient) {
                return [
                    'id' => $patient->id,
                    'type' => 'patient',
                    'title' => $patient->first_name . ' ' . $patient->last_name,
                    'subtitle' => 'Patient ID: ' . $patient->patient_number . ' | ' . $patient->phone,
                    'url' => route('patients.show', $patient->id),
                    'icon' => 'bi-person',
                    'category' => 'Patients',
                    'data' => $patient
                ];
            })
            ->toArray();
    }

    /**
     * Search appointments
     */
    private function searchAppointments($query)
    {
        return Appointment::with(['patient', 'doctor'])
            ->where(function($q) use ($query) {
                $q->whereHas('patient', function($patientQuery) use ($query) {
                    $patientQuery->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%")
                                ->orWhere('patient_number', 'like', "%{$query}%");
                })
                ->orWhereHas('doctor', function($doctorQuery) use ($query) {
                    $doctorQuery->where('first_name', 'like', "%{$query}%")
                               ->orWhere('last_name', 'like', "%{$query}%");
                })
                ->orWhere('reason', 'like', "%{$query}%")
                ->orWhere('notes', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($appointment) {
                return [
                    'id' => $appointment->id,
                    'type' => 'appointment',
                    'title' => $appointment->patient->first_name . ' ' . $appointment->patient->last_name,
                    'subtitle' => 'Appointment with Dr. ' . ($appointment->doctor ? $appointment->doctor->name : 'Unassigned') . ' - ' . $appointment->appointment_date,
                    'url' => route('appointments.show', $appointment->id),
                    'icon' => 'bi-calendar-event',
                    'category' => 'Appointments',
                    'data' => $appointment
                ];
            })
            ->toArray();
    }

    /**
     * Search users/staff
     */
    private function searchUsers($query)
    {
        return User::with(['roles', 'staffProfile'])
            ->where(function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($user) {
                $role = $user->roles->first();
                return [
                    'id' => $user->id,
                    'type' => 'user',
                    'title' => $user->name,
                    'subtitle' => ($role ? $role->name : 'User') . ' | ' . $user->email,
                    'url' => route('users.show', $user->id),
                    'icon' => 'bi-person-badge',
                    'category' => 'Staff',
                    'data' => $user
                ];
            })
            ->toArray();
    }

    /**
     * Search consultations
     */
    private function searchConsultations($query)
    {
        return Consultation::with(['patient', 'doctor'])
            ->where(function($q) use ($query) {
                $q->whereHas('patient', function($patientQuery) use ($query) {
                    $patientQuery->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%")
                                ->orWhere('patient_number', 'like', "%{$query}%");
                })
                ->orWhereHas('doctor', function($doctorQuery) use ($query) {
                    $doctorQuery->where('first_name', 'like', "%{$query}%")
                               ->orWhere('last_name', 'like', "%{$query}%");
                })
                ->orWhere('chief_complaint', 'like', "%{$query}%")
                ->orWhere('history_of_present_illness', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($consultation) {
                return [
                    'id' => $consultation->id,
                    'type' => 'consultation',
                    'title' => $consultation->patient->first_name . ' ' . $consultation->patient->last_name,
                    'subtitle' => 'Consultation with Dr. ' . ($consultation->doctor ? $consultation->doctor->name : 'Unassigned') . ' - ' . $consultation->consultation_date,
                    'url' => route('consultations.show', $consultation->id),
                    'icon' => 'bi-clipboard-pulse',
                    'category' => 'Consultations',
                    'data' => $consultation
                ];
            })
            ->toArray();
    }

    /**
     * Search visits
     */
    private function searchVisits($query)
    {
        return Visit::with(['patient', 'assignedDoctor'])
            ->where(function($q) use ($query) {
                $q->whereHas('patient', function($patientQuery) use ($query) {
                    $patientQuery->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%")
                                ->orWhere('patient_number', 'like', "%{$query}%");
                })
                ->orWhereHas('assignedDoctor', function($doctorQuery) use ($query) {
                    $doctorQuery->where('first_name', 'like', "%{$query}%")
                               ->orWhere('last_name', 'like', "%{$query}%");
                })
                ->orWhere('visit_type', 'like', "%{$query}%")
                ->orWhere('visit_token', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($visit) {
                return [
                    'id' => $visit->id,
                    'type' => 'visit',
                    'title' => $visit->patient->first_name . ' ' . $visit->patient->last_name,
                    'subtitle' => 'Visit #' . $visit->visit_token . ' (' . ucfirst($visit->visit_type) . ') - ' . $visit->check_in_time->format('Y-m-d'),
                    'url' => route('visits.show', $visit->id),
                    'icon' => 'bi-hospital',
                    'category' => 'Visits',
                    'data' => $visit
                ];
            })
            ->toArray();
    }

    /**
     * Search drugs
     */
    private function searchDrugs($query)
    {
        return Drug::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('generic_name', 'like', "%{$query}%")
                  ->orWhere('drug_code', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($drug) {
                return [
                    'id' => $drug->id,
                    'type' => 'drug',
                    'title' => $drug->name,
                    'subtitle' => 'Generic: ' . $drug->generic_name . ' | Code: ' . $drug->drug_code,
                    'url' => url('/drugs/' . $drug->id),
                    'icon' => 'bi-capsule',
                    'category' => 'Drugs',
                    'data' => $drug
                ];
            })
            ->toArray();
    }

    /**
     * Search store items
     */
    private function searchStoreItems($query)
    {
        return StoreItem::with(['drug'])
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhereHas('drug', function($drugQuery) use ($query) {
                      $drugQuery->where('name', 'like', "%{$query}%")
                               ->orWhere('generic_name', 'like', "%{$query}%");
                  });
            })
            ->where('is_active', true)
            ->limit(20)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'type' => 'store_item',
                    'title' => $item->name,
                    'subtitle' => 'Price: $' . number_format($item->price, 2) . ' | Category: ' . $item->category,
                    'url' => url('/store-items/' . $item->id),
                    'icon' => 'bi-shop',
                    'category' => 'Store Items',
                    'data' => $item
                ];
            })
            ->toArray();
    }

    /**
     * Search lab requests
     */
    private function searchLabRequests($query)
    {
        return LabRequest::with(['patient', 'doctor', 'template'])
            ->where(function($q) use ($query) {
                $q->whereHas('patient', function($patientQuery) use ($query) {
                    $patientQuery->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%")
                                ->orWhere('patient_number', 'like', "%{$query}%");
                })
                ->orWhereHas('doctor', function($doctorQuery) use ($query) {
                    $doctorQuery->where('first_name', 'like', "%{$query}%")
                               ->orWhere('last_name', 'like', "%{$query}%");
                })
                ->orWhereHas('template', function($testQuery) use ($query) {
                    $testQuery->where('template_name', 'like', "%{$query}%");
                })
                ->orWhere('request_number', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($request) {
                return [
                    'id' => $request->id,
                    'type' => 'lab_request',
                    'title' => $request->patient->first_name . ' ' . $request->patient->last_name,
                    'subtitle' => 'Lab Request #' . $request->request_number . ' - ' . ($request->template ? $request->template->template_name : 'Multiple Tests'),
                    'url' => route('lab.show', $request->id),
                    'icon' => 'bi-clipboard-data',
                    'category' => 'Lab Requests',
                    'data' => $request
                ];
            })
            ->toArray();
    }

    /**
     * Search lab results
     */
    private function searchLabResults($query)
    {
        return LabTestResult::with(['labRequest.patient', 'labRequest.template'])
            ->where(function($q) use ($query) {
                $q->whereHas('labRequest.patient', function($patientQuery) use ($query) {
                    $patientQuery->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%")
                                ->orWhere('patient_number', 'like', "%{$query}%");
                })
                ->orWhereHas('labRequest.template', function($testQuery) use ($query) {
                    $testQuery->where('template_name', 'like', "%{$query}%");
                })
                ->orWhere('result_value', 'like', "%{$query}%")
                ->orWhere('clinical_interpretation', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($result) {
                return [
                    'id' => $result->id,
                    'type' => 'lab_result',
                    'title' => $result->labRequest->patient->first_name . ' ' . $result->labRequest->patient->last_name,
                    'subtitle' => ($result->labRequest->template ? $result->labRequest->template->template_name : 'Lab Test') . ' - ' . $result->result_value,
                    'url' => route('lab.show', $result->labRequest->id),
                    'icon' => 'bi-graph-up',
                    'category' => 'Lab Results',
                    'data' => $result
                ];
            })
            ->toArray();
    }

    /**
     * Search prescriptions
     */
    private function searchPrescriptions($query)
    {
        return Prescription::with(['patient', 'doctor', 'orders.drug'])
            ->where(function($q) use ($query) {
                $q->whereHas('patient', function($patientQuery) use ($query) {
                    $patientQuery->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%")
                                ->orWhere('patient_number', 'like', "%{$query}%");
                })
                ->orWhereHas('doctor', function($doctorQuery) use ($query) {
                    $doctorQuery->where('first_name', 'like', "%{$query}%")
                               ->orWhere('last_name', 'like', "%{$query}%");
                })
                ->orWhere('prescription_number', 'like', "%{$query}%")
                ->orWhere('notes', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($prescription) {
                return [
                    'id' => $prescription->id,
                    'type' => 'prescription',
                    'title' => $prescription->patient->first_name . ' ' . $prescription->patient->last_name,
                    'subtitle' => 'Prescription #' . $prescription->prescription_number . ' - Dr. ' . $prescription->doctor->name,
                    'url' => url('/prescriptions/' . $prescription->id),
                    'icon' => 'bi-prescription',
                    'category' => 'Prescriptions',
                    'data' => $prescription
                ];
            })
            ->toArray();
    }

    /**
     * Search invoices
     */
    private function searchInvoices($query)
    {
        return Invoice::with(['patient'])
            ->where(function($q) use ($query) {
                $q->whereHas('patient', function($patientQuery) use ($query) {
                    $patientQuery->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%")
                                ->orWhere('patient_number', 'like', "%{$query}%");
                })
                ->orWhere('invoice_number', 'like', "%{$query}%")
                ->orWhere('total_amount', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'type' => 'invoice',
                    'title' => $invoice->patient->first_name . ' ' . $invoice->patient->last_name,
                    'subtitle' => 'Invoice #' . $invoice->invoice_number . ' - $' . number_format($invoice->total_amount, 2),
                    'url' => url('/invoices/' . $invoice->id),
                    'icon' => 'bi-receipt',
                    'category' => 'Invoices',
                    'data' => $invoice
                ];
            })
            ->toArray();
    }

    /**
     * Search complaints
     */
    private function searchComplaints($query)
    {
        return Complaint::with(['patient', 'assignedUser'])
            ->where(function($q) use ($query) {
                $q->whereHas('patient', function($patientQuery) use ($query) {
                    $patientQuery->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%")
                                ->orWhere('patient_number', 'like', "%{$query}%");
                })
                ->orWhereHas('assignedUser', function($userQuery) use ($query) {
                    $userQuery->where('first_name', 'like', "%{$query}%")
                             ->orWhere('last_name', 'like', "%{$query}%");
                })
                ->orWhere('subject', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->orWhere('complaint_number', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($complaint) {
                return [
                    'id' => $complaint->id,
                    'type' => 'complaint',
                    'title' => $complaint->subject,
                    'subtitle' => 'Complaint #' . $complaint->complaint_number . ' - ' . $complaint->patient->first_name . ' ' . $complaint->patient->last_name,
                    'url' => route('complaints.show', $complaint->id),
                    'icon' => 'bi-exclamation-triangle',
                    'category' => 'Complaints',
                    'data' => $complaint
                ];
            })
            ->toArray();
    }

    /**
     * Search branches
     */
    private function searchBranches($query)
    {
        return Branch::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('address', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($branch) {
                return [
                    'id' => $branch->id,
                    'type' => 'branch',
                    'title' => $branch->name,
                    'subtitle' => $branch->address . ' | ' . $branch->phone,
                    'url' => url('/branches/' . $branch->id),
                    'icon' => 'bi-building',
                    'category' => 'Branches',
                    'data' => $branch
                ];
            })
            ->toArray();
    }

    /**
     * Search lab templates
     */
    private function searchLabTemplates($query)
    {
        return LabTestTemplate::where(function($q) use ($query) {
                $q->where('template_name', 'like', "%{$query}%")
                  ->orWhere('category', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($template) {
                return [
                    'id' => $template->id,
                    'type' => 'lab_template',
                    'title' => $template->template_name,
                    'subtitle' => $template->category . ' | ' . ($template->description ? Str::limit($template->description, 50) : 'No description'),
                    'url' => route('lab.templates.show', $template->id),
                    'icon' => 'bi-clipboard-data',
                    'category' => 'Lab Templates',
                    'data' => $template
                ];
            })
            ->toArray();
    }

    /**
     * Search wards
     */
    private function searchWards($query)
    {
        return Ward::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('type', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($ward) {
                return [
                    'id' => $ward->id,
                    'type' => 'ward',
                    'title' => $ward->name,
                    'subtitle' => $ward->type . ' Ward | ' . $ward->total_beds . ' beds',
                    'url' => route('wards.show', $ward->id),
                    'icon' => 'bi-hospital',
                    'category' => 'Wards',
                    'data' => $ward
                ];
            })
            ->toArray();
    }

    /**
     * Search beds
     */
    private function searchBeds($query)
    {
        return Bed::with(['ward'])
            ->where(function($q) use ($query) {
                $q->where('bed_number', 'like', "%{$query}%")
                  ->orWhere('bed_type', 'like', "%{$query}%")
                  ->orWhereHas('ward', function($wardQuery) use ($query) {
                      $wardQuery->where('name', 'like', "%{$query}%");
                  });
            })
            ->limit(20)
            ->get()
            ->map(function($bed) {
                return [
                    'id' => $bed->id,
                    'type' => 'bed',
                    'title' => 'Bed ' . $bed->bed_number,
                    'subtitle' => $bed->ward->name . ' | ' . ucfirst($bed->bed_type) . ' | ' . ucfirst($bed->status),
                    'url' => route('beds.show', $bed->id),
                    'icon' => 'bi-bed',
                    'category' => 'Beds',
                    'data' => $bed
                ];
            })
            ->toArray();
    }

    /**
     * Search insurance providers
     */
    private function searchInsuranceProviders($query)
    {
        return InsuranceProvider::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('contact_person', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($provider) {
                return [
                    'id' => $provider->id,
                    'type' => 'insurance_provider',
                    'title' => $provider->name,
                    'subtitle' => $provider->code . ' | ' . $provider->contact_person . ' | ' . $provider->phone,
                    'url' => route('insurance.providers.show', $provider->id),
                    'icon' => 'bi-shield-check',
                    'category' => 'Insurance Providers',
                    'data' => $provider
                ];
            })
            ->toArray();
    }

    /**
     * Search insurance policies
     */
    private function searchInsurancePolicies($query)
    {
        return InsurancePolicy::with(['patient', 'insuranceProvider'])
            ->where(function($q) use ($query) {
                $q->where('policy_number', 'like', "%{$query}%")
                  ->orWhere('coverage_type', 'like', "%{$query}%")
                  ->orWhereHas('patient', function($patientQuery) use ($query) {
                      $patientQuery->where('first_name', 'like', "%{$query}%")
                                  ->orWhere('last_name', 'like', "%{$query}%")
                                  ->orWhere('patient_number', 'like', "%{$query}%");
                  })
                  ->orWhereHas('insuranceProvider', function($providerQuery) use ($query) {
                      $providerQuery->where('name', 'like', "%{$query}%");
                  });
            })
            ->limit(20)
            ->get()
            ->map(function($policy) {
                return [
                    'id' => $policy->id,
                    'type' => 'insurance_policy',
                    'title' => $policy->policy_number,
                    'subtitle' => $policy->patient->first_name . ' ' . $policy->patient->last_name . ' | ' . $policy->insuranceProvider->name,
                    'url' => route('insurance.policies.show', $policy->id),
                    'icon' => 'bi-file-earmark-medical',
                    'category' => 'Insurance Policies',
                    'data' => $policy
                ];
            })
            ->toArray();
    }


    /**
     * Search radiology requests
     */
    private function searchRadiologyRequests($query)
    {
        return RadiologyRequest::with(['patient', 'doctor'])
            ->where(function($q) use ($query) {
                $q->where('request_number', 'like', "%{$query}%")
                  ->orWhere('clinical_question', 'like', "%{$query}%")
                  ->orWhere('indication', 'like', "%{$query}%")
                  ->orWhereHas('patient', function($patientQuery) use ($query) {
                      $patientQuery->where('first_name', 'like', "%{$query}%")
                                  ->orWhere('last_name', 'like', "%{$query}%")
                                  ->orWhere('patient_number', 'like', "%{$query}%");
                  })
                  ->orWhereHas('doctor', function($doctorQuery) use ($query) {
                      $doctorQuery->where('first_name', 'like', "%{$query}%")
                                 ->orWhere('last_name', 'like', "%{$query}%");
                  });
            })
            ->limit(20)
            ->get()
            ->map(function($request) {
                return [
                    'id' => $request->id,
                    'type' => 'radiology_request',
                    'title' => $request->request_number,
                    'subtitle' => $request->patient->first_name . ' ' . $request->patient->last_name . ' | ' . ($request->clinical_question ?: 'Radiology Request'),
                    'url' => route('radiology.requests.show', $request->id),
                    'icon' => 'bi-camera',
                    'category' => 'Radiology Requests',
                    'data' => $request
                ];
            })
            ->toArray();
    }



    /**
     * Search surgery schedules
     */
    private function searchSurgerySchedules($query)
    {
        return SurgerySchedule::with(['patient', 'surgeon', 'theatre'])
            ->where(function($q) use ($query) {
                $q->where('surgery_number', 'like', "%{$query}%")
                  ->orWhere('surgery_type', 'like', "%{$query}%")
                  ->orWhere('notes', 'like', "%{$query}%")
                  ->orWhereHas('patient', function($patientQuery) use ($query) {
                      $patientQuery->where('first_name', 'like', "%{$query}%")
                                  ->orWhere('last_name', 'like', "%{$query}%")
                                  ->orWhere('patient_number', 'like', "%{$query}%");
                  })
                  ->orWhereHas('surgeon', function($surgeonQuery) use ($query) {
                      $surgeonQuery->where('first_name', 'like', "%{$query}%")
                                  ->orWhere('last_name', 'like', "%{$query}%");
                  });
            })
            ->limit(20)
            ->get()
            ->map(function($schedule) {
                return [
                    'id' => $schedule->id,
                    'type' => 'surgery_schedule',
                    'title' => $schedule->surgery_number,
                    'subtitle' => $schedule->patient->first_name . ' ' . $schedule->patient->last_name . ' | ' . ($schedule->surgery_type ?: 'Surgery') . ' | ' . $schedule->surgery_date,
                    'url' => route('surgery.schedules.show', $schedule->id),
                    'icon' => 'bi-scissors',
                    'category' => 'Surgery Schedules',
                    'data' => $schedule
                ];
            })
            ->toArray();
    }

    /**
     * Search theatres
     */
    private function searchTheatres($query)
    {
        return Theatre::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('location', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function($theatre) {
                return [
                    'id' => $theatre->id,
                    'type' => 'theatre',
                    'title' => $theatre->name,
                    'subtitle' => $theatre->location . ' | ' . ucfirst($theatre->status),
                    'url' => route('theatres.show', $theatre->id),
                    'icon' => 'bi-hospital',
                    'category' => 'Theatres',
                    'data' => $theatre
                ];
            })
            ->toArray();
    }

}
