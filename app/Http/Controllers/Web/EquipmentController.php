<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LabEquipment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = LabEquipment::with(['supplier', 'creator']);
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }
        
        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $equipment = $query->latest()->paginate(20);
        
        // Statistics
        $statistics = [
            'total' => LabEquipment::count(),
            'operational' => LabEquipment::where('status', 'operational')->count(),
            'maintenance' => LabEquipment::where('status', 'under_maintenance')->count(),
            'needs_maintenance' => LabEquipment::where('next_maintenance_date', '<=', now()->addDays(7))->count(),
        ];
        
        return view('lab.equipment.index', compact('equipment', 'statistics'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('lab.equipment.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'model' => 'nullable|string|max:255',
                'manufacturer' => 'nullable|string|max:255',
                'serial_number' => 'required|string|max:255|unique:lab_equipment',
                'equipment_type' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'installation_date' => 'nullable|date',
                'warranty_expiry' => 'nullable|date',
                'status' => 'required|in:operational,under_maintenance,out_of_service,retired',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'purchase_date' => 'nullable|date',
                'purchase_cost' => 'nullable|numeric|min:0',
            ]);
            
            $validated['is_active'] = true;
            $validated['created_by'] = auth()->id();
            
            LabEquipment::create($validated);
            
            return redirect()->route('lab.equipment.index')
                ->with('success', 'Equipment added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating equipment: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create equipment. Please try again.');
        }
    }

    public function show(LabEquipment $equipment)
    {
        $equipment->load(['supplier', 'creator', 'maintenanceRecords.performer']);
        return view('lab.equipment.show', compact('equipment'));
    }

    public function edit(LabEquipment $equipment)
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('lab.equipment.edit', compact('equipment', 'suppliers'));
    }

    public function update(Request $request, LabEquipment $equipment)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'model' => 'nullable|string|max:255',
                'manufacturer' => 'nullable|string|max:255',
                'serial_number' => 'required|string|max:255|unique:lab_equipment,serial_number,' . $equipment->id,
                'equipment_type' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'installation_date' => 'nullable|date',
                'warranty_expiry' => 'nullable|date',
                'status' => 'required|in:operational,under_maintenance,out_of_service,retired',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'purchase_date' => 'nullable|date',
                'purchase_cost' => 'nullable|numeric|min:0',
            ]);
            
            $validated['updated_by'] = auth()->id();
            
            $equipment->update($validated);
            
            return redirect()->route('lab.equipment.index')
                ->with('success', 'Equipment updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating equipment: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'equipment_id' => $equipment->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update equipment. Please try again.');
        }
    }

    public function destroy(LabEquipment $equipment)
    {
        try {
            $equipment->delete();
            
            return redirect()->route('lab.equipment.index')
                ->with('success', 'Equipment deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting equipment: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'equipment_id' => $equipment->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete equipment. Please try again.');
        }
    }
}
