<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\Drug;
use App\Models\DrugStock;
use App\Models\Prescription;
use App\Models\DrugOrder;
use App\Models\Patient;
use App\Models\User;
use App\Models\Branch;
use App\Models\Visit;
use App\Models\Invoice;
use App\Models\InsurancePolicy;
use App\Models\InsuranceClaim;
use App\Exceptions\PaymentGateException;
use App\Services\PaymentPolicyService;
use App\Services\PendingChargesService;
use App\Services\PharmacyInventoryAlertService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyController extends Controller
{
    use ExportsListData, ResolvesUserBranch, WorkflowNavigation;
    public function index(Request $request)
    {
        $query = Drug::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%")
                  ->orWhere('drug_code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }
        
        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        // Filter by stock status
        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->whereHas('stocks', function($q) {
                    $q->whereColumn('current_stock', '<=', 'reorder_level');
                });
            } elseif ($request->stock_status === 'out') {
                $query->whereHas('stocks', function($q) {
                    $q->where('current_stock', 0);
                });
            } elseif ($request->stock_status === 'in_stock') {
                $query->whereHas('stocks', function($q) {
                    $q->where('current_stock', '>', 0);
                });
            }
        }
        
        // Filter by prescription required
        if ($request->filled('prescription_required')) {
            $query->where('requires_prescription', $request->prescription_required);
        }
        
        // Filter by NHIS coverage
        if ($request->filled('nhis_covered')) {
            $query->where('nhis_covered', $request->nhis_covered);
        }
        
        $drugs = $query->latest('id')->paginate(20)->withQueryString();
        
        // Get unique categories for filter
        $categories = Drug::distinct()->pluck('category')->filter()->sort()->values();
        
        $alertService = app(PharmacyInventoryAlertService::class);
        $alertCounts = $alertService->getAlertCounts($this->userBranchId());

        // Calculate total inventory value
        $totalValue = DrugStock::where('is_active', true)
            ->selectRaw('SUM(current_stock * cost_price) as total')
            ->value('total') ?? 0;
        
        $statistics = [
            'total_drugs' => Drug::count(),
            'active_drugs' => Drug::where('is_active', true)->count(),
            'prescription_required' => Drug::where('requires_prescription', true)->count(),
            'nhis_covered' => Drug::where('nhis_covered', true)->count(),
            'low_stock' => $alertCounts['low_stock'],
            'out_of_stock' => $alertCounts['out_of_stock'],
            'expiring_soon' => $alertCounts['expiring_soon'],
            'total_value' => $totalValue,
        ];
        
        return view('pharmacy.index', compact('drugs', 'statistics', 'categories'));
    }
    
    public function create()
    {
        return view('pharmacy.create');
    }
    
    public function store(Request $request)
    {
        \Log::info('Drug creation started', ['user_id' => auth()->id(), 'request_data' => $request->all()]);
        
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'generic_name' => 'nullable|string|max:255',
                'drug_code' => 'nullable|string|max:50',
                'category' => 'required|string|max:100',
                'dosage_form' => 'required|string|max:100',
                'strength' => 'nullable|string|max:50',
                'unit' => 'nullable|string|max:50',
                'manufacturer' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'selling_price' => 'required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'nhis_price' => 'nullable|numeric|min:0',
                'requires_prescription' => 'nullable|in:on,1,true,0,false',
                'nhis_covered' => 'nullable|in:on,1,true,0,false',
                // Stock fields
                'current_stock' => 'required|integer|min:0',
                'reorder_level' => 'required|integer|min:0',
                'minimum_stock' => 'nullable|integer|min:0',
                'batch_number' => 'nullable|string|max:255',
                'expiry_date' => 'nullable|date|after:today',
                'supplier' => 'nullable|string|max:255',
            ]);
            
            DB::beginTransaction();
            
            $validated['created_by'] = auth()->id();
            // Convert checkbox values to boolean
            $validated['requires_prescription'] = $request->has('requires_prescription') && in_array($request->input('requires_prescription'), ['on', '1', 'true', true]) ? 1 : 0;
            $validated['nhis_covered'] = $request->has('nhis_covered') && in_array($request->input('nhis_covered'), ['on', '1', 'true', true]) ? 1 : 0;
            $validated['is_active'] = true;
            
            // Separate drug and stock data
            $drugData = collect($validated)->except([
                'current_stock', 'reorder_level', 'minimum_stock', 
                'batch_number', 'expiry_date', 'supplier'
            ])->toArray();
            
            // Create drug
            $drug = Drug::create($drugData);
            
            \Log::info('Drug created successfully', ['drug_id' => $drug->id]);
            
            // Get user's branch
            $userBranch = Branch::find($this->userBranchId());
            if (!$userBranch) {
                throw new \Exception('User not assigned to any branch');
            }
            
            // Create initial stock record
            $stockData = [
                'drug_id' => $drug->id,
                'branch_id' => $userBranch->id,
                'current_stock' => $validated['current_stock'],
                'reorder_level' => $validated['reorder_level'],
                'minimum_stock' => $validated['minimum_stock'] ?? 5,
                'maximum_stock' => $validated['reorder_level'] * 3, // Auto-calculate max stock
                'batch_number' => $validated['batch_number'] ?? null,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'supplier' => $validated['supplier'] ?? null,
                'cost_price' => $validated['cost_price'] ?? 0,
                'selling_price' => $validated['selling_price'],
                'is_active' => true,
                'created_by' => auth()->id(),
            ];
            
            DrugStock::create($stockData);
            
            \Log::info('Drug stock created successfully', [
                'drug_id' => $drug->id, 
                'branch_id' => $userBranch->id,
                'stock_quantity' => $validated['current_stock']
            ]);
            
            DB::commit();
            
            return redirect()->route('pharmacy.index')
                ->with('success', 'Drug and initial stock added successfully! Drug #: ' . $drug->drug_number);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Drug creation failed', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create drug: ' . $e->getMessage());
        }
    }
    
    public function show(Drug $pharmacy)
    {
        return view('pharmacy.show', compact('pharmacy'));
    }
    
    public function edit(Drug $pharmacy)
    {
        // Get user's branch
        $userBranchId = $this->userBranchId();
        $userBranch = Branch::find($userBranchId);
        
        // Get current stock for this drug at user's branch
        $currentStock = null;
        if ($userBranch) {
            $currentStock = DrugStock::where('drug_id', $pharmacy->id)
                ->where('branch_id', $userBranch->id)
                ->first();
        }
        
        return view('pharmacy.edit', compact('pharmacy', 'currentStock'));
    }
    
    public function update(Request $request, Drug $pharmacy)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'generic_name' => 'nullable|string|max:255',
                'drug_code' => 'nullable|string|max:50',
                'category' => 'required|string|max:100',
                'dosage_form' => 'required|string|max:100',
                'strength' => 'nullable|string|max:50',
                'unit' => 'nullable|string|max:50',
                'manufacturer' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'selling_price' => 'required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'nhis_price' => 'nullable|numeric|min:0',
                'requires_prescription' => 'nullable|in:on,1,true,0,false',
                'nhis_covered' => 'nullable|in:on,1,true,0,false',
                // Stock fields
                'manage_stock' => 'nullable',
                'add_quantity' => 'nullable|integer|min:0',
                'reorder_level' => 'required|integer|min:0',
                'minimum_stock' => 'nullable|integer|min:0',
                'batch_number' => 'nullable|string|max:255',
                'expiry_date' => 'nullable|date',
                'supplier' => 'nullable|string|max:255',
            ]);
            
            DB::beginTransaction();
            
            $validated['updated_by'] = auth()->id();
            // Convert checkbox values to boolean
            $validated['requires_prescription'] = $request->has('requires_prescription') && in_array($request->input('requires_prescription'), ['on', '1', 'true', true]) ? 1 : 0;
            $validated['nhis_covered'] = $request->has('nhis_covered') && in_array($request->input('nhis_covered'), ['on', '1', 'true', true]) ? 1 : 0;
            
            // Separate drug and stock data
            $drugData = collect($validated)->except([
                'manage_stock', 'add_quantity', 'reorder_level', 'minimum_stock', 
                'batch_number', 'expiry_date', 'supplier'
            ])->toArray();
            
            // Update drug
            $pharmacy->update($drugData);
            
            // Manage stock if requested
            if ($request->has('manage_stock') && $request->input('manage_stock') == '1') {
                $userBranchId = $this->userBranchId();
                $userBranch = Branch::find($userBranchId);
                
                if (!$userBranch) {
                    throw new \Exception('User not assigned to any branch');
                }
                
                $stock = DrugStock::where('drug_id', $pharmacy->id)
                    ->where('branch_id', $userBranchId)
                    ->first();
                
                $addQuantity = (int) ($validated['add_quantity'] ?? 0);
                
                if ($stock) {
                    // Update existing stock
                    $stockUpdateData = [
                        'reorder_level' => $validated['reorder_level'],
                        'minimum_stock' => $validated['minimum_stock'] ?? $stock->minimum_stock,
                        'batch_number' => $validated['batch_number'] ?? $stock->batch_number,
                        'expiry_date' => $validated['expiry_date'] ?? $stock->expiry_date,
                        'supplier' => $validated['supplier'] ?? $stock->supplier,
                        'selling_price' => $validated['selling_price'],
                        'cost_price' => $validated['cost_price'] ?? $stock->cost_price,
                        'updated_by' => auth()->id(),
                    ];
                    
                    // Add to current stock if quantity specified
                    if ($addQuantity > 0) {
                        $stockUpdateData['current_stock'] = $stock->current_stock + $addQuantity;
                        
                        \Log::info('Stock quantity added', [
                            'drug_id' => $pharmacy->id,
                            'branch_id' => $userBranch->id,
                            'previous_stock' => $stock->current_stock,
                            'added_quantity' => $addQuantity,
                            'new_stock' => $stockUpdateData['current_stock']
                        ]);
                    }
                    
                    $stock->update($stockUpdateData);
                    
                } else {
                    // Create new stock record
                    if ($addQuantity > 0) {
                        $stockData = [
                            'drug_id' => $pharmacy->id,
                            'branch_id' => $userBranch->id,
                            'current_stock' => $addQuantity,
                            'reorder_level' => $validated['reorder_level'],
                            'minimum_stock' => $validated['minimum_stock'] ?? 5,
                            'maximum_stock' => $validated['reorder_level'] * 3,
                            'batch_number' => $validated['batch_number'] ?? null,
                            'expiry_date' => $validated['expiry_date'] ?? null,
                            'supplier' => $validated['supplier'] ?? null,
                            'cost_price' => $validated['cost_price'] ?? 0,
                            'selling_price' => $validated['selling_price'],
                            'is_active' => true,
                            'created_by' => auth()->id(),
                        ];
                        
                        DrugStock::create($stockData);
                        
                        \Log::info('Stock record created for drug', [
                            'drug_id' => $pharmacy->id,
                            'branch_id' => $userBranch->id,
                            'initial_stock' => $addQuantity
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('pharmacy.show', $pharmacy)
                ->with('success', 'Drug updated successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Drug update failed', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update drug: ' . $e->getMessage());
        }
    }
    
    public function destroy(Drug $pharmacy)
    {
        try {
            // Check if drug has active prescriptions or stock
            $hasPrescriptions = $pharmacy->orders()->count() > 0;
            $hasStock = $pharmacy->stocks()->where('current_stock', '>', 0)->count() > 0;
            
            if ($hasPrescriptions) {
                return back()
                    ->with('error', 'Cannot delete drug with existing prescriptions. Consider deactivating instead.');
            }
            
            if ($hasStock) {
                return back()
                    ->with('error', 'Cannot delete drug with existing stock. Please clear stock first.');
            }
            
            $pharmacy->delete();
            
            return redirect()->route('pharmacy.index')
                ->with('success', 'Drug deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('Error deleting drug: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'drug_id' => $pharmacy->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete drug. Please try again.');
        }
    }
    
    /**
     * Display prescription management page
     */
    public function prescriptions(Request $request)
    {
        $query = Prescription::with(['patient', 'doctor', 'orders.drug', 'consultation'])
            ->latest('id');

        // Scope by branch so pharmacists see prescriptions relevant to their branch (includes doctor-created from consultations)
        $userBranchId = $this->userBranchId();
        $query->where('branch_id', $userBranchId);
            
        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Filter by priority (urgent prescriptions first)
        if ($request->has('priority') && $request->priority !== 'all') {
            $query->whereHas('orders', function($q) use ($request) {
                $q->whereHas('drug', function($drugQuery) use ($request) {
                    if ($request->priority === 'critical') {
                        $drugQuery->whereIn('category', ['Antibiotics', 'Cardiovascular', 'Emergency']);
                    }
                });
            });
        }
        
        // Search by patient name, prescription number, or doctor
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('prescription_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($patientQuery) use ($search) {
                      $patientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('patient_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('doctor', function($doctorQuery) use ($search) {
                      $doctorQuery->where('first_name', 'like', "%{$search}%")
                                 ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
        
        $prescriptions = $query->paginate(20);
        
        // Enhanced statistics with stock alerts (same branch scope as list)
        $statsBase = Prescription::query()->where('branch_id', $userBranchId);
        $statistics = [
            'total' => (clone $statsBase)->count(),
            'pending' => (clone $statsBase)->whereIn('status', ['pending', 'active'])->count(),
            'dispensed' => (clone $statsBase)->whereIn('status', ['dispensed', 'completed'])->count(),
            'completed' => (clone $statsBase)->where('status', 'completed')->count(),
            'out_of_stock' => (clone $statsBase)->whereHas('orders', function($q) {
                $q->where('status', 'out_of_stock');
            })->count(),
            'urgent' => (clone $statsBase)->whereHas('orders', function($q) {
                $q->whereHas('drug', function($drugQuery) {
                    $drugQuery->whereIn('category', ['Antibiotics', 'Cardiovascular', 'Emergency']);
                });
            })->whereIn('status', ['pending', 'active'])->count(),
        ];
        
        return view('pharmacy.prescriptions.index', compact('prescriptions', 'statistics'));
    }
    
    /**
     * Show prescription details
     */
    public function showPrescription(Prescription $prescription)
    {
        $this->assertPrescriptionAccess($prescription);
        $prescription->load(['patient', 'doctor', 'orders.drug', 'branch', 'consultation.visit']);

        $paymentContext = $this->getPrescriptionPaymentContext($prescription);

        return view('pharmacy.prescriptions.show', array_merge(
            compact('prescription'),
            $paymentContext
        ));
    }

    /**
     * Patient portal: list own prescriptions
     */
    public function myPrescriptions(Request $request)
    {
        if (!auth()->user()->isPatient()) {
            abort(403, 'Only patients can access this page.');
        }

        $patient = $this->portalPatient();
        $query = Prescription::with(['patient', 'doctor', 'orders.drug', 'consultation'])
            ->where('patient_id', $patient->id)
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $prescriptions = $query->paginate(20);
        $statistics = [
            'total' => Prescription::where('patient_id', $patient->id)->count(),
            'pending' => Prescription::where('patient_id', $patient->id)->where('status', 'pending')->count(),
            'dispensed' => Prescription::where('patient_id', $patient->id)->where('status', 'dispensed')->count(),
            'completed' => Prescription::where('patient_id', $patient->id)->where('status', 'completed')->count(),
        ];

        return view('pharmacy.prescriptions.index', compact('prescriptions', 'statistics'));
    }

    /**
     * Patient portal: view own prescription details
     */
    public function myShowPrescription(Prescription $prescription)
    {
        if (!auth()->user()->isPatient()) {
            abort(403, 'Only patients can access this page.');
        }

        $this->assertPortalPatientOwns($prescription->patient_id);
        $prescription->load(['patient', 'doctor', 'orders.drug', 'branch']);

        $paymentSummary = ['can_proceed' => true, 'payment_required' => false, 'amount_due' => 0, 'is_paid' => true];
        $chargeBreakdown = [];

        return view('pharmacy.prescriptions.show', compact('prescription', 'paymentSummary', 'chargeBreakdown'));
    }
    
    /**
     * Dispense prescription
     */
    public function dispensePrescription(Request $request, Prescription $prescription)
    {
        if (!auth()->user()->can('dispense_drugs')) {
            abort(403, 'You do not have permission to dispense medications.');
        }

        $this->assertPrescriptionAccess($prescription);

        try {
            $this->assertCanDispense($prescription);
        } catch (PaymentGateException $e) {
            return $this->handlePaymentGateException($e, $prescription, $request);
        }

        $request->validate([
            'orders' => 'required|array',
            'orders.*.order_id' => 'required|exists:drug_orders,id',
            'orders.*.quantity_dispensed' => 'required|integer|min:1',
            'orders.*.notes' => 'nullable|string'
        ]);
        
        DB::beginTransaction();
        
        try {
            $dispensedOrders = [];
            $lowStockAlerts = [];
            
            foreach ($request->orders as $orderData) {
                $order = DrugOrder::findOrFail($orderData['order_id']);
                
                // Validate quantity
                if ($orderData['quantity_dispensed'] > $order->getRemainingQuantity()) {
                    throw new \Exception("Cannot dispense more than remaining quantity for {$order->drug->name}");
                }
                
                // Update drug order
                $order->update([
                    'quantity_dispensed' => $order->quantity_dispensed + $orderData['quantity_dispensed'],
                    'status' => ($order->quantity_dispensed + $orderData['quantity_dispensed']) >= $order->quantity ? 'dispensed' : 'pending',
                    'dispensed_by' => auth()->id(),
                    'dispensed_at' => now(),
                    'notes' => $orderData['notes'] ?? $order->notes
                ]);
                
                // Update stock and check for low stock alerts
                $stock = DrugStock::where('drug_id', $order->drug_id)
                    ->where('branch_id', $prescription->branch_id)
                    ->first();
                
                if ($stock) {
                    if ($stock->current_stock < $orderData['quantity_dispensed']) {
                        throw new \Exception("Insufficient stock for {$order->drug->name}");
                    }
                    
                    $newStock = $stock->current_stock - $orderData['quantity_dispensed'];
                    $stock->decrement('current_stock', $orderData['quantity_dispensed']);
                    
                    // Check for low stock alert
                    if ($newStock <= $stock->reorder_level && $newStock > 0) {
                        $lowStockAlerts[] = [
                            'drug' => $order->drug->name,
                            'current_stock' => $newStock,
                            'reorder_level' => $stock->reorder_level
                        ];
                    }
                }
                
                // Update consultation intervention status
                \App\Models\ConsultationIntervention::where('consultation_id', $prescription->consultation_id)
                    ->where('medication_id', $order->drug_id)
                    ->update(['status' => 'completed', 'completed_at' => now()]);
                
                $dispensedOrders[] = $order->load('drug');
            }
            
            // Update prescription status after dispensing
            $this->updatePrescriptionStatusAfterDispense($prescription);
            
            // Create notification for patient
            $this->createPrescriptionNotification($prescription, 'dispensed', 'Medications Dispensed', 
                'Your prescription has been dispensed and is ready for pickup.');

            // Log dispensing activity
            \Log::info('Prescription dispensed', [
                'prescription_id' => $prescription->id,
                'patient_id' => $prescription->patient_id,
                'dispensed_by' => auth()->id(),
                'dispensed_orders' => count($dispensedOrders)
            ]);
            
            DB::commit();
            
            // Complete workflow step if prescription has visit
            if ($prescription->consultation && $prescription->consultation->visit) {
                $this->completeWorkflowStep($prescription->consultation->visit, 'pharmacy_dispensing', [
                    'prescription_id' => $prescription->id,
                ]);
            }
            
            $message = 'Medications dispensed successfully!';
            if (!empty($lowStockAlerts)) {
                $message .= ' Note: Some drugs are running low on stock.';
            }
            
            // Use workflow navigation if available
            if ($prescription->consultation && $prescription->consultation->visit) {
                return $this->redirectToNextStep($prescription->consultation->visit, $message)
                    ->with('low_stock_alerts', $lowStockAlerts);
            }
            
            return redirect()->route('pharmacy.prescriptions.show', $prescription)
                ->with('success', $message)
                ->with('low_stock_alerts', $lowStockAlerts);
                
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error processing dispensing: ' . $e->getMessage());
        }
    }
    
    /**
     * Display dispensing workflow page
     */
    public function dispensing(Request $request)
    {
        $userBranchId = $this->userBranchId();
        
        $query = Prescription::with(['patient', 'doctor', 'orders.drug'])
            ->where('branch_id', $userBranchId)
            ->whereIn('status', ['pending', 'active']) // Look for both pending and active prescriptions
            ->latest('id');
            
        // Search by patient name or prescription number
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('prescription_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($patientQuery) use ($search) {
                      $patientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('patient_number', 'like', "%{$search}%");
                  });
            });
        }
        
        $prescriptions = $query->paginate(20);
        
        $statistics = [
            'pending_prescriptions' => Prescription::where('branch_id', $userBranchId)
                ->whereIn('status', ['pending', 'active'])
                ->count(),
            'dispensed_today' => Prescription::where('branch_id', $userBranchId)
                ->whereDate('updated_at', today())
                ->whereIn('status', ['dispensed', 'completed'])
                ->count(),
        ];
        
        return view('pharmacy.dispensing.index', compact('prescriptions', 'statistics'));
    }
    
    /**
     * Process dispensing workflow
     */
    public function processDispensing(Request $request)
    {
        if (!auth()->user()->can('dispense_drugs')) {
            abort(403, 'You do not have permission to dispense medications.');
        }

        $request->validate([
            'prescription_id' => 'required|exists:prescriptions,id',
            'orders' => 'required|array',
            'orders.*.order_id' => 'required|exists:drug_orders,id',
            'orders.*.quantity_dispensed' => 'required|integer|min:1',
            'orders.*.notes' => 'nullable|string'
        ]);
        
        $prescription = Prescription::findOrFail($request->prescription_id);
        $this->assertPrescriptionAccess($prescription);

        try {
            $this->assertCanDispense($prescription);
        } catch (PaymentGateException $e) {
            return $this->handlePaymentGateException($e, $prescription, $request);
        }
        
        DB::beginTransaction();
        
        try {
            $dispensedOrders = [];
            
            foreach ($request->orders as $orderData) {
                $order = DrugOrder::findOrFail($orderData['order_id']);
                
                // Validate quantity
                if ($orderData['quantity_dispensed'] > $order->getRemainingQuantity()) {
                    throw new \Exception("Cannot dispense more than remaining quantity for {$order->drug->name}");
                }
                
                // Update drug order
                $order->update([
                    'quantity_dispensed' => $order->quantity_dispensed + $orderData['quantity_dispensed'],
                    'status' => ($order->quantity_dispensed + $orderData['quantity_dispensed']) >= $order->quantity ? 'dispensed' : 'pending',
                    'dispensed_by' => auth()->id(),
                    'dispensed_at' => now(),
                    'notes' => $orderData['notes'] ?? $order->notes
                ]);
                
                // Update stock
                $stock = DrugStock::where('drug_id', $order->drug_id)
                    ->where('branch_id', $prescription->branch_id)
                    ->first();
                
                if ($stock) {
                    if ($stock->current_stock < $orderData['quantity_dispensed']) {
                        throw new \Exception("Insufficient stock for {$order->drug->name}");
                    }
                    $stock->decrement('current_stock', $orderData['quantity_dispensed']);
                }
                
                $dispensedOrders[] = $order->load('drug');
            }
            
            $this->updatePrescriptionStatusAfterDispense($prescription);
            
            DB::commit();
            
            return redirect()->route('pharmacy.dispensing')
                ->with('success', 'Medications dispensed successfully!');
                
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error processing dispensing: ' . $e->getMessage());
        }
    }
    
    /**
     * Display stock management page
     */
    public function stock(Request $request)
    {
        $userBranchId = $this->userBranchId();
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : $userBranchId;
        $alertService = app(PharmacyInventoryAlertService::class);
        $expiryDays = $alertService->expiryWarningDays();

        $query = DrugStock::with(['drug', 'branch'])
            ->where('is_active', true)
            ->whereHas('drug')
            ->where('branch_id', $branchId);
        
        // Filter by stock status
        if ($request->has('status') && $request->status !== 'all') {
            switch ($request->status) {
                case 'low_stock':
                    $query->whereColumn('current_stock', '<=', 'reorder_level');
                    break;
                case 'out_of_stock':
                    $query->where('current_stock', 0);
                    break;
                case 'expiring_soon':
                    $query->whereNotNull('expiry_date')
                        ->where('expiry_date', '>', now())
                        ->where('expiry_date', '<=', now()->addDays($expiryDays));
                    break;
                case 'expired':
                    $query->whereNotNull('expiry_date')
                        ->where('expiry_date', '<', now());
                    break;
                case 'high_value':
                    $query->whereRaw('current_stock * cost_price > 1000');
                    break;
                case 'fast_moving':
                    // Drugs with high dispensing frequency (last 30 days)
                    $query->whereHas('drug', function($drugQuery) {
                        $drugQuery->whereHas('orders', function($orderQuery) {
                            $orderQuery->where('status', 'dispensed')
                                      ->where('dispensed_at', '>=', now()->subDays(30));
                        });
                    });
                    break;
            }
        }
        
        // Search by drug name, category, or manufacturer
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('drug', function($drugQuery) use ($search) {
                $drugQuery->where('name', 'like', "%{$search}%")
                         ->orWhere('generic_name', 'like', "%{$search}%")
                         ->orWhere('drug_code', 'like', "%{$search}%")
                         ->orWhere('category', 'like', "%{$search}%")
                         ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }
        
        $stocks = $query->paginate(20)->withQueryString();

        $statsBase = DrugStock::query()
            ->where('is_active', true)
            ->whereHas('drug')
            ->where('branch_id', $branchId);
        
        // Branch-scoped statistics
        $statistics = [
            'total_items' => (clone $statsBase)->count(),
            'low_stock' => (clone $statsBase)->whereColumn('current_stock', '<=', 'reorder_level')->count(),
            'out_of_stock' => (clone $statsBase)->where('current_stock', 0)->count(),
            'expiring_soon' => (clone $statsBase)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '>', now())
                ->where('expiry_date', '<=', now()->addDays($expiryDays))
                ->count(),
            'expired' => (clone $statsBase)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<', now())
                ->count(),
            'total_value' => (clone $statsBase)
                ->selectRaw('SUM(current_stock * cost_price) as total')
                ->value('total') ?? 0,
            'reorder_needed' => (clone $statsBase)
                ->whereColumn('current_stock', '<=', 'reorder_level')
                ->selectRaw('SUM(GREATEST(reorder_level - current_stock, 0) * cost_price) as reorder_value')
                ->value('reorder_value') ?? 0,
        ];
        
        $branches = Branch::where('is_active', true)->get();
        
        // Branch-scoped critical alerts via alert service
        $criticalAlerts = [
            'out_of_stock_drugs' => $alertService->checkOutOfStock($branchId)->take(5),
            'expired_drugs' => $alertService->checkExpired($branchId)->take(5),
            'critical_low_stock' => $alertService->checkLowStock($branchId)
                ->filter(fn ($s) => $s->current_stock > 0)
                ->sortBy('current_stock')
                ->take(5)
                ->values(),
        ];
        
        return view('pharmacy.stock.index', compact(
            'stocks',
            'statistics',
            'branches',
            'criticalAlerts',
            'userBranchId',
            'branchId'
        ));
    }
    
    /**
     * Add new stock entry
     */
    public function addStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'drug_id' => 'required|exists:drugs,id',
                'branch_id' => 'required|exists:branches,id',
                'current_stock' => 'required|integer|min:0',
                'minimum_stock' => 'nullable|integer|min:0',
                'reorder_level' => 'required|integer|min:0',
                'batch_number' => 'nullable|string|max:255',
                'expiry_date' => 'nullable|date|after:today',
                'supplier' => 'nullable|string|max:255',
                'cost_price' => 'required|numeric|min:0',
                'selling_price' => 'required|numeric|min:0',
            ]);
            
            // Check if stock already exists for this drug and branch
            $existingStock = DrugStock::where('drug_id', $validated['drug_id'])
                ->where('branch_id', $validated['branch_id'])
                ->first();
            
            if ($existingStock) {
                return redirect()->back()
                    ->with('error', 'Stock already exists for this drug at the selected branch. Please use "Update Stock" instead.');
            }

            $this->assertResourceInUserBranch(
                (int) $validated['branch_id'],
                ['view_drugs', 'manage_pharmacy_inventory', 'dispense_drugs']
            );
            
            $validated['created_by'] = auth()->id();
            $validated['is_active'] = true;
            $validated['minimum_stock'] = $validated['minimum_stock'] ?? 10;
            $validated['maximum_stock'] = $validated['reorder_level'] * 3; // Auto-calculate max stock
            
            DrugStock::create($validated);
            
            return redirect()->route('pharmacy.stock')
                ->with('success', 'Stock added successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Failed to add stock', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to add stock: ' . $e->getMessage());
        }
    }
    
    /**
     * Update stock levels
     */
    public function updateStock(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:drug_stocks,id',
            'current_stock' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'notes' => 'nullable|string'
        ]);
        
        $stock = DrugStock::findOrFail($request->stock_id);

        $this->assertResourceInUserBranch(
            (int) $stock->branch_id,
            ['view_drugs', 'manage_pharmacy_inventory', 'dispense_drugs']
        );
        
        $stock->update([
            'current_stock' => $request->current_stock,
            'reorder_level' => $request->reorder_level,
            'updated_by' => auth()->id()
        ]);
        
        return redirect()->route('pharmacy.stock')
            ->with('success', 'Stock updated successfully!');
    }
    
    /**
     * Display dispensing history
     */
    public function history(Request $request)
    {
        $query = DrugOrder::with(['drug', 'prescription.patient', 'prescription.doctor', 'dispenser'])
            ->where('status', 'dispensed')
            ->latest('dispensed_at');
            
        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('dispensed_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('dispensed_at', '<=', $request->date_to);
        }
        
        // Filter by patient
        if ($request->has('patient_id')) {
            $query->whereHas('prescription', function($q) use ($request) {
                $q->where('patient_id', $request->patient_id);
            });
        }
        
        $orders = $query->paginate(20);
        
        $statistics = [
            'total_dispensed' => DrugOrder::where('status', 'dispensed')->count(),
            'dispensed_today' => DrugOrder::whereDate('dispensed_at', today())
                ->where('status', 'dispensed')->count(),
            'dispensed_this_week' => DrugOrder::whereBetween('dispensed_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->where('status', 'dispensed')->count(),
        ];
        
        $patients = Patient::latest()->get();
        
        return view('pharmacy.history.index', compact('orders', 'statistics', 'patients'));
    }
    
    /**
     * Print prescription
     */
    public function printPrescription(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'orders.drug', 'branch']);
        
        return view('pharmacy.prescriptions.print', compact('prescription'));
    }
    
    /**
     * Edit prescription
     */
    public function editPrescription(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'orders.drug', 'branch']);
        $drugs = Drug::where('is_active', true)->orderBy('name')->get();
        
        return view('pharmacy.prescriptions.edit', compact('prescription', 'drugs'));
    }
    
    /**
     * Update prescription
     */
    public function updatePrescription(Request $request, Prescription $prescription)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
            'orders' => 'nullable|array',
            'orders.*.drug_id' => 'required_with:orders|exists:drugs,id',
            'orders.*.quantity' => 'required_with:orders|integer|min:1',
            'orders.*.dosage_instructions' => 'required_with:orders|string|max:500',
            'orders.*.frequency' => 'nullable|string|max:100',
            'orders.*.duration' => 'nullable|string|max:100',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Update prescription notes
            $prescription->update([
                'notes' => $request->notes,
                'updated_by' => auth()->id()
            ]);
            
            // Update or create drug orders
            if ($request->has('orders')) {
                // Delete existing orders that are not in the request
                $existingOrderIds = collect($request->orders)->pluck('id')->filter();
                $prescription->orders()->whereNotIn('id', $existingOrderIds)->delete();
                
                foreach ($request->orders as $orderData) {
                    if (isset($orderData['id'])) {
                        // Update existing order
                        $order = DrugOrder::findOrFail($orderData['id']);
                        $order->update([
                            'drug_id' => $orderData['drug_id'],
                            'quantity' => $orderData['quantity'],
                            'dosage_instructions' => $orderData['dosage_instructions'],
                            'frequency' => $orderData['frequency'],
                            'duration' => $orderData['duration'],
                        ]);
                    } else {
                        // Create new order
                        DrugOrder::create([
                            'prescription_id' => $prescription->id,
                            'drug_id' => $orderData['drug_id'],
                            'quantity' => $orderData['quantity'],
                            'dosage_instructions' => $orderData['dosage_instructions'],
                            'instructions' => $orderData['dosage_instructions'], // Required field
                            'frequency' => $orderData['frequency'],
                            'duration' => $orderData['duration'],
                            'status' => 'pending'
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('pharmacy.prescriptions.show', $prescription)
                ->with('success', 'Prescription updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating prescription: ' . $e->getMessage());
        }
    }
    
    /**
     * Cancel prescription
     */
    public function cancelPrescription(Request $request, Prescription $prescription)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);
        
        if ($prescription->status === 'cancelled') {
            return redirect()->back()
                ->with('error', 'Prescription is already cancelled.');
        }
        
        $prescription->update([
            'status' => 'cancelled',
            'notes' => $prescription->notes . "\n\nCancelled: " . $request->reason,
            'updated_by' => auth()->id()
        ]);
        
        return redirect()->route('pharmacy.prescriptions.show', $prescription)
            ->with('success', 'Prescription cancelled successfully!');
    }
    
    /**
     * Complete prescription
     */
    public function completePrescription(Prescription $prescription)
    {
        if ($prescription->status !== 'dispensed') {
            return redirect()->back()
                ->with('error', 'Only dispensed prescriptions can be completed.');
        }
        
        $prescription->update([
            'status' => 'completed',
            'updated_by' => auth()->id()
        ]);
        
        return redirect()->route('pharmacy.prescriptions.show', $prescription)
            ->with('success', 'Prescription marked as completed!');
    }
    
    /**
     * Prescription history
     */
    public function prescriptionHistory(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'orders.drug', 'orders.dispenser', 'branch']);
        
        $history = $prescription->orders()
            ->where('status', 'dispensed')
            ->with(['drug', 'dispenser'])
            ->orderBy('dispensed_at', 'desc')
            ->get();
        
        return view('pharmacy.prescriptions.history', compact('prescription', 'history'));
    }

    /**
     * Get prescription analytics and insights
     */
    public function analytics(Request $request)
    {
        try {
            $dateRange = $request->get('date_range', '30'); // Default to last 30 days
            $startDate = now()->subDays($dateRange);
            $endDate = now();

            // Prescription trends
            $prescriptionTrends = Prescription::whereBetween('prescription_date', [$startDate, $endDate])
                ->selectRaw('DATE(prescription_date) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Top prescribed drugs - with null check
            $topDrugs = DrugOrder::whereHas('prescription', function($query) use ($startDate, $endDate) {
                    $query->whereBetween('prescription_date', [$startDate, $endDate]);
                })
                ->with('drug')
                ->whereNotNull('drug_id')
                ->selectRaw('drug_id, SUM(quantity) as total_quantity')
                ->groupBy('drug_id')
                ->orderBy('total_quantity', 'desc')
                ->limit(10)
                ->get()
                ->filter(function($item) {
                    return $item->drug !== null;
                });

            // Prescription status distribution
            $statusDistribution = Prescription::whereBetween('prescription_date', [$startDate, $endDate])
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            // Doctor prescription patterns - with null check
            $doctorPrescriptions = Prescription::whereBetween('prescription_date', [$startDate, $endDate])
                ->whereNotNull('doctor_id')
                ->with(['doctor' => function($query) {
                    $query->select('id', 'first_name', 'last_name', 'email');
                }])
                ->selectRaw('doctor_id, COUNT(*) as count')
                ->groupBy('doctor_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->filter(function($item) {
                    return $item->doctor !== null;
                });

            // Stock movement analysis - Optimized query
            $stockMovement = collect();
            
            // Get all drugs that had dispensing activity
            $activeDrugIds = DrugOrder::whereBetween('dispensed_at', [$startDate, $endDate])
                ->whereNotNull('dispensed_at')
                ->distinct()
                ->pluck('drug_id');

            if ($activeDrugIds->isNotEmpty()) {
                $stockMovement = DrugStock::with('drug')
                    ->whereIn('drug_id', $activeDrugIds)
                    ->where('is_active', true)
                    ->get()
                    ->map(function($stock) use ($startDate, $endDate) {
                        if (!$stock->drug) {
                            return null;
                        }

                        $dispensed = DrugOrder::where('drug_id', $stock->drug_id)
                            ->whereBetween('dispensed_at', [$startDate, $endDate])
                            ->whereNotNull('dispensed_at')
                            ->sum('quantity_dispensed') ?? 0;
                        
                        return [
                            'drug_name' => $stock->drug->name ?? 'Unknown',
                            'current_stock' => $stock->current_stock ?? 0,
                            'reorder_level' => $stock->reorder_level ?? 0,
                            'dispensed_quantity' => $dispensed,
                            'stock_health' => ($stock->current_stock ?? 0) > ($stock->reorder_level ?? 0) ? 'healthy' : 'low'
                        ];
                    })
                    ->filter()
                    ->values();
            }

            // Financial analytics - with safety checks
            $totalPrescriptionValue = 0;
            $prescriptions = Prescription::whereBetween('prescription_date', [$startDate, $endDate])
                ->with(['orders.drug' => function($query) {
                    $query->select('id', 'name', 'selling_price');
                }])
                ->get();

            foreach ($prescriptions as $prescription) {
                foreach ($prescription->orders as $order) {
                    if ($order->drug && $order->drug->selling_price) {
                        $totalPrescriptionValue += ($order->quantity ?? 0) * $order->drug->selling_price;
                    }
                }
            }

            $financialData = [
                'total_prescription_value' => $totalPrescriptionValue,
                'average_prescription_value' => 0,
                'revenue_by_category' => []
            ];

            // Calculate average prescription value
            $prescriptionCount = $prescriptions->count();
            if ($prescriptionCount > 0) {
                $financialData['average_prescription_value'] = $totalPrescriptionValue / $prescriptionCount;
            }

            return view('pharmacy.analytics.index', compact(
                'prescriptionTrends',
                'topDrugs',
                'statusDistribution',
                'doctorPrescriptions',
                'stockMovement',
                'financialData',
                'dateRange'
            ));
        } catch (\Exception $e) {
            \Log::error('Pharmacy Analytics Error: ' . $e->getMessage());
            
            // Return empty data on error
            return view('pharmacy.analytics.index', [
                'prescriptionTrends' => collect(),
                'topDrugs' => collect(),
                'statusDistribution' => collect(),
                'doctorPrescriptions' => collect(),
                'stockMovement' => collect(),
                'financialData' => [
                    'total_prescription_value' => 0,
                    'average_prescription_value' => 0,
                    'revenue_by_category' => []
                ],
                'dateRange' => $request->get('date_range', '30'),
                'error' => 'An error occurred while loading analytics data. Please try again.'
            ]);
        }
    }

    /**
     * Generate prescription billing integration
     */
    public function generateBilling(Request $request, Prescription $prescription)
    {
        $request->validate([
            'billing_type' => 'required|in:cash,insurance',
            'insurance_policy_id' => 'required_if:billing_type,insurance|exists:insurance_policies,id'
        ]);

        // Check if patient has active insurance policy
        $insurancePolicy = null;
        if ($request->billing_type === 'insurance') {
            $insurancePolicy = InsurancePolicy::findOrFail($request->insurance_policy_id);
            
            // Validate policy is active and covers patient
            if ($insurancePolicy->patient_id !== $prescription->patient_id || !$insurancePolicy->is_active) {
                return redirect()->back()
                    ->with('error', 'Invalid or inactive insurance policy.');
            }
        }

        // Get patient's branch for pricing context
        $patientBranch = $prescription->patient->branches()->first();
        $branchId = $patientBranch ? $patientBranch->id : auth()->user()->branches()->first()->id;
        
        $pricingService = app(\App\Services\PricingService::class);
        
        // Prepare invoice items with dynamic pricing
        $invoiceItems = [];
        $subtotal = 0;
        $insuranceCoverage = 0;
        $patientCoPay = 0;

        // Calculate prices for each drug in prescription
        foreach ($prescription->orders as $order) {
            // Use actual drug selling price from database
            $unitPrice = $order->drug->selling_price ?? 0;
            
            // Apply dynamic pricing (volume discounts, special rules)
            try {
                $pricing = $pricingService->calculateDrugPrice(
                    $order->drug_id,
                    $order->quantity,
                    $prescription->patient_id,
                    $branchId
                );
                $finalUnitPrice = $pricing['final_price'] / $order->quantity; // Get unit price
                $itemTotal = $pricing['final_price'];
            } catch (\Exception $e) {
                // Fallback to base selling price
                $finalUnitPrice = $unitPrice;
                $itemTotal = $order->quantity * $unitPrice;
                \Log::warning('PricingService failed for drug, using base price', [
                    'drug_id' => $order->drug_id,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Calculate insurance coverage if applicable
            $coveredAmount = 0;
            $patientAmount = $itemTotal;
            
            if ($request->billing_type === 'insurance' && $insurancePolicy) {
                $drugCoverage = $this->calculateDrugCoverage($order->drug, $insurancePolicy);
                $coveredAmount = $itemTotal * ($drugCoverage / 100);
                $patientAmount = $itemTotal - $coveredAmount;
                
                $insuranceCoverage += $coveredAmount;
                $patientCoPay += $patientAmount;
            } else {
                $patientCoPay += $itemTotal;
            }
            
            // Add to invoice items array (JSON field)
            $invoiceItems[] = [
                'id' => 'item_' . uniqid(),
                'item_type' => 'drug',
                'item_id' => $order->drug_id,
                'description' => $order->drug->name . ' - ' . $order->dosage_instructions,
                'quantity' => $order->quantity,
                'unit_price' => $finalUnitPrice,
                'total' => $itemTotal,
                'service_type' => 'pharmacy',
                'insurance_covered' => $coveredAmount,
                'patient_amount' => $patientAmount,
                'coverage_percentage' => $request->billing_type === 'insurance' ? $drugCoverage ?? 0 : 0
            ];
            
            $subtotal += $itemTotal;
        }

        // Calculate final amounts
        $taxAmount = $patientCoPay * 0.15; // 15% tax rate on patient portion
        $totalAmount = $patientCoPay + $taxAmount;

        // Create invoice with all items in JSON field
        $invoice = Invoice::create([
            'patient_id' => $prescription->patient_id,
            'branch_id' => $branchId,
            'invoice_date' => now()->toDateString(),
            'due_date' => $request->billing_type === 'insurance' ? now()->addDays(60)->toDateString() : now()->addDays(30)->toDateString(),
            'items' => $invoiceItems, // Store as JSON
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
            'status' => $request->billing_type === 'insurance' ? 'pending' : 'pending',
            'payment_method' => $request->billing_type === 'insurance' ? 'insurance' : null,
            'notes' => 'Prescription #' . $prescription->prescription_number . ' - ' . $request->billing_type . ' billing',
            'created_by' => auth()->id()
        ]);
        
        \Log::info('Pharmacy invoice created with dynamic pricing', [
            'prescription_id' => $prescription->id,
            'invoice_id' => $invoice->id,
            'total_amount' => $totalAmount,
            'insurance_coverage' => $insuranceCoverage,
            'patient_copay' => $patientCoPay
        ]);

        // Create insurance claim if applicable
        if ($request->billing_type === 'insurance' && $insurancePolicy) {
            $this->createInsuranceClaim($invoice, $insurancePolicy, $prescription, $insuranceCoverage, $patientCoPay);
        }

        // Create notification for billing
        \App\Models\PrescriptionNotification::create([
            'prescription_id' => $prescription->id,
            'patient_id' => $prescription->patient_id,
            'doctor_id' => $prescription->doctor_id,
            'notification_type' => 'billing_ready',
            'title' => 'Prescription Invoice Ready',
            'message' => $request->billing_type === 'insurance' 
                ? 'Your prescription invoice has been generated and submitted to your insurance provider.'
                : 'Your prescription invoice is ready for payment.',
            'priority' => 'medium',
            'metadata' => ['invoice_id' => $invoice->id, 'billing_type' => $request->billing_type],
            'status' => 'pending'
        ]);

        return redirect()->route('billing.show', $invoice)
            ->with('success', 'Invoice #' . $invoice->invoice_number . ' generated successfully for prescription!');
    }

    /**
     * Calculate drug coverage percentage for insurance policy
     */
    private function calculateDrugCoverage($drug, $insurancePolicy)
    {
        // Check if drug is covered by NHIS
        if ($drug->nhis_covered && $insurancePolicy->provider->name === 'NHIS') {
            return 100; // Full coverage for NHIS covered drugs
        }

        // Check if drug requires prescription
        if ($drug->requires_prescription) {
            return 80; // 80% coverage for prescription drugs
        }

        return 50; // 50% coverage for OTC drugs
    }

    /**
     * Create insurance claim for prescription
     */
    private function createInsuranceClaim($invoice, $insurancePolicy, $prescription, $insuranceCoverage, $patientCoPay)
    {
        InsuranceClaim::create([
            'patient_id' => $prescription->patient_id,
            'policy_id' => $insurancePolicy->id,
            'invoice_id' => $invoice->id,
            'claim_number' => 'CLM-' . strtoupper(uniqid()),
            'total_amount' => $invoice->subtotal,
            'covered_amount' => $insuranceCoverage,
            'co_pay_amount' => $patientCoPay,
            'status' => 'submitted',
            'submitted_date' => now(),
            'notes' => 'Prescription claim for ' . $prescription->prescription_number,
            'created_by' => auth()->id()
        ]);
    }

    /**
     * Check drug interactions for a prescription
     */
    public function checkDrugInteractions(Prescription $prescription)
    {
        $drugIds = $prescription->orders->pluck('drug_id')->toArray();
        
        $interactions = \App\Models\DrugInteraction::checkMultipleDrugs($drugIds);
        
        return response()->json([
            'success' => true,
            'interactions' => $interactions,
            'drug_count' => count($drugIds)
        ]);
    }

    /**
     * Get stock alerts and notifications
     */
    public function getStockAlerts()
    {
        $branchId = $this->userBranchId();
        $alertService = app(PharmacyInventoryAlertService::class);

        $alerts = [
            'out_of_stock' => $alertService->checkOutOfStock($branchId),
            'low_stock' => $alertService->checkLowStock($branchId)->filter(fn ($s) => $s->current_stock > 0)->values(),
            'expiring_soon' => $alertService->checkExpiringSoon($branchId),
            'expired' => $alertService->checkExpired($branchId),
        ];

        return response()->json([
            'success' => true,
            'branch_id' => $branchId,
            'alerts' => $alerts,
            'total_alerts' => collect($alerts)->sum(fn($alert) => $alert->count())
        ]);
    }

    /**
     * Create prescription notification
     */
    private function createPrescriptionNotification($prescription, $type, $title, $message, $priority = 'medium', $metadata = [])
    {
        \App\Models\PrescriptionNotification::create([
            'prescription_id' => $prescription->id,
            'patient_id' => $prescription->patient_id,
            'doctor_id' => $prescription->doctor_id,
            'pharmacist_id' => auth()->id(),
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'metadata' => $metadata,
            'status' => 'pending'
        ]);
    }

    /**
     * Get prescription notifications for a patient
     */
    public function getPatientNotifications(Request $request, $patientId)
    {
        $notifications = \App\Models\PrescriptionNotification::where('patient_id', $patientId)
            ->with(['prescription', 'doctor', 'pharmacist'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pharmacy.notifications.index', compact('notifications'));
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(\App\Models\PrescriptionNotification $notification)
    {
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    protected function userBranchId(): int
    {
        return $this->resolveUserBranchId(['view_drugs', 'manage_pharmacy_inventory', 'dispense_drugs']);
    }

    protected function assertPrescriptionAccess(Prescription $prescription): void
    {
        if ($prescription->branch_id) {
            $this->assertResourceInUserBranch(
                (int) $prescription->branch_id,
                ['view_drugs', 'manage_pharmacy_inventory', 'dispense_drugs', 'view_prescriptions']
            );
        } else {
            $this->resolveUserBranchId(['view_drugs', 'manage_pharmacy_inventory', 'dispense_drugs']);
        }
    }

    protected function resolvePrescriptionVisit(Prescription $prescription): ?Visit
    {
        $prescription->loadMissing(['consultation.visit', 'patient']);

        $visit = $prescription->consultation?->visit;
        if (!$visit) {
            $visit = Visit::where('patient_id', $prescription->patient_id)
                ->where('status', 'active')
                ->whereIn('visit_type', ['OPD', 'PharmacyOnly', 'Emergency'])
                ->latest('id')
                ->first();
        }

        return $visit;
    }

    protected function getPrescriptionPaymentContext(Prescription $prescription): array
    {
        $visit = $this->resolvePrescriptionVisit($prescription);
        $paymentPolicy = app(PaymentPolicyService::class);
        $paymentSummary = $paymentPolicy->getPaymentStatusSummary(
            (int) $prescription->patient_id,
            (int) $prescription->branch_id,
            $visit
        );

        $paymentSummary['cashier_url'] = url('/cashier') . '?patient_id=' . $prescription->patient_id;

        $chargeBreakdown = [];
        if ($paymentSummary['payment_required'] ?? false) {
            $pendingCharges = app(PendingChargesService::class)->getPatientPendingCharges(
                (int) $prescription->patient_id,
                (int) $prescription->branch_id
            );
            $chargeBreakdown = $this->summarizePendingCharges($pendingCharges);
        }

        return compact('paymentSummary', 'chargeBreakdown');
    }

    protected function summarizePendingCharges(array $charges): array
    {
        $groups = [
            'consultation' => ['label' => 'Consultation', 'count' => 0, 'amount' => 0.0],
            'lab' => ['label' => 'Lab Tests', 'count' => 0, 'amount' => 0.0],
            'prescription' => ['label' => 'Prescriptions', 'count' => 0, 'amount' => 0.0],
            'radiology' => ['label' => 'Radiology', 'count' => 0, 'amount' => 0.0],
            'appointment' => ['label' => 'Appointments', 'count' => 0, 'amount' => 0.0],
            'invoice' => ['label' => 'Invoices', 'count' => 0, 'amount' => 0.0],
            'other' => ['label' => 'Other', 'count' => 0, 'amount' => 0.0],
        ];

        foreach ($charges as $charge) {
            $type = $charge['type'] ?? 'other';
            $key = array_key_exists($type, $groups) ? $type : 'other';
            $groups[$key]['count']++;
            $groups[$key]['amount'] += (float) ($charge['amount'] ?? 0);
        }

        return array_values(array_filter($groups, fn ($group) => $group['count'] > 0));
    }

    protected function handlePaymentGateException(
        PaymentGateException $e,
        Prescription $prescription,
        Request $request
    ) {
        $payload = array_merge(['success' => false], $e->toArray());
        $payload['cashier_url'] = url('/cashier') . '?patient_id=' . $e->getPatientId();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($payload, 402);
        }

        return redirect()
            ->route('pharmacy.prescriptions.show', ['prescription' => $prescription, 'dispense' => 'true'])
            ->with('error', $e->getMessage())
            ->with('payment_required', true)
            ->with('amount_due', $e->getAmountDue())
            ->with('cashier_url', $payload['cashier_url']);
    }

    protected function assertCanDispense(Prescription $prescription): void
    {
        $visit = $this->resolvePrescriptionVisit($prescription);

        if ($visit) {
            app(PaymentPolicyService::class)->assertCanProceedWithService(
                $visit,
                $prescription->patient_id,
                $prescription->branch_id
            );
        }
    }

    protected function updatePrescriptionStatusAfterDispense(Prescription $prescription): void
    {
        $remainingOrders = DrugOrder::where('prescription_id', $prescription->id)
            ->where('status', '!=', 'dispensed')
            ->count();

        if ($remainingOrders === 0) {
            $prescription->update(['status' => 'completed']);
        } elseif (!in_array($prescription->status, ['completed', 'cancelled'], true)) {
            $prescription->update(['status' => 'dispensed']);
        }
    }

    public function export(Request $request)
    {
        $query = Drug::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('drug_code', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'low' => $query->whereHas('stocks', fn ($q) => $q->whereColumn('current_stock', '<=', 'reorder_level')),
                'out' => $query->whereHas('stocks', fn ($q) => $q->where('current_stock', 0)),
                'in_stock' => $query->whereHas('stocks', fn ($q) => $q->where('current_stock', '>', 0)),
                default => null,
            };
        }
        if ($request->filled('prescription_required')) {
            $query->where('requires_prescription', $request->prescription_required);
        }
        if ($request->filled('nhis_covered')) {
            $query->where('nhis_covered', $request->nhis_covered);
        }

        $query->latest('id');

        return $this->exportFromQuery($request, $query, [
            'Drug Code' => 'drug_code',
            'Name' => 'name',
            'Generic Name' => 'generic_name',
            'Category' => 'category',
            'Dosage Form' => 'dosage_form',
            'Strength' => 'strength',
            'Selling Price' => 'selling_price',
            'NHIS Covered' => fn ($d) => $d->nhis_covered ? 'Yes' : 'No',
            'Active' => fn ($d) => $d->is_active ? 'Yes' : 'No',
        ], 'pharmacy-drugs', 'manage_pharmacy_inventory');
    }

    public function exportPrescriptions(Request $request)
    {
        $userBranchId = $this->userBranchId();
        $query = Prescription::with(['patient', 'doctor'])->where('branch_id', $userBranchId)->latest('id');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prescription_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($pq) => $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patient_number', 'like', "%{$search}%"))
                    ->orWhereHas('doctor', fn ($dq) => $dq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        return $this->exportFromQuery($request, $query, [
            'Prescription #' => 'prescription_number',
            'Patient' => fn ($p) => $p->patient?->full_name ?? '',
            'Patient Number' => fn ($p) => $p->patient?->patient_number ?? '',
            'Doctor' => fn ($p) => $this->formatExportUserName($p->doctor),
            'Status' => 'status',
            'Created At' => fn ($p) => $this->formatExportDate($p->created_at, 'Y-m-d H:i'),
        ], 'pharmacy-prescriptions', 'dispense_drugs');
    }

    public function exportDispensing(Request $request)
    {
        $userBranchId = $this->userBranchId();
        $query = Prescription::with(['patient', 'doctor'])
            ->where('branch_id', $userBranchId)
            ->whereIn('status', ['pending', 'active'])
            ->latest('id');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prescription_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($pq) => $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patient_number', 'like', "%{$search}%"));
            });
        }

        return $this->exportFromQuery($request, $query, [
            'Prescription #' => 'prescription_number',
            'Patient' => fn ($p) => $p->patient?->full_name ?? '',
            'Patient Number' => fn ($p) => $p->patient?->patient_number ?? '',
            'Doctor' => fn ($p) => $this->formatExportUserName($p->doctor),
            'Status' => 'status',
            'Created At' => fn ($p) => $this->formatExportDate($p->created_at, 'Y-m-d H:i'),
        ], 'pharmacy-dispensing', 'dispense_drugs');
    }

    public function exportStock(Request $request)
    {
        $userBranchId = $this->userBranchId();
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : $userBranchId;
        $alertService = app(PharmacyInventoryAlertService::class);
        $expiryDays = $alertService->expiryWarningDays();

        $query = DrugStock::with(['drug', 'branch'])
            ->where('is_active', true)
            ->whereHas('drug')
            ->where('branch_id', $branchId);

        if ($request->has('status') && $request->status !== 'all') {
            match ($request->status) {
                'low_stock' => $query->whereColumn('current_stock', '<=', 'reorder_level'),
                'out_of_stock' => $query->where('current_stock', 0),
                'expiring_soon' => $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '>', now())
                    ->where('expiry_date', '<=', now()->addDays($expiryDays)),
                'expired' => $query->whereNotNull('expiry_date')->where('expiry_date', '<', now()),
                default => null,
            };
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('drug', fn ($dq) => $dq->where('name', 'like', "%{$search}%")
                ->orWhere('generic_name', 'like', "%{$search}%")
                ->orWhere('drug_code', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%"));
        }

        return $this->exportFromQuery($request, $query, [
            'Drug' => fn ($s) => $s->drug?->name ?? '',
            'Drug Code' => fn ($s) => $s->drug?->drug_code ?? '',
            'Branch' => fn ($s) => $s->branch?->name ?? '',
            'Current Stock' => 'current_stock',
            'Reorder Level' => 'reorder_level',
            'Cost Price' => 'cost_price',
            'Expiry Date' => fn ($s) => $this->formatExportDate($s->expiry_date),
        ], 'pharmacy-stock', 'manage_pharmacy_inventory');
    }

    public function exportHistory(Request $request)
    {
        $query = DrugOrder::with(['drug', 'prescription.patient', 'prescription.doctor', 'dispenser'])
            ->where('status', 'dispensed')
            ->latest('dispensed_at');

        if ($request->has('date_from')) {
            $query->whereDate('dispensed_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('dispensed_at', '<=', $request->date_to);
        }
        if ($request->has('patient_id')) {
            $query->whereHas('prescription', fn ($q) => $q->where('patient_id', $request->patient_id));
        }

        return $this->exportFromQuery($request, $query, [
            'Drug' => fn ($o) => $o->drug?->name ?? '',
            'Patient' => fn ($o) => $o->prescription?->patient?->full_name ?? '',
            'Doctor' => fn ($o) => $this->formatExportUserName($o->prescription?->doctor),
            'Quantity Dispensed' => 'quantity_dispensed',
            'Dispensed At' => fn ($o) => $this->formatExportDate($o->dispensed_at, 'Y-m-d H:i'),
            'Dispensed By' => fn ($o) => $this->formatExportUserName($o->dispenser),
        ], 'pharmacy-dispensing-history', 'dispense_drugs');
    }
}
