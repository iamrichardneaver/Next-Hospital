<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppointmentFee;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AppointmentFeeController extends Controller
{
    /**
     * Display a listing of appointment fees.
     */
    public function index(Request $request)
    {
        $query = AppointmentFee::with(['doctor', 'branch', 'creator'])
            ->orderBy('branch_id')
            ->orderBy('appointment_type')
            ->orderBy('fee_category');

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by appointment type
        if ($request->filled('appointment_type')) {
            $query->where('appointment_type', $request->appointment_type);
        }

        // Filter by doctor
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('fee_category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('doctor', function($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $fees = $query->paginate(20);
        $branches = Branch::all();
        $doctors = User::role('doctor')->orderBy('name')->get();

        // Statistics
        $stats = [
            'total_fees' => AppointmentFee::count(),
            'active_fees' => AppointmentFee::active()->count(),
            'in_person_fees' => AppointmentFee::where('appointment_type', 'in-person')->active()->count(),
            'teleconsultation_fees' => AppointmentFee::where('appointment_type', 'teleconsultation')->active()->count(),
            'average_in_person_fee' => AppointmentFee::where('appointment_type', 'in-person')->active()->avg('base_fee'),
            'average_teleconsultation_fee' => AppointmentFee::where('appointment_type', 'teleconsultation')->active()->avg('base_fee'),
        ];

        return view('appointment-fees.index', compact('fees', 'branches', 'doctors', 'stats'));
    }

    /**
     * Show the form for creating a new appointment fee.
     */
    public function create()
    {
        $branches = Branch::all();
        $doctors = User::role('doctor')->orderBy('name')->get();

        return view('appointment-fees.create', compact('branches', 'doctors'));
    }

    /**
     * Store a newly created appointment fee in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'appointment_type' => 'required|in:in-person,teleconsultation',
            'fee_category' => 'required|string|max:50',
            'base_fee' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'platform_fee' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after:effective_from',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Check for conflicts
            $conflict = $this->checkFeeConflict(
                $request->doctor_id,
                $request->branch_id,
                $request->appointment_type,
                $request->fee_category,
                $request->effective_from
            );

            if ($conflict) {
                return redirect()->back()
                    ->with('error', 'A fee with similar criteria already exists and is still effective.')
                    ->withInput();
            }

            AppointmentFee::create([
                'doctor_id' => $request->doctor_id,
                'branch_id' => $request->branch_id,
                'appointment_type' => $request->appointment_type,
                'fee_category' => $request->fee_category,
                'base_fee' => $request->base_fee,
                'currency' => $request->currency,
                'platform_fee' => $request->platform_fee ?? 0,
                'tax_rate' => $request->tax_rate ?? 0,
                'discount_rules' => null, // Can be added later
                'is_active' => $request->boolean('is_active', true),
                'effective_from' => $request->effective_from,
                'effective_until' => $request->effective_until,
                'description' => $request->description,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('appointment-fees.index')
                ->with('success', 'Appointment fee created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create appointment fee: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified appointment fee.
     */
    public function show(AppointmentFee $appointmentFee)
    {
        $appointmentFee->load(['doctor', 'branch', 'creator', 'updater']);
        return view('appointment-fees.show', compact('appointmentFee'));
    }

    /**
     * Show the form for editing the specified appointment fee.
     */
    public function edit(AppointmentFee $appointmentFee)
    {
        $branches = Branch::all();
        $doctors = User::role('doctor')->orderBy('name')->get();

        return view('appointment-fees.edit', compact('appointmentFee', 'branches', 'doctors'));
    }

    /**
     * Update the specified appointment fee in storage.
     */
    public function update(Request $request, AppointmentFee $appointmentFee)
    {
        $validator = Validator::make($request->all(), [
            'base_fee' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'platform_fee' => 'sometimes|numeric|min:0',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after:effective_from',
            'description' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $appointmentFee->update(array_merge($request->all(), [
                'updated_by' => auth()->id(),
            ]));

            return redirect()->route('appointment-fees.index')
                ->with('success', 'Appointment fee updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update appointment fee: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified appointment fee from storage.
     */
    public function destroy(AppointmentFee $appointmentFee)
    {
        try {
            $appointmentFee->delete();

            return redirect()->route('appointment-fees.index')
                ->with('success', 'Appointment fee deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete appointment fee: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(AppointmentFee $appointmentFee)
    {
        try {
            $appointmentFee->update([
                'is_active' => !$appointmentFee->is_active,
                'updated_by' => auth()->id(),
            ]);

            $status = $appointmentFee->is_active ? 'activated' : 'deactivated';

            return redirect()->back()
                ->with('success', "Appointment fee {$status} successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Bulk create default fees for a branch
     */
    public function bulkCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'currency' => 'required|string|size:3',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $branch_id = $request->branch_id;
            $currency = $request->currency;
            $created_by = auth()->id();

            // Default fee structures
            $defaultFees = [
                ['appointment_type' => 'in-person', 'fee_category' => 'general', 'base_fee' => 50.00],
                ['appointment_type' => 'in-person', 'fee_category' => 'specialist', 'base_fee' => 100.00],
                ['appointment_type' => 'teleconsultation', 'fee_category' => 'general', 'base_fee' => 30.00],
                ['appointment_type' => 'teleconsultation', 'fee_category' => 'specialist', 'base_fee' => 60.00],
            ];

            $created = 0;
            foreach ($defaultFees as $feeData) {
                // Check if already exists
                $exists = AppointmentFee::where('branch_id', $branch_id)
                    ->where('appointment_type', $feeData['appointment_type'])
                    ->where('fee_category', $feeData['fee_category'])
                    ->whereNull('doctor_id')
                    ->exists();

                if (!$exists) {
                    AppointmentFee::create([
                        'branch_id' => $branch_id,
                        'doctor_id' => null,
                        'appointment_type' => $feeData['appointment_type'],
                        'fee_category' => $feeData['fee_category'],
                        'base_fee' => $feeData['base_fee'],
                        'currency' => $currency,
                        'platform_fee' => 0,
                        'tax_rate' => 0,
                        'is_active' => true,
                        'effective_from' => now()->toDateString(),
                        'description' => "Default {$feeData['fee_category']} {$feeData['appointment_type']} fee",
                        'created_by' => $created_by,
                    ]);
                    $created++;
                }
            }

            DB::commit();

            return redirect()->route('appointment-fees.index')
                ->with('success', "{$created} default fees created successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create default fees: ' . $e->getMessage());
        }
    }

    /**
     * Check for fee conflicts.
     */
    private function checkFeeConflict($doctorId, $branchId, $appointmentType, $feeCategory, $effectiveFrom, $excludeId = null)
    {
        $query = AppointmentFee::where('branch_id', $branchId)
            ->where('appointment_type', $appointmentType)
            ->where('fee_category', $feeCategory)
            ->where('is_active', true);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        } else {
            $query->whereNull('doctor_id');
        }

        if ($effectiveFrom) {
            $query->where(function($q) use ($effectiveFrom) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $effectiveFrom);
            });
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }
}

