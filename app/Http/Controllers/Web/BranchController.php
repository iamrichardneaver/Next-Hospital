<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Display a listing of branches (server-side rendering)
     */
    public function index()
    {
        $branches = Branch::latest('id')->paginate(20);
        
        $statistics = [
            'total' => Branch::count(),
            'active' => Branch::where('is_active', true)->count(),
            'inactive' => Branch::where('is_active', false)->count(),
        ];
        
        return view('branches.index', compact('branches', 'statistics'));
    }
    
    /**
     * Show the form for creating a new branch
     */
    public function create()
    {
        return view('branches.create');
    }
    
    /**
     * Store a newly created branch in database
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:500',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'is_active' => 'boolean',
            ]);
            
            $validated['created_by'] = auth()->id();
            
            $branch = Branch::create($validated);
            
            return redirect()->route('branches.index')
                ->with('success', 'Branch created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error creating branch: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create branch. Please try again.');
        }
    }
    
    /**
     * Display the specified branch
     */
    public function show(Branch $branch)
    {
        return view('branches.show', compact('branch'));
    }
    
    /**
     * Show the form for editing the specified branch
     */
    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }
    
    /**
     * Update the specified branch in database
     */
    public function update(Request $request, Branch $branch)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:500',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'is_active' => 'boolean',
            ]);
            
            $validated['updated_by'] = auth()->id();
            
            $branch->update($validated);
            
            return redirect()->route('branches.index')
                ->with('success', 'Branch updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error updating branch: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'branch_id' => $branch->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update branch. Please try again.');
        }
    }
    
    /**
     * Remove the specified branch from database
     */
    public function destroy(Branch $branch)
    {
        try {
            // Check if branch has users or patients
            $usersCount = \App\Models\FacilityUser::where('branch_id', $branch->id)->count();
            $patientsCount = \App\Models\Patient::where('branch_id', $branch->id)->count();
            
            if ($usersCount > 0 || $patientsCount > 0) {
                return back()
                    ->with('error', 'Cannot delete branch with associated users or patients. Consider deactivating instead.');
            }
            
            $branch->delete();
            
            return redirect()->route('branches.index')
                ->with('success', 'Branch deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('Error deleting branch: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'branch_id' => $branch->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete branch. Please try again.');
        }
    }
    
    /**
     * Switch user's current branch
     */
    public function switchBranch(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id'
        ]);
        
        $branch = Branch::findOrFail($validated['branch_id']);
        
        // Check if branch is active
        if (!$branch->is_active) {
            return back()->with('error', 'Cannot switch to an inactive branch.');
        }
        
        // Store branch selection in session
        session(['user_branch_id' => $branch->id]);
        session(['user_branch_name' => $branch->name]);
        
        // If user has facility_users relationship, update it
        $user = auth()->user();
        if ($user->staffProfile) {
            $user->staffProfile->update(['branch_id' => $branch->id]);
        }
        
        return back()->with('success', "Switched to {$branch->name} branch successfully!");
    }
}
