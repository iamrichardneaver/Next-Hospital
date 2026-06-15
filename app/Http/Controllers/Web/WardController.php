<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\BedAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WardController extends Controller
{
    public function index()
    {
        $wards = Ward::with('beds')->latest('id')->paginate(20);
        
        $statistics = [
            'total_wards' => Ward::count(),
            'total_beds' => Bed::count(),
            'occupied_beds' => Bed::where('status', 'occupied')->count(),
            'available_beds' => Bed::where('status', 'vacant')->count(),
        ];
        
        return view('wards.index', compact('wards', 'statistics'));
    }
    
    public function create()
    {
        return view('wards.create');
    }
    
    public function store(Request $request)
    {
        try {
            \DB::beginTransaction();
            
            $validated = $request->validate([
                'name' => 'required|string',
                'type' => 'required|in:male,female,general,pediatric,maternity,icu,isolation',
                'total_beds' => 'required|integer|min:1|max:500',
                'description' => 'nullable|string',
            ]);
            
            $validated['branch_id'] = auth()->user()->staffProfile->branch_id ?? 1;
            $validated['code'] = strtoupper(substr($validated['name'], 0, 3)) . '-' . rand(100, 999);
            $validated['available_beds'] = $validated['total_beds'];
            
            $ward = Ward::create($validated);
            
            // Create beds for the ward
            for ($i = 1; $i <= $validated['total_beds']; $i++) {
                Bed::create([
                    'ward_id' => $ward->id,
                    'bed_number' => $i,
                    'bed_type' => 'standard',
                    'status' => 'vacant',
                    'is_active' => true
                ]);
            }
            
            \DB::commit();
            
            return redirect()->route('wards.index')
                ->with('success', 'Ward created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Error creating ward: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create ward. Please try again.');
        }
    }
    
    public function show(Ward $ward)
    {
        $ward->load([
            'beds' => function($query) {
                $query->orderBy('bed_number');
            },
            'beds.currentAssignment.patient'
        ]);
        
        return view('wards.show', compact('ward'));
    }
    
    public function edit(Ward $ward)
    {
        return view('wards.edit', compact('ward'));
    }
    
    public function update(Request $request, Ward $ward)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'type' => 'required|in:male,female,general,pediatric,maternity,icu,isolation',
                'description' => 'nullable|string',
            ]);
            
            $ward->update($validated);
            
            return redirect()->route('wards.show', $ward)
                ->with('success', 'Ward updated!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating ward: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'ward_id' => $ward->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update ward. Please try again.');
        }
    }
    
    public function destroy(Ward $ward)
    {
        try {
            // Check if ward has occupied beds
            $occupiedBeds = Bed::where('ward_id', $ward->id)
                ->where('status', 'occupied')
                ->count();
            
            if ($occupiedBeds > 0) {
                return back()
                    ->with('error', 'Cannot delete ward with occupied beds. Please discharge all patients first.');
            }
            
            $ward->delete();
            
            return redirect()->route('wards.index')
                ->with('success', 'Ward deleted!');
        } catch (\Exception $e) {
            Log::error('Error deleting ward: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'ward_id' => $ward->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete ward. They may have existing records.');
        }
    }
}
