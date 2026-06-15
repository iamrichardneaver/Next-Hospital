<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PharmacySupplierController extends Controller
{
    /** @var list<string> */
    protected array $allowedTypes = ['pharmacy', 'both', 'general'];

    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query()->forPharmacy();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        } elseif ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $suppliers = $query->orderBy('name')->paginate(min((int) $request->get('per_page', 20), 50));

        return response()->json(['success' => true, 'data' => $suppliers]);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $this->assertSupplierEligible($supplier);

        return response()->json(['success' => true, 'data' => $supplier]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateSupplier($request);
        $validated['is_active'] = true;
        $validated['created_by'] = auth()->id();

        $supplier = Supplier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data' => $supplier,
        ], 201);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $this->assertSupplierEligible($supplier);

        $validated = $this->validateSupplier($request);
        $validated['updated_by'] = auth()->id();

        $supplier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully.',
            'data' => $supplier->fresh(),
        ]);
    }

    protected function assertSupplierEligible(Supplier $supplier): void
    {
        if (!in_array($supplier->supplier_type, $this->allowedTypes, true)) {
            abort(404);
        }
    }

    protected function validateSupplier(Request $request): array
    {
        $types = implode(',', $this->allowedTypes);

        return $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:50',
            'supplier_type' => "required|in:{$types}",
            'payment_terms' => 'nullable|string|max:100',
            'delivery_terms' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);
    }
}
