<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\SurgerySchedule;
use App\Models\SurgeryProcedure;
use App\Models\SurgeryTeam;
use App\Models\Patient;
use App\Models\User;
use App\Models\Theatre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurgeryController extends Controller
{
    use ExportsListData, ResolvesUserBranch;

    public function index()
    {
        $surgeries = SurgerySchedule::with(['patient', 'surgeon', 'theatre', 'procedure'])
            ->latest('id')
            ->paginate(20);

        $statistics = [
            'total' => SurgerySchedule::count(),
            'scheduled' => SurgerySchedule::where('status', 'scheduled')->count(),
            'in_progress' => SurgerySchedule::where('status', 'in_progress')->count(),
            'completed' => SurgerySchedule::where('status', 'completed')->count(),
            'today' => SurgerySchedule::whereDate('surgery_date', today())->count(),
        ];

        return view('surgery.index', compact('surgeries', 'statistics'));
    }

    public function create()
    {
        $patients = Patient::latest()->limit(200)->get();
        $surgeons = User::role('doctor')->where('is_active', true)->get();
        $theatres = Theatre::where('is_active', true)->get();
        $procedures = SurgeryProcedure::where('is_active', true)->orderBy('name')->get();

        return view('surgery.create', compact('patients', 'surgeons', 'theatres', 'procedures'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'procedure_id' => 'required|exists:surgery_procedures,id',
                'surgeon_id' => 'required|exists:users,id',
                'assistant_id' => 'nullable|exists:users,id',
                'anaesthetist_id' => 'nullable|exists:users,id',
                'theatre_id' => 'required|exists:theatres,id',
                'surgery_date' => 'required|date|after_or_equal:today',
                'surgery_time' => 'required|date_format:H:i',
                'estimated_duration' => 'required|integer|min:15|max:1440',
                'priority' => 'required|in:elective,urgent,emergency',
                'surgery_type' => 'required|in:major,minor,diagnostic,therapeutic',
                'anesthesia_type' => 'required|in:general,regional,local,conscious_sedation',
                'notes' => 'nullable|string|max:1000',
            ]);

            $branchId = $this->resolveUserBranchId('view_surgery_schedules');
            $procedure = SurgeryProcedure::findOrFail($validated['procedure_id']);

            DB::beginTransaction();

            $surgery = SurgerySchedule::create([
                'patient_id' => $validated['patient_id'],
                'surgeon_id' => $validated['surgeon_id'],
                'theatre_id' => $validated['theatre_id'],
                'procedure_id' => $validated['procedure_id'],
                'surgery_date' => $validated['surgery_date'],
                'surgery_time' => $validated['surgery_time'],
                'estimated_duration' => $validated['estimated_duration'],
                'priority' => $validated['priority'],
                'surgery_type' => $validated['surgery_type'],
                'anesthesia_type' => $validated['anesthesia_type'],
                'status' => 'scheduled',
                'notes' => $validated['notes'] ?? null,
                'branch_id' => $branchId,
                'created_by' => auth()->id(),
            ]);

            SurgeryTeam::create([
                'surgery_id' => $surgery->id,
                'user_id' => $validated['surgeon_id'],
                'role' => 'surgeon',
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
            ]);

            if (!empty($validated['assistant_id'])) {
                SurgeryTeam::create([
                    'surgery_id' => $surgery->id,
                    'user_id' => $validated['assistant_id'],
                    'role' => 'assistant_surgeon',
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id(),
                ]);
            }

            if (!empty($validated['anaesthetist_id'])) {
                SurgeryTeam::create([
                    'surgery_id' => $surgery->id,
                    'user_id' => $validated['anaesthetist_id'],
                    'role' => 'anesthesiologist',
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return redirect()->route('surgery.show', $surgery)
                ->with('success', 'Surgery scheduled successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating surgery schedule: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Failed to schedule surgery. Please try again.');
        }
    }

    public function show(SurgerySchedule $surgery)
    {
        $surgery->load(['patient', 'surgeon', 'theatre', 'procedure', 'team.user']);

        return view('surgery.show', compact('surgery'));
    }

    public function edit(SurgerySchedule $surgery)
    {
        $surgeons = User::role('doctor')->where('is_active', true)->get();
        $theatres = Theatre::where('is_active', true)->get();
        $procedures = SurgeryProcedure::where('is_active', true)->orderBy('name')->get();
        $surgery->load('team.user');

        $assistantId = $surgery->team->firstWhere('role', 'assistant_surgeon')?->user_id;
        $anaesthetistId = $surgery->team->firstWhere('role', 'anesthesiologist')?->user_id;

        return view('surgery.edit', compact('surgery', 'surgeons', 'theatres', 'procedures', 'assistantId', 'anaesthetistId'));
    }

    public function update(Request $request, SurgerySchedule $surgery)
    {
        try {
            $validated = $request->validate([
                'procedure_id' => 'required|exists:surgery_procedures,id',
                'surgeon_id' => 'required|exists:users,id',
                'assistant_id' => 'nullable|exists:users,id',
                'anaesthetist_id' => 'nullable|exists:users,id',
                'theatre_id' => 'required|exists:theatres,id',
                'surgery_date' => 'required|date',
                'surgery_time' => 'required|date_format:H:i',
                'estimated_duration' => 'required|integer|min:15|max:1440',
                'priority' => 'required|in:elective,urgent,emergency',
                'surgery_type' => 'required|in:major,minor,diagnostic,therapeutic',
                'anesthesia_type' => 'required|in:general,regional,local,conscious_sedation',
                'notes' => 'nullable|string|max:1000',
                'status' => 'required|in:scheduled,in_progress,completed,cancelled,postponed',
            ]);

            DB::beginTransaction();

            $surgery->update([
                'procedure_id' => $validated['procedure_id'],
                'surgeon_id' => $validated['surgeon_id'],
                'theatre_id' => $validated['theatre_id'],
                'surgery_date' => $validated['surgery_date'],
                'surgery_time' => $validated['surgery_time'],
                'estimated_duration' => $validated['estimated_duration'],
                'priority' => $validated['priority'],
                'surgery_type' => $validated['surgery_type'],
                'anesthesia_type' => $validated['anesthesia_type'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);

            $surgery->team()->delete();

            SurgeryTeam::create([
                'surgery_id' => $surgery->id,
                'user_id' => $validated['surgeon_id'],
                'role' => 'surgeon',
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
            ]);

            if (!empty($validated['assistant_id'])) {
                SurgeryTeam::create([
                    'surgery_id' => $surgery->id,
                    'user_id' => $validated['assistant_id'],
                    'role' => 'assistant_surgeon',
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id(),
                ]);
            }

            if (!empty($validated['anaesthetist_id'])) {
                SurgeryTeam::create([
                    'surgery_id' => $surgery->id,
                    'user_id' => $validated['anaesthetist_id'],
                    'role' => 'anesthesiologist',
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return redirect()->route('surgery.show', $surgery)
                ->with('success', 'Surgery schedule updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating surgery schedule: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'surgery_id' => $surgery->id,
            ]);

            return back()->withInput()->with('error', 'Failed to update surgery schedule.');
        }
    }

    public function start(SurgerySchedule $surgery)
    {
        $surgery->update([
            'status' => 'in_progress',
            'actual_start_time' => now(),
            'started_by' => auth()->id(),
        ]);

        return redirect()->route('surgery.show', $surgery)
            ->with('success', 'Surgery started successfully!');
    }

    public function complete(Request $request, SurgerySchedule $surgery)
    {
        $validated = $request->validate([
            'post_op_notes' => 'nullable|string|max:1000',
            'actual_duration' => 'nullable|integer|min:1',
        ]);

        $surgery->update([
            'status' => 'completed',
            'actual_end_time' => now(),
            'post_op_notes' => $validated['post_op_notes'] ?? null,
            'recovery_room_time' => $validated['actual_duration'] ?? null,
            'completed_by' => auth()->id(),
        ]);

        return redirect()->route('surgery.show', $surgery)
            ->with('success', 'Surgery completed successfully!');
    }

    public function destroy(SurgerySchedule $surgery)
    {
        try {
            if ($surgery->status === 'in_progress') {
                return back()->with('error', 'Cannot delete a surgery that is currently in progress.');
            }

            $surgery->team()->delete();
            $surgery->delete();

            return redirect()->route('surgery.index')
                ->with('success', 'Surgery schedule deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting surgery schedule: ' . $e->getMessage(), [
                'surgery_id' => $surgery->id,
            ]);

            return back()->with('error', 'Failed to delete surgery schedule.');
        }
    }

    public function export(Request $request)
    {
        $query = SurgerySchedule::with(['patient', 'surgeon', 'theatre', 'procedure'])->latest('id');

        return $this->exportFromQuery($request, $query, [
            'Patient' => fn ($s) => $s->patient?->full_name ?? '',
            'Patient Number' => fn ($s) => $s->patient?->patient_number ?? '',
            'Procedure' => fn ($s) => $s->procedure?->name ?? '',
            'Surgeon' => fn ($s) => $this->formatExportUserName($s->surgeon),
            'Theatre' => fn ($s) => $s->theatre?->name ?? '',
            'Surgery Date' => fn ($s) => $this->formatExportDate($s->surgery_date),
            'Status' => 'status',
        ], 'surgery-schedules', 'view_surgery_schedules');
    }
}
