<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Exceptions\PaymentGateException;
use App\Models\Drug;
use App\Models\DrugStock;
use App\Models\Prescription;
use App\Models\DrugOrder;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\Visit;
use App\Models\Queue;
use App\Services\PaymentPolicyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PharmacyController extends Controller
{
    use ResolvesUserBranch, WorkflowNavigation;
    /**
     * Display a listing of drugs.
     */
    public function index(Request $request)
    {
        $query = Drug::with(['stocks'])
            ->orderBy('id', 'desc');

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by availability
        if ($request->has('available')) {
            $query->whereHas('stocks', function($q) {
                $q->where('current_stock', '>', 0);
            });
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%")
                  ->orWhere('drug_code', 'like', "%{$search}%");
            });
        }

        $drugs = $query->paginate($request->get('per_page', 20));

        // Transform the data to include stock information
        $transformedDrugs = $drugs->getCollection()->map(function ($drug) {
            $stock = $drug->stocks->first();
            return [
                'id' => $drug->id,
                'name' => $drug->name,
                'generic_name' => $drug->generic_name,
                'drug_code' => $drug->drug_code,
                'category' => $drug->category,
                'dosage_form' => $drug->dosage_form,
                'strength' => $drug->strength,
                'unit' => $drug->unit,
                'manufacturer' => $drug->manufacturer,
                'description' => $drug->description,
                'indications' => $drug->indications,
                'contraindications' => $drug->contraindications,
                'side_effects' => $drug->side_effects,
                'dosage_instructions' => $drug->dosage_instructions,
                'storage_conditions' => $drug->storage_conditions,
                'prescription_required' => $drug->requires_prescription,
                'controlled_substance' => $drug->controlled_substance,
                'nhis_covered' => $drug->nhis_covered,
                'cost_price' => round((float) $drug->cost_price, 2),
                'selling_price' => round((float) $drug->selling_price, 2),
                'unit_price' => round((float) $drug->selling_price, 2), // Map selling_price to unit_price for frontend
                'nhis_price' => round((float) $drug->nhis_price, 2),
                'stock_level' => $stock ? (int) $stock->current_stock : 0,
                'reorder_level' => $stock ? (int) $stock->reorder_level : 0,
                'expiry_date' => $stock ? $stock->expiry_date : now()->addYear()->format('Y-m-d'),
                'is_active' => $drug->is_active,
                'created_at' => $drug->created_at,
                'updated_at' => $drug->updated_at
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedDrugs,
            'meta' => [
                'current_page' => $drugs->currentPage(),
                'last_page' => $drugs->lastPage(),
                'per_page' => $drugs->perPage(),
                'total' => $drugs->total()
            ],
            'message' => 'Drugs retrieved successfully'
        ]);
    }

    /**
     * Store a newly created drug.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'generic_name' => 'required|string|max:255',
            'drug_code' => 'required|string|unique:drugs,drug_code',
            'category' => 'required|string',
            'dosage_form' => 'required|string',
            'strength' => 'required|string',
            'unit' => 'required|string',
            'manufacturer' => 'required|string',
            'description' => 'nullable|string',
            'indications' => 'nullable|string',
            'contraindications' => 'nullable|string',
            'side_effects' => 'nullable|string',
            'dosage_instructions' => 'nullable|string',
            'storage_conditions' => 'nullable|string',
            'prescription_required' => 'boolean',
            'controlled_substance' => 'boolean',
            'nhis_covered' => 'boolean',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'nhis_price' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        if (isset($data['prescription_required'])) {
            $data['requires_prescription'] = $data['prescription_required'];
            unset($data['prescription_required']);
        }
        $drug = Drug::create($data);

        return response()->json([
            'success' => true,
            'data' => $drug,
            'message' => 'Drug created successfully'
        ], 201);
    }

    /**
     * Display the specified drug.
     */
    public function show($id)
    {
        $drug = Drug::with(['stocks', 'prescriptions', 'orders'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $drug,
            'message' => 'Drug retrieved successfully'
        ]);
    }

    /**
     * Update the specified drug.
     */
    public function update(Request $request, $id)
    {
        $drug = Drug::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'generic_name' => 'sometimes|string|max:255',
            'drug_code' => 'sometimes|string|unique:drugs,drug_code,' . $id,
            'category' => 'sometimes|string',
            'dosage_form' => 'sometimes|string',
            'strength' => 'sometimes|string',
            'unit' => 'sometimes|string',
            'manufacturer' => 'sometimes|string',
            'description' => 'nullable|string',
            'indications' => 'nullable|string',
            'contraindications' => 'nullable|string',
            'side_effects' => 'nullable|string',
            'dosage_instructions' => 'nullable|string',
            'storage_conditions' => 'nullable|string',
            'prescription_required' => 'sometimes|boolean',
            'controlled_substance' => 'sometimes|boolean',
            'nhis_covered' => 'sometimes|boolean',
            'cost_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'nhis_price' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        if (isset($data['prescription_required'])) {
            $data['requires_prescription'] = $data['prescription_required'];
            unset($data['prescription_required']);
        }
        $drug->update($data);

        return response()->json([
            'success' => true,
            'data' => $drug,
            'message' => 'Drug updated successfully'
        ]);
    }

    /**
     * Get drug stock levels.
     */
    public function stock(Request $request)
    {
        if (auth()->user()->hasRole('doctor') && !auth()->user()->can('manage_pharmacy_inventory')) {
            return response()->json([
                'success' => false,
                'message' => 'Pharmacy stock management is not available for doctors.',
            ], 403);
        }

        $query = DrugStock::with(['drug', 'branch'])
            ->where('current_stock', '>', 0);

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by low stock
        if ($request->has('low_stock')) {
            $query->whereRaw('current_stock <= minimum_stock');
        }

        // Filter by expiring soon
        if ($request->has('expiring_soon')) {
            $query->where('expiry_date', '<=', now()->addDays(30));
        }

        $stocks = $query->orderBy('current_stock', 'asc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $stocks,
            'message' => 'Drug stock retrieved successfully'
        ]);
    }

    /**
     * Update drug stock.
     */
    public function updateStock(Request $request, $drugId)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'current_stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'expiry_date' => 'required|date|after:today',
            'batch_number' => 'nullable|string',
            'supplier' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $stock = DrugStock::updateOrCreate(
            [
                'drug_id' => $drugId,
                'branch_id' => $request->branch_id
            ],
            $request->only([
                'current_stock', 'minimum_stock', 'expiry_date', 
                'batch_number', 'supplier'
            ])
        );

        return response()->json([
            'success' => true,
            'data' => $stock,
            'message' => 'Drug stock updated successfully'
        ]);
    }

    /**
     * Create a prescription.
     */
    public function createPrescription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'prescription_date' => 'required|date',
            'medications' => 'required|array|min:1',
            'medications.*.drug_id' => 'required|exists:drugs,id',
            'medications.*.quantity' => 'required|integer|min:1',
            'medications.*.dosage_instructions' => 'required|string',
            'medications.*.frequency' => 'required|string',
            'medications.*.duration' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create prescription
            $prescription = Prescription::create([
                'patient_id' => $request->patient_id,
                'consultation_id' => $request->consultation_id,
                'doctor_id' => $request->doctor_id,
                'branch_id' => $request->branch_id,
                'prescription_date' => $request->prescription_date,
                'status' => 'active',
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            // Create drug orders for each medication
            foreach ($request->medications as $medication) {
                DrugOrder::create([
                    'prescription_id' => $prescription->id,
                    'drug_id' => $medication['drug_id'],
                    'quantity' => $medication['quantity'],
                    'dosage_instructions' => $medication['dosage_instructions'],
                    'instructions' => $medication['dosage_instructions'], // Required field
                    'frequency' => $medication['frequency'],
                    'duration' => $medication['duration'],
                    'status' => 'pending'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $prescription->load(['patient', 'doctor', 'orders.drug']),
                'message' => 'Prescription created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating prescription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prescriptions.
     */
    public function getPrescriptions(Request $request)
    {
        $query = Prescription::with(['patient', 'doctor', 'orders.drug'])
            ->orderBy('prescription_date', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by doctor
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('prescription_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('prescription_date', '<=', $request->date_to);
        }

        $prescriptions = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $prescriptions,
            'message' => 'Prescriptions retrieved successfully'
        ]);
    }

    /**
     * Dispense medication.
     */
    public function dispenseMedication(Request $request, $orderId)
    {
        $order = DrugOrder::with(['drug', 'prescription.consultation.visit'])->findOrFail($orderId);
        $prescription = $order->prescription;

        $validator = Validator::make($request->all(), [
            'quantity_dispensed' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $qtyToDispense = (int) $request->quantity_dispensed;
        if ($qtyToDispense > $order->getRemainingQuantity()) {
            return response()->json([
                'success' => false,
                'message' => "Cannot dispense more than remaining quantity for {$order->drug->name}",
            ], 422);
        }

        if ($prescription?->branch_id) {
            $this->assertResourceInUserBranch(
                (int) $prescription->branch_id,
                ['dispense_drugs', 'manage_pharmacy_inventory']
            );
        }

        try {
            $this->assertCanDispensePrescription($prescription);
        } catch (PaymentGateException $e) {
            return response()->json(array_merge(['success' => false], $e->toArray()), 402);
        }

        DB::beginTransaction();

        try {
            $stock = DrugStock::where('drug_id', $order->drug_id)
                ->where('branch_id', $prescription->branch_id)
                ->first();

            if ($stock && $stock->current_stock < $qtyToDispense) {
                throw new \Exception("Insufficient stock for {$order->drug->name}");
            }

            $newQtyDispensed = $order->quantity_dispensed + $qtyToDispense;
            $order->update([
                'quantity_dispensed' => $newQtyDispensed,
                'status' => $newQtyDispensed >= $order->quantity ? 'dispensed' : 'pending',
                'dispensed_by' => auth()->id(),
                'dispensed_at' => now(),
                'notes' => $request->notes ?? $order->notes,
            ]);

            if ($stock) {
                $stock->decrement('current_stock', $qtyToDispense);
            }

            $remainingOrders = DrugOrder::where('prescription_id', $prescription->id)
                ->where('status', '!=', 'dispensed')
                ->count();

            if ($remainingOrders === 0) {
                $prescription->update(['status' => 'completed']);
                if ($prescription->workflowInstance) {
                    $this->completeWorkflowStep($prescription, 'pharmacy_dispensing', [
                        'dispensed_orders' => $prescription->orders()->where('status', 'dispensed')->count(),
                    ]);
                }
            } elseif (!in_array($prescription->status, ['completed', 'cancelled'], true)) {
                $prescription->update(['status' => 'dispensed']);
            }

            DB::commit();

            $response = [
                'success' => true,
                'data' => $order->load(['drug', 'prescription.patient']),
                'message' => 'Medication dispensed successfully',
            ];

            if ($prescription->workflowInstance && $remainingOrders === 0) {
                $workflowResponse = $this->getNextStepResponse($prescription, 'Medication dispensed successfully');
                $response['workflow'] = $workflowResponse->getData(true)['workflow'] ?? null;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error dispensing medication: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pharmacy statistics.
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_drugs' => Drug::count(),
            'low_stock_drugs' => DrugStock::whereRaw('current_stock <= minimum_stock')->count(),
            'expiring_drugs' => DrugStock::where('expiry_date', '<=', now()->addDays(30))->count(),
            'total_prescriptions' => Prescription::whereBetween('prescription_date', [$dateFrom, $dateTo])->count(),
            'active_prescriptions' => Prescription::where('status', 'active')->count(),
            'completed_prescriptions' => Prescription::where('status', 'completed')->count(),
            'pending_orders' => DrugOrder::where('status', 'pending')->count(),
            'dispensed_orders' => DrugOrder::where('status', 'dispensed')->count(),
            'total_inventory_value' => $this->calculateInventoryValue(),
            'top_drugs' => $this->getTopDrugs($dateFrom, $dateTo)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Pharmacy statistics retrieved successfully'
        ]);
    }

    /**
     * Calculate total inventory value.
     */
    private function calculateInventoryValue()
    {
        return DrugStock::join('drugs', 'drug_stocks.drug_id', '=', 'drugs.id')
            ->selectRaw('SUM(drug_stocks.current_stock * drugs.cost_price) as total_value')
            ->value('total_value') ?? 0;
    }

    /**
     * Get top prescribed drugs.
     */
    private function getTopDrugs($dateFrom, $dateTo)
    {
        return DrugOrder::whereHas('prescription', function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('prescription_date', [$dateFrom, $dateTo]);
            })
            ->join('drugs', 'drug_orders.drug_id', '=', 'drugs.id')
            ->selectRaw('drugs.name, drugs.generic_name, COUNT(*) as prescription_count, SUM(drug_orders.quantity) as total_quantity')
            ->groupBy('drugs.id', 'drugs.name', 'drugs.generic_name')
            ->orderBy('prescription_count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get drug categories.
     */
    public function getCategories()
    {
        $categories = Drug::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories->values(),
            'message' => 'Drug categories retrieved successfully'
        ]);
    }

    /**
     * Search drugs by name or code.
     */
    public function searchDrugs(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query || strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Search query must be at least 2 characters'
            ], 400);
        }

        $drugs = Drug::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('generic_name', 'like', "%{$query}%")
                  ->orWhere('drug_code', 'like', "%{$query}%");
            })
            ->with(['stocks' => function($query) {
                $query->where('current_stock', '>', 0);
            }])
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $drugs,
            'message' => 'Drug search completed successfully'
        ]);
    }

    /**
     * Get pending prescriptions for dispensing.
     */
    public function getPendingPrescriptions(Request $request)
    {
        $query = Prescription::with(['patient', 'doctor', 'orders.drug'])
            ->where('status', 'active')
            ->whereHas('orders', function($q) {
                $q->where('status', 'pending');
            })
            ->orderBy('prescription_date', 'desc');

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('prescription_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('prescription_date', '<=', $request->date_to);
        }

        $prescriptions = $query->paginate(20);

        // Transform the prescription data to include unit_price for frontend compatibility
        $prescriptions->getCollection()->each(function($prescription) {
            $prescription->orders->each(function($order) {
                $order->drug->unit_price = $order->drug->selling_price;
            });
        });

        return response()->json([
            'data' => [
                'data' => $prescriptions->items(),
                'meta' => [
                    'current_page' => $prescriptions->currentPage(),
                    'last_page' => $prescriptions->lastPage(),
                    'per_page' => $prescriptions->perPage(),
                    'total' => $prescriptions->total()
                ]
            ]
        ]);
    }

    /**
     * Get prescription details for dispensing.
     */
    public function getPrescriptionDetails($id)
    {
        $prescription = Prescription::with([
            'patient', 
            'doctor', 
            'consultation',
            'orders.drug.stocks' => function($query) {
                $query->where('current_stock', '>', 0);
            }
        ])->findOrFail($id);

        // Transform the prescription data to include unit_price for frontend compatibility
        $prescription->orders->each(function($order) {
            $order->drug->unit_price = $order->drug->selling_price;
        });

        return response()->json([
            'success' => true,
            'data' => $prescription,
            'message' => 'Prescription details retrieved successfully'
        ]);
    }

    /**
     * Add prescription items to POS cart.
     */
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prescription_id' => 'required|exists:prescriptions,id',
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:drug_orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $prescription = Prescription::with(['orders.drug'])->findOrFail($request->prescription_id);
        $cartItems = [];

        foreach ($request->order_ids as $orderId) {
            $order = $prescription->orders()->where('id', $orderId)->first();
            if ($order && $order->status === 'pending') {
                $cartItems[] = [
                    'order_id' => $order->id,
                    'drug_id' => $order->drug_id,
                    'drug_name' => $order->drug->name,
                    'generic_name' => $order->drug->generic_name,
                    'strength' => $order->drug->strength,
                    'dosage_form' => $order->drug->dosage_form,
                    'quantity' => $order->quantity,
                    'quantity_dispensed' => $order->quantity_dispensed,
                    'remaining_quantity' => $order->getRemainingQuantity(),
                    'dosage_instructions' => $order->dosage_instructions,
                    'frequency' => $order->frequency,
                    'duration' => $order->duration,
                    'unit_price' => $order->drug->selling_price ?? 0,
                    'total_price' => ($order->drug->selling_price ?? 0) * $order->quantity
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'prescription' => $prescription,
                'cart_items' => $cartItems
            ],
            'message' => 'Items added to cart successfully'
        ]);
    }

    /**
     * Process dispensing for multiple orders.
     */
    public function processDispensing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prescription_id' => 'required|exists:prescriptions,id',
            'orders' => 'required|array|min:1',
            'orders.*.order_id' => 'required|exists:drug_orders,id',
            'orders.*.quantity_dispensed' => 'required|integer|min:1',
            'orders.*.notes' => 'nullable|string',
            'payment_method' => 'required|in:cash,card,momo,insurance',
            'total_amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $prescription = Prescription::findOrFail($request->prescription_id);
            $this->assertCanDispensePrescription($prescription);
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

            // Check if all orders in prescription are dispensed
            $remainingOrders = DrugOrder::where('prescription_id', $prescription->id)
                ->where('status', '!=', 'dispensed')
                ->count();

            if ($remainingOrders == 0) {
                $prescription->update(['status' => 'completed']);
            } elseif (!in_array($prescription->status, ['completed', 'cancelled'], true)) {
                $prescription->update(['status' => 'dispensed']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'prescription' => $prescription->load(['patient', 'doctor']),
                    'dispensed_orders' => $dispensedOrders
                ],
                'message' => 'Medications dispensed successfully'
            ]);

        } catch (PaymentGateException $e) {
            DB::rollback();
            return response()->json(array_merge(['success' => false], $e->toArray()), 402);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error processing dispensing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dispensing history.
     */
    public function getDispensingHistory(Request $request)
    {
        $query = DrugOrder::with(['drug', 'prescription.patient', 'prescription.doctor', 'dispenser'])
            ->where('status', 'dispensed')
            ->orderBy('dispensed_at', 'desc');

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

        return response()->json([
            'success' => true,
            'data' => $orders,
            'message' => 'Dispensing history retrieved successfully'
        ]);
    }

    /**
     * Get pharmacy orders (combines prescription orders and store orders).
     */
    public function getPharmacyOrders(Request $request)
    {
        $query = DrugOrder::with([
            'prescription.patient', 
            'prescription.doctor',
            'drug',
            'creator'
        ]);

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('prescription.patient', function($patientQuery) use ($search) {
                    $patientQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('other_names', 'like', "%{$search}%");
                })
                ->orWhereHas('drug', function($drugQuery) use ($search) {
                    $drugQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 10));

        // Transform data for frontend
        $transformedOrders = $orders->getCollection()->map(function($order) {
            return [
                'id' => $order->id,
                'order_number' => 'PH-ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'patient_id' => $order->prescription->patient_id,
                'patient_name' => $this->formatPrescriptionPatientName($order->prescription),
                'prescription_id' => $order->prescription_id,
                'order_type' => 'prescription',
                'status' => $order->status,
                'total_amount' => $order->drug->selling_price * $order->quantity,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'items' => [
                    [
                        'id' => $order->id,
                        'drug_id' => $order->drug_id,
                        'drug_name' => $order->drug->name,
                        'quantity' => $order->quantity,
                        'unit_price' => $order->drug->selling_price,
                        'total_price' => $order->drug->selling_price * $order->quantity,
                        'dosage_instructions' => $order->dosage_instructions
                    ]
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedOrders,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total()
            ]
        ]);
    }

    /**
     * Get pharmacy order details.
     */
    public function getPharmacyOrderDetails($id)
    {
        $order = DrugOrder::with([
            'prescription.patient',
            'prescription.doctor',
            'prescription.consultation',
            'drug',
            'creator',
            'dispenser'
        ])->findOrFail($id);

        $transformedOrder = [
            'id' => $order->id,
            'order_number' => 'PH-ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'patient_id' => $order->prescription->patient_id,
            'patient_name' => $this->formatPrescriptionPatientName($order->prescription),
            'patient_contact' => $order->prescription->patient?->contact,
            'prescription_id' => $order->prescription_id,
            'order_type' => 'prescription',
            'status' => $order->status,
            'total_amount' => $order->drug->selling_price * $order->quantity,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'dispensed_at' => $order->dispensed_at,
            'notes' => $order->notes,
            'prescription' => [
                'id' => $order->prescription->id,
                'prescription_date' => $order->prescription->prescription_date,
                'doctor_name' => $order->prescription->doctor->first_name . ' ' . $order->prescription->doctor->last_name,
                'consultation_id' => $order->prescription->consultation_id
            ],
            'items' => [
                [
                    'id' => $order->id,
                    'drug_id' => $order->drug_id,
                    'drug_name' => $order->drug->name,
                    'drug_description' => $order->drug->description,
                    'quantity' => $order->quantity,
                    'quantity_dispensed' => $order->quantity_dispensed,
                    'unit_price' => $order->drug->selling_price,
                    'total_price' => $order->drug->selling_price * $order->quantity,
                    'dosage_instructions' => $order->dosage_instructions,
                    'frequency' => $order->frequency,
                    'duration' => $order->duration
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $transformedOrder
        ]);
    }

    /**
     * Update pharmacy order status.
     */
    public function updatePharmacyOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,ready,dispensed,cancelled',
            'notes' => 'nullable|string',
            'branch_id' => 'nullable|integer'
        ]);

        $order = DrugOrder::findOrFail($id);
        $oldStatus = $order->status;
        
        // Update status with proper validation
        $order->status = trim($request->status);

        // Handle specific status updates
        if ($request->status === 'dispensed') {
            $order->dispensed_by = auth()->id();
            $order->dispensed_at = now();
            $order->quantity_dispensed = $order->quantity;
        }

        // Update notes if provided
        if ($request->has('notes') && !empty($request->notes)) {
            $order->notes = $request->notes;
        }

        $order->save();

        // Load relationships for response
        $order->load(['drug', 'prescription.patient', 'prescription.doctor']);

        return response()->json([
            'success' => true,
            'message' => "Order status updated from {$oldStatus} to {$request->status}",
            'data' => $order
        ]);
    }

    /**
     * Create prescription from visit and add to pharmacy queue.
     */
    public function createFromVisit(Request $request, $visitId)
    {
        $visit = Visit::with(['patient', 'queues'])->findOrFail($visitId);

        if ($visit->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Visit is not active'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'consultation_id' => 'required|exists:consultations,id',
            'drugs' => 'required|array|min:1',
            'drugs.*.drug_id' => 'required|exists:drugs,id',
            'drugs.*.quantity' => 'required|integer|min:1',
            'drugs.*.dosage' => 'required|string',
            'drugs.*.frequency' => 'required|string',
            'drugs.*.duration' => 'required|string',
            'drugs.*.instructions' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create prescription
            $prescription = Prescription::create([
                'patient_id' => $visit->patient_id,
                'consultation_id' => $request->consultation_id,
                'branch_id' => $visit->branch_id,
                'status' => 'pending',
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            // Create drug orders
            foreach ($request->drugs as $drugData) {
                DrugOrder::create([
                    'prescription_id' => $prescription->id,
                    'drug_id' => $drugData['drug_id'],
                    'quantity' => $drugData['quantity'],
                    'dosage_instructions' => $drugData['dosage'] ?? '',
                    'instructions' => $drugData['instructions'] ?? $drugData['dosage'] ?? '', // Required field
                    'frequency' => $drugData['frequency'],
                    'duration' => $drugData['duration'],
                    'status' => 'pending'
                ]);
            }

            // Add to pharmacy queue if not already there
            $existingQueue = Queue::where('visit_id', $visit->id)
                                ->where('queue_type', 'Pharmacy')
                                ->where('status', '!=', 'completed')
                                ->first();

            if (!$existingQueue) {
                $lastPosition = Queue::where('queue_type', 'Pharmacy')
                                  ->where('branch_id', $visit->branch_id)
                                  ->where('status', '!=', 'cancelled')
                                  ->max('position') ?? 0;

                Queue::create([
                    'visit_id' => $visit->id,
                    'patient_id' => $visit->patient_id,
                    'branch_id' => $visit->branch_id,
                    'queue_type' => 'Pharmacy',
                    'position' => $lastPosition + 1,
                    'status' => 'waiting',
                    'queued_at' => now(),
                    'priority' => $visit->priority,
                    'estimated_wait_time' => $this->calculateEstimatedWaitTime('Pharmacy', $visit->branch_id)
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $prescription->load(['patient', 'drugOrders.drug', 'consultation']),
                'message' => 'Prescription created and added to pharmacy queue successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating prescription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pharmacy queue status.
     */
    public function getPharmacyQueueStatus(Request $request)
    {
        $branchId = $request->get('branch_id')
            ?? $this->resolveUserBranchId(['view_pharmacy_queue', 'manage_pharmacy_queue', 'view_queues']);

        $queues = Queue::with(['patient', 'visit'])
                      ->where('queue_type', 'Pharmacy')
                      ->where('branch_id', $branchId)
                      ->whereIn('status', ['waiting', 'called', 'serving'])
                      ->orderBy('priority', 'asc')
                      ->orderBy('position')
                      ->get();

        $stats = [
            'total_waiting' => $queues->where('status', 'waiting')->count(),
            'total_called' => $queues->where('status', 'called')->count(),
            'total_serving' => $queues->where('status', 'serving')->count(),
            'current_serving' => $queues->where('status', 'serving')->first(),
            'next_in_line' => $queues->where('status', 'waiting')->first(),
            'average_wait_time' => $this->calculateAverageWaitTime('Pharmacy', $branchId),
            'estimated_wait_time' => $this->calculateEstimatedWaitTime('Pharmacy', $branchId)
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'queues' => $queues,
                'stats' => $stats
            ],
            'message' => 'Pharmacy queue status retrieved successfully'
        ]);
    }

    /**
     * Call next patient in pharmacy queue.
     */
    public function callNextPharmacyPatient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'called_by' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Find next patient in pharmacy queue
            $nextQueue = Queue::where('queue_type', 'Pharmacy')
                            ->where('branch_id', $request->branch_id)
                            ->where('status', 'waiting')
                            ->orderBy('priority', 'asc')
                            ->orderBy('position')
                            ->first();

            if (!$nextQueue) {
                return response()->json([
                    'success' => false,
                    'message' => 'No patients waiting in pharmacy queue'
                ], 404);
            }

            // Update queue status
            $nextQueue->update([
                'status' => 'called',
                'called_at' => now(),
                'called_by' => $request->called_by
            ]);

            // Update all other queues to move them up
            Queue::where('queue_type', 'Pharmacy')
                ->where('branch_id', $request->branch_id)
                ->where('status', 'waiting')
                ->where('position', '>', $nextQueue->position)
                ->decrement('position');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $nextQueue->load(['patient', 'visit']),
                'message' => 'Pharmacy patient called successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error calling pharmacy patient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete pharmacy service and update queue.
     */
    public function completePharmacyService(Request $request, $visitId)
    {
        $visit = Visit::with(['queues'])->findOrFail($visitId);

        if ($visit->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Visit is not active'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update queue status to completed
            $visit->queues()->where('queue_type', 'Pharmacy')->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Complete visit if all queues are completed
            $activeQueues = $visit->queues()->whereIn('status', ['waiting', 'called', 'serving'])->count();
            if ($activeQueues == 0) {
                $visit->update([
                    'status' => 'completed',
                    'check_out_time' => now(),
                    'updated_by' => auth()->id()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $visit,
                'message' => 'Pharmacy service completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error completing pharmacy service: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate average wait time for pharmacy queue.
     */
    private function calculateAverageWaitTime(string $queueType, int $branchId): float
    {
        return Queue::where('queue_type', $queueType)
                   ->where('branch_id', $branchId)
                   ->whereNotNull('called_at')
                   ->whereNotNull('queued_at')
                   ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, queued_at, called_at)) as avg_wait')
                   ->value('avg_wait') ?? 0;
    }

    /**
     * Calculate estimated wait time for pharmacy queue.
     */
    private function calculateEstimatedWaitTime(string $queueType, int $branchId): int
    {
        $avgServiceTime = Queue::where('queue_type', $queueType)
                             ->where('branch_id', $branchId)
                             ->where('status', 'completed')
                             ->whereNotNull('serving_at')
                             ->whereNotNull('completed_at')
                             ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, serving_at, completed_at)) as avg_service')
                             ->value('avg_service') ?? 5; // Default 5 minutes for pharmacy

        $patientsAhead = Queue::where('queue_type', $queueType)
                             ->where('branch_id', $branchId)
                             ->where('status', 'waiting')
                             ->count();

        return $patientsAhead * $avgServiceTime;
    }

    /**
     * Create pharmacy visit from prescription.
     */
    public function createPharmacyVisit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'prescription_id' => 'required|exists:prescriptions,id',
            'chief_complaint' => 'nullable|string|max:1000',
            'priority' => 'nullable|in:routine,urgent,critical'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create pharmacy visit
            $visit = Visit::create([
                'patient_id' => $request->patient_id,
                'branch_id' => $request->branch_id,
                'visit_type' => 'PharmacyOnly',
                'chief_complaint' => $request->chief_complaint ?? 'Prescription dispensing',
                'priority' => $request->priority ?? 'routine',
                'check_in_time' => now(),
                'status' => 'active',
                'created_by' => auth()->id()
            ]);

            // Add to pharmacy queue
            $this->addToPharmacyQueue($visit);

            // Update prescription with visit reference
            $prescription = Prescription::findOrFail($request->prescription_id);
            $prescription->update([
                'visit_id' => $visit->id,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $visit->load(['patient', 'branch', 'queues']),
                'message' => 'Pharmacy visit created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating pharmacy visit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add visit to pharmacy queue.
     */
    private function addToPharmacyQueue(Visit $visit): void
    {
        // Get next position in pharmacy queue
        $lastPosition = Queue::where('queue_type', 'Pharmacy')
                            ->where('branch_id', $visit->branch_id)
                            ->where('status', '!=', 'cancelled')
                            ->max('position') ?? 0;

        Queue::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'branch_id' => $visit->branch_id,
            'queue_type' => 'Pharmacy',
            'position' => $lastPosition + 1,
            'status' => 'waiting',
            'queued_at' => now(),
            'priority' => $visit->priority,
            'estimated_wait_time' => $this->calculateEstimatedWaitTime('Pharmacy', $visit->branch_id)
        ]);
    }

    /**
     * Generate billing for prescription (API endpoint).
     */
    public function generatePrescriptionBilling(Request $request, $prescriptionId)
    {
        $validator = Validator::make($request->all(), [
            'billing_type' => 'required|in:cash,insurance',
            'insurance_policy_id' => 'required_if:billing_type,insurance|exists:insurance_policies,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $prescription = Prescription::with(['patient', 'orders.drug', 'branch'])->findOrFail($prescriptionId);

            // Check if patient has active insurance policy
            $insurancePolicy = null;
            if ($request->billing_type === 'insurance') {
                $insurancePolicy = \App\Models\InsurancePolicy::findOrFail($request->insurance_policy_id);
                
                // Validate policy is active and covers patient
                if ($insurancePolicy->patient_id !== $prescription->patient_id || !$insurancePolicy->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or inactive insurance policy'
                    ], 400);
                }
            }

            // Get patient's branch for pricing context
            $branchId = $prescription->branch_id ?? $prescription->patient->branch_id ?? 1;
            
            $pricingService = app(\App\Services\PricingService::class);
            $modulePricingService = app(\App\Services\ModulePricingService::class);

            $invoiceItems = [];
            $subtotal = 0;
            $insuranceCoverage = 0;
            $patientCoPay = 0;
            foreach ($prescription->orders as $order) {
                $drug = $order->drug;

                $pricingResult = $pricingService->calculateDrugPrice(
                    $drug->id,
                    $order->quantity,
                    $prescription->patient_id,
                    $branchId
                );

                $unitPrice = (float) ($pricingResult['final_price'] ?? $pricingResult['unit_price'] ?? $drug->selling_price) / max(1, $order->quantity);
                $itemTotal = (float) ($pricingResult['final_price'] ?? ($unitPrice * $order->quantity));
                $subtotal += $itemTotal;

                // Calculate insurance coverage if applicable
                if ($request->billing_type === 'insurance' && $insurancePolicy) {
                    $coverageService = app(\App\Services\InsuranceCoverageService::class);
                    $coverage = $coverageService->calculateCoverage(
                        $prescription->patient_id,
                        'pharmacy',
                        $drug->drug_code ?? null,
                        $itemTotal,
                        now()->toDateString()
                    );

                    $coveredAmount = $coverage['covered_amount'] ?? 0;
                    $coPayAmount = $coverage['co_pay_amount'] ?? $itemTotal;
                    
                    $insuranceCoverage += $coveredAmount;
                    $patientCoPay += $coPayAmount;
                } else {
                    $patientCoPay += $itemTotal;
                }

                $invoiceItems[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => $drug->name . ' (' . $drug->strength . ' ' . $drug->dosage_form . ')',
                    'quantity' => $order->quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal,
                    'service_type' => 'pharmacy',
                    'charge_component' => 'module_price',
                    'drug_id' => $drug->id,
                    'prescription_order_id' => $order->id,
                ];
            }

            $moduleFee = $modulePricingService->resolveModuleFee(
                'pharmacy',
                (int) $branchId,
                (int) $prescription->patient_id,
                \App\Services\ModulePricingService::APPLIES_ON_ORDER_CREATED
            );
            if ($moduleFee > 0) {
                $feeRecord = \App\Models\ServicePricing::where('branch_id', $branchId)
                    ->where('pricing_type', 'module_fee')
                    ->whereJsonContains('module_codes', 'pharmacy')
                    ->where('is_active', true)
                    ->first();

                $invoiceItems[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => $feeRecord?->service_name ?? 'Pharmacy Service Fee',
                    'quantity' => 1,
                    'unit_price' => $moduleFee,
                    'total' => $moduleFee,
                    'service_type' => 'pharmacy',
                    'charge_component' => 'admin_fee',
                    'prescription_id' => $prescription->id,
                ];
                $subtotal += $moduleFee;
                $patientCoPay += $moduleFee;
            }

            // Create invoice
            $invoice = \App\Models\Invoice::create([
                'patient_id' => $prescription->patient_id,
                'branch_id' => $branchId,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'items' => $invoiceItems,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $patientCoPay,
                'status' => 'pending',
                'payment_method' => $request->billing_type === 'insurance' ? 'insurance' : 'cash',
                'notes' => 'Prescription #' . $prescription->id . ' - ' . ($request->billing_type === 'insurance' ? 'Insurance Billing' : 'Cash Billing'),
                'created_by' => auth()->id()
            ]);

            // If insurance, create claim
            if ($request->billing_type === 'insurance' && $insurancePolicy && $insuranceCoverage > 0) {
                $claim = \App\Models\InsuranceClaim::create([
                    'patient_id' => $prescription->patient_id,
                    'insurance_provider_id' => $insurancePolicy->insurance_provider_id,
                    'policy_id' => $insurancePolicy->id,
                    'invoice_id' => $invoice->id,
                    'total_amount' => $subtotal,
                    'covered_amount' => $insuranceCoverage,
                    'co_pay_amount' => $patientCoPay,
                    'status' => 'submitted',
                    'submitted_date' => now()->toDateString(),
                    'notes' => 'Prescription billing claim for ' . $prescription->prescription_number,
                    'created_by' => auth()->id()
                ]);

                // Create claim items
                foreach ($invoiceItems as $item) {
                    if (isset($item['drug_id'])) {
                        \App\Models\ClaimItem::create([
                            'claim_id' => $claim->id,
                            'service_type' => 'pharmacy',
                            'description' => $item['description'],
                            'amount' => $item['total'],
                            'covered_amount' => ($item['total'] * ($insuranceCoverage / $subtotal)),
                            'co_pay_amount' => ($item['total'] * ($patientCoPay / $subtotal))
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice' => $invoice->load(['patient', 'branch']),
                    'prescription' => $prescription,
                    'billing_summary' => [
                        'subtotal' => $subtotal,
                        'insurance_covered' => $insuranceCoverage,
                        'patient_co_pay' => $patientCoPay,
                        'total_due' => $patientCoPay
                    ]
                ],
                'message' => 'Prescription billing generated successfully'
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error generating prescription billing: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating billing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prescription analytics and insights
     * Matches Web PharmacyController::analytics()
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $dateRange = $request->get('date_range', 30); // Default to last 30 days
            $startDate = now()->subDays($dateRange);
            $endDate = now();

            // Prescription trends
            $prescriptionTrends = Prescription::whereBetween('prescription_date', [$startDate, $endDate])
                ->selectRaw('DATE(prescription_date) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Top prescribed drugs
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

            // Doctor prescription patterns
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

            // Stock movement analysis
            $stockMovement = collect();
            
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

            // Financial analytics
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
                'total_prescription_value' => round($totalPrescriptionValue, 2),
                'average_prescription_value' => $prescriptions->count() > 0 ? round($totalPrescriptionValue / $prescriptions->count(), 2) : 0,
                'revenue_by_category' => []
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'prescription_trends' => $prescriptionTrends,
                    'top_drugs' => $topDrugs,
                    'status_distribution' => $statusDistribution,
                    'doctor_prescriptions' => $doctorPrescriptions,
                    'stock_movement' => $stockMovement,
                    'financial_data' => $financialData,
                    'date_range' => $dateRange
                ],
                'message' => 'Pharmacy analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Pharmacy Analytics Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check drug interactions for a prescription
     * Matches Web PharmacyController::checkDrugInteractions()
     */
    public function checkDrugInteractions(Request $request, $prescriptionId): JsonResponse
    {
        try {
            $prescription = Prescription::with('orders.drug')->findOrFail($prescriptionId);
            
            $drugIds = $prescription->orders->pluck('drug_id')->toArray();
            
            // Check for drug interactions
            $interactions = [];
            if (class_exists(\App\Models\DrugInteraction::class)) {
                $interactions = \App\Models\DrugInteraction::checkMultipleDrugs($drugIds);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'interactions' => $interactions,
                    'drug_count' => count($drugIds),
                    'has_interactions' => !empty($interactions)
                ],
                'message' => 'Drug interaction check completed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check drug interactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock alerts and notifications
     * Matches Web PharmacyController::getStockAlerts()
     */
    public function getStockAlerts(): JsonResponse
    {
        try {
            $branchId = $this->resolveUserBranchId(['view_drugs', 'manage_pharmacy_inventory', 'dispense_drugs']);
            $alertService = app(\App\Services\PharmacyInventoryAlertService::class);

            $alerts = [
                'out_of_stock' => $alertService->checkOutOfStock($branchId),
                'low_stock' => $alertService->checkLowStock($branchId)->filter(fn ($s) => $s->current_stock > 0)->values(),
                'expiring_soon' => $alertService->checkExpiringSoon($branchId),
                'expired' => $alertService->checkExpired($branchId),
            ];

            return response()->json([
                'success' => true,
                'branch_id' => $branchId,
                'data' => $alerts,
                'total_alerts' => collect($alerts)->sum(fn($alert) => $alert->count()),
                'message' => 'Stock alerts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stock alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prescription notifications for a patient
     * Matches Web PharmacyController::getPatientNotifications()
     */
    public function getPatientNotifications(Request $request, $patientId): JsonResponse
    {
        try {
            $notifications = \App\Models\PrescriptionNotification::where('patient_id', $patientId)
                ->with(['prescription', 'doctor', 'pharmacist'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $notifications->items(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total()
                ],
                'message' => 'Patient notifications retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get patient notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     * Matches Web PharmacyController::markNotificationAsRead()
     */
    public function markNotificationAsRead(Request $request, $notificationId): JsonResponse
    {
        try {
            $notification = \App\Models\PrescriptionNotification::findOrFail($notificationId);
            
            if (method_exists($notification, 'markAsRead')) {
                $notification->markAsRead();
            } else {
                $notification->update(['read_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function formatPrescriptionPatientName(?Prescription $prescription): ?string
    {
        $patient = $prescription?->patient;
        if (!$patient) {
            return null;
        }

        return trim(
            ($patient->first_name ?? '') . ' ' .
            ($patient->other_names ? $patient->other_names . ' ' : '') .
            ($patient->last_name ?? '')
        );
    }

    protected function assertCanDispensePrescription(?Prescription $prescription): void
    {
        if (!$prescription) {
            return;
        }

        $prescription->loadMissing(['consultation.visit', 'patient']);

        $visit = $prescription->consultation?->visit;
        if (!$visit) {
            $visit = Visit::where('patient_id', $prescription->patient_id)
                ->where('status', 'active')
                ->whereIn('visit_type', ['OPD', 'PharmacyOnly', 'Emergency'])
                ->latest('id')
                ->first();
        }

        if ($visit) {
            app(PaymentPolicyService::class)->assertCanProceedWithService(
                $visit,
                $prescription->patient_id,
                $prescription->branch_id
            );
        }
    }
}
