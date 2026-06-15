<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Bed;
use App\Models\IcuLog;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IcuController extends Controller
{
    use ResolvesUserBranch;

    public function index()
    {
        $branchId = $this->resolveUserBranchId('view_wards');

        $logsQuery = IcuLog::with(['patient', 'attendingDoctor', 'assignedNurse', 'bed', 'branch'])
            ->where('branch_id', $branchId);

        if (auth()->user()->hasRole('doctor') && !auth()->user()->can('manage_wards')) {
            $logsQuery->where('attending_doctor_id', auth()->id());
        }

        $logs = $logsQuery->latest('admission_time')->paginate(20);

        $statistics = [
            'active' => IcuLog::where('branch_id', $branchId)->where('status', 'active')->count(),
            'critical' => IcuLog::where('branch_id', $branchId)->where('patient_condition', 'critical')->where('status', 'active')->count(),
            'on_ventilator' => IcuLog::where('branch_id', $branchId)->where('on_ventilator', true)->where('status', 'active')->count(),
            'total' => IcuLog::where('branch_id', $branchId)->count(),
        ];

        return view('icu.index', compact('logs', 'statistics'));
    }

    public function create()
    {
        $branchId = $this->resolveUserBranchId('manage_wards');

        $patients = Patient::orderBy('first_name')->limit(200)->get();
        $doctors = User::role('doctor')->where('is_active', true)->get();
        $nurses = User::role('nurse')->where('is_active', true)->get();
        $beds = Bed::whereHas('ward', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->where(function ($wq) {
                    $wq->where('type', 'icu')
                        ->orWhere('name', 'like', '%ICU%');
                });
        })->where('status', 'available')->with('ward')->get();

        $visits = Visit::where('status', 'active')
            ->where('branch_id', $branchId)
            ->where('visit_type', 'IPD')
            ->with('patient')
            ->latest()
            ->limit(50)
            ->get();

        return view('icu.create', compact('patients', 'doctors', 'nurses', 'beds', 'visits', 'branchId'));
    }

    public function store(Request $request)
    {
        $branchId = $this->resolveUserBranchId('manage_wards');

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'bed_id' => 'nullable|exists:beds,id',
            'admission_time' => 'required|date',
            'admission_type' => 'required|in:elective,emergency,transfer',
            'admission_diagnosis' => 'nullable|string|max:1000',
            'chief_complaint' => 'nullable|string|max:1000',
            'attending_doctor_id' => 'required|exists:users,id',
            'assigned_nurse_id' => 'nullable|exists:users,id',
            'patient_condition' => 'nullable|in:stable,serious,critical',
        ]);

        DB::beginTransaction();

        try {
            $icuLog = IcuLog::create([
                ...$validated,
                'branch_id' => $branchId,
                'patient_condition' => $validated['patient_condition'] ?? 'serious',
                'recorded_by' => auth()->id(),
                'recorded_at' => now(),
                'status' => 'active',
            ]);

            if (!empty($validated['bed_id'])) {
                Bed::where('id', $validated['bed_id'])->update(['status' => 'occupied']);
            }

            DB::commit();

            return redirect()->route('icu.index')
                ->with('success', 'ICU admission recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to record ICU admission: ' . $e->getMessage())->withInput();
        }
    }

    public function show(IcuLog $icu)
    {
        $branchId = $this->resolveUserBranchId('view_wards');
        if ((int) $icu->branch_id !== (int) $branchId && !auth()->user()->hasRole('super_admin')) {
            abort(403);
        }

        $icu->load(['patient', 'visit', 'bed.ward', 'attendingDoctor', 'assignedNurse', 'recordedBy', 'branch']);

        return view('icu.show', compact('icu'));
    }

    public function edit(IcuLog $icu)
    {
        $branchId = $this->resolveUserBranchId('manage_wards');
        if ((int) $icu->branch_id !== (int) $branchId && !auth()->user()->hasRole('super_admin')) {
            abort(403);
        }

        $doctors = User::role('doctor')->where('is_active', true)->get();
        $nurses = User::role('nurse')->where('is_active', true)->get();
        $beds = Bed::whereHas('ward', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->where(function ($wq) {
                    $wq->where('type', 'icu')->orWhere('name', 'like', '%ICU%');
                });
        })->whereIn('status', ['available', 'occupied'])->with('ward')->get();

        return view('icu.edit', compact('icu', 'doctors', 'nurses', 'beds'));
    }

    public function update(Request $request, IcuLog $icu)
    {
        $branchId = $this->resolveUserBranchId('manage_wards');

        $validated = $request->validate([
            'bed_id' => 'nullable|exists:beds,id',
            'admission_diagnosis' => 'nullable|string|max:1000',
            'chief_complaint' => 'nullable|string|max:1000',
            'attending_doctor_id' => 'required|exists:users,id',
            'assigned_nurse_id' => 'nullable|exists:users,id',
            'patient_condition' => 'nullable|in:stable,serious,critical',
            'on_ventilator' => 'nullable|boolean',
            'doctor_notes' => 'nullable|string',
            'nursing_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $oldBedId = $icu->bed_id;
            $icu->update([
                ...$validated,
                'on_ventilator' => (bool) ($validated['on_ventilator'] ?? false),
                'recorded_by' => auth()->id(),
                'recorded_at' => now(),
            ]);

            if (!empty($validated['bed_id']) && $oldBedId != $validated['bed_id']) {
                if ($oldBedId) {
                    Bed::where('id', $oldBedId)->update(['status' => 'vacant']);
                }
                Bed::where('id', $validated['bed_id'])->update(['status' => 'occupied']);
            }

            DB::commit();

            return redirect()->route('icu.show', $icu)->with('success', 'ICU record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to update ICU record.')->withInput();
        }
    }

    public function dischargeForm(IcuLog $icu)
    {
        $this->resolveUserBranchId('manage_wards');

        if ($icu->status !== 'active') {
            return redirect()->route('icu.show', $icu)->with('error', 'Patient is not actively admitted to ICU.');
        }

        return view('icu.discharge', compact('icu'));
    }

    public function discharge(Request $request, IcuLog $icu)
    {
        $validated = $request->validate([
            'discharge_time' => 'required|date',
            'discharge_notes' => 'nullable|string|max:2000',
            'discharge_destination' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $icu->update([
                'discharge_time' => $validated['discharge_time'],
                'discharge_notes' => $validated['discharge_notes'] ?? null,
                'discharge_destination' => $validated['discharge_destination'] ?? null,
                'status' => 'discharged',
            ]);

            if ($icu->bed_id) {
                Bed::where('id', $icu->bed_id)->update(['status' => 'vacant']);
            }

            DB::commit();

            return redirect()->route('icu.show', $icu)->with('success', 'Patient discharged from ICU successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to discharge patient.')->withInput();
        }
    }
}
