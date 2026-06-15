<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\BloodDonation;
use App\Models\BloodInventory;
use App\Models\Patient;
use App\Models\Transfusion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BloodBankController extends Controller
{
    use ResolvesUserBranch;

    public function index()
    {
        $tab = request('tab', 'donations');
        $branchId = $this->resolveUserBranchId('view_wards');

        $donations = BloodDonation::with(['donor', 'branch'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('donation_date')
            ->paginate(15, ['*'], 'donations_page');

        $inventory = BloodInventory::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('blood_group')
            ->paginate(15, ['*'], 'inventory_page');

        $transfusions = Transfusion::with(['patient', 'branch'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('transfusion_date')
            ->paginate(15, ['*'], 'transfusions_page');

        $statistics = [
            'donations' => BloodDonation::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
            'inventory_units' => BloodInventory::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->sum('available_units'),
            'transfusions' => Transfusion::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
            'low_stock' => BloodInventory::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereColumn('available_units', '<', 'minimum_stock_level')->count(),
        ];

        return view('blood-bank.index', compact('tab', 'donations', 'inventory', 'transfusions', 'statistics'));
    }

    public function createDonation()
    {
        $branchId = $this->resolveUserBranchId('view_wards');
        $patients = Patient::orderBy('first_name')->limit(200)->get();

        return view('blood-bank.donations.create', compact('patients', 'branchId'));
    }

    public function storeDonation(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_wards');

        $validated = $request->validate([
            'donor_id' => 'nullable|exists:patients,id',
            'donor_name' => 'required|string|max:255',
            'donor_phone' => 'nullable|string|max:20',
            'blood_group' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'volume_ml' => 'required|numeric|min:1',
            'donation_date' => 'required|date',
            'blood_bag_number' => 'nullable|string|unique:blood_donations,blood_bag_number',
            'screening_notes' => 'nullable|string',
        ]);

        BloodDonation::create([
            ...$validated,
            'branch_id' => $branchId,
            'collected_by' => auth()->id(),
            'status' => 'pending',
        ]);

        return redirect()->route('blood-bank.index', ['tab' => 'donations'])
            ->with('success', 'Blood donation recorded successfully.');
    }

    public function showDonation(BloodDonation $donation)
    {
        $donation->load(['donor', 'collectedBy', 'testedBy', 'approvedBy', 'branch']);

        return view('blood-bank.donations.show', compact('donation'));
    }

    public function editDonation(BloodDonation $donation)
    {
        $patients = Patient::orderBy('first_name')->limit(200)->get();

        return view('blood-bank.donations.edit', compact('donation', 'patients'));
    }

    public function updateDonation(Request $request, BloodDonation $donation)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,tested,approved,rejected,used,expired',
            'hiv_test' => 'nullable|in:positive,negative,pending',
            'hbv_test' => 'nullable|in:positive,negative,pending',
            'hcv_test' => 'nullable|in:positive,negative,pending',
            'syphilis_test' => 'nullable|in:positive,negative,pending',
            'screening_notes' => 'nullable|string',
        ]);

        $donation->update($validated);

        if ($donation->all_tests_passed && $donation->status === 'tested') {
            $donation->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);
        }

        return redirect()->route('blood-bank.donations.show', $donation)
            ->with('success', 'Donation updated successfully.');
    }

    public function showInventory(BloodInventory $inventory)
    {
        $inventory->load(['branch', 'lastUpdatedBy']);

        return view('blood-bank.inventory.show', compact('inventory'));
    }

    public function editInventory(BloodInventory $inventory)
    {
        return view('blood-bank.inventory.edit', compact('inventory'));
    }

    public function updateInventory(Request $request, BloodInventory $inventory)
    {
        $validated = $request->validate([
            'minimum_stock_level' => 'sometimes|numeric|min:0',
            'optimal_stock_level' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $inventory->update([
            ...$validated,
            'last_updated_at' => now(),
            'last_updated_by' => auth()->id(),
        ]);

        return redirect()->route('blood-bank.inventory.show', $inventory)
            ->with('success', 'Inventory updated successfully.');
    }

    public function createTransfusion()
    {
        $branchId = $this->resolveUserBranchId('view_wards');
        $patients = Patient::orderBy('first_name')->limit(200)->get();
        $doctors = User::role('doctor')->where('is_active', true)->get();

        return view('blood-bank.transfusions.create', compact('patients', 'doctors', 'branchId'));
    }

    public function storeTransfusion(Request $request)
    {
        $branchId = $this->resolveUserBranchId('view_wards');

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'blood_group_patient' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'blood_group_donor' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'blood_component' => 'required|in:whole_blood,packed_cells,plasma,platelets,cryoprecipitate',
            'volume_ml' => 'required|numeric|min:1',
            'indication' => 'nullable|string',
            'doctor_id' => 'required|exists:users,id',
        ]);

        Transfusion::create([
            ...$validated,
            'branch_id' => $branchId,
            'status' => 'pending',
            'transfusion_date' => now(),
        ]);

        return redirect()->route('blood-bank.index', ['tab' => 'transfusions'])
            ->with('success', 'Transfusion order created successfully.');
    }

    public function showTransfusion(Transfusion $transfusion)
    {
        $transfusion->load(['patient', 'doctor', 'donation', 'branch', 'administeredBy']);

        return view('blood-bank.transfusions.show', compact('transfusion'));
    }

    public function editTransfusion(Transfusion $transfusion)
    {
        $doctors = User::role('doctor')->where('is_active', true)->get();

        return view('blood-bank.transfusions.edit', compact('transfusion', 'doctors'));
    }

    public function updateTransfusion(Request $request, Transfusion $transfusion)
    {
        $validated = $request->validate([
            'status' => 'sometimes|string|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'adverse_reactions' => 'nullable|string',
        ]);

        $transfusion->update($validated);

        return redirect()->route('blood-bank.transfusions.show', $transfusion)
            ->with('success', 'Transfusion updated successfully.');
    }
}
