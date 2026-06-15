<?php

namespace App\Http\Controllers\Web\Concerns;

use App\Models\Supplier;
use Illuminate\Http\Request;

trait ManagesModuleSuppliers
{
    abstract protected function supplierModule(): string;

    abstract protected function supplierRoutePrefix(): string;

    abstract protected function supplierViewPrefix(): string;

    /** @return list<string> */
    abstract protected function supplierAllowedTypes(): array;

    abstract protected function supplierDefaultType(): string;

    /** @return list<string> */
    abstract protected function supplierPermissions(): array;

    public function index(Request $request)
    {
        $query = Supplier::query()
            ->when(true, fn ($q) => $this->applySupplierScope($q));

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
        }

        $suppliers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view("{$this->supplierViewPrefix()}.index", [
            'suppliers' => $suppliers,
            'module' => $this->supplierModule(),
        ]);
    }

    public function create()
    {
        return view("{$this->supplierViewPrefix()}.create", [
            'module' => $this->supplierModule(),
            'allowedTypes' => $this->supplierAllowedTypes(),
            'defaultType' => $this->supplierDefaultType(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateSupplier($request);
        $validated['is_active'] = true;
        $validated['created_by'] = auth()->id();

        Supplier::create($validated);

        return redirect()
            ->route("{$this->supplierRoutePrefix()}.index")
            ->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier)
    {
        $this->assertSupplierEligible($supplier);

        return view("{$this->supplierViewPrefix()}.edit", [
            'supplier' => $supplier,
            'module' => $this->supplierModule(),
            'allowedTypes' => $this->supplierAllowedTypes(),
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->assertSupplierEligible($supplier);

        $validated = $this->validateSupplier($request);
        $validated['updated_by'] = auth()->id();

        $supplier->update($validated);

        return redirect()
            ->route("{$this->supplierRoutePrefix()}.index")
            ->with('success', 'Supplier updated successfully.');
    }

    public function deactivate(Supplier $supplier)
    {
        $this->assertSupplierEligible($supplier);

        $supplier->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Supplier deactivated.');
    }

    public function activate(Supplier $supplier)
    {
        $this->assertSupplierEligible($supplier);

        $supplier->update([
            'is_active' => true,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Supplier reactivated.');
    }

    protected function applySupplierScope($query)
    {
        return match ($this->supplierModule()) {
            'pharmacy' => $query->forPharmacy(),
            'lab' => $query->forLaboratory(),
            'radiology' => $query->forRadiology(),
            default => $query,
        };
    }

    protected function assertSupplierEligible(Supplier $supplier): void
    {
        $eligible = match ($this->supplierModule()) {
            'pharmacy' => in_array($supplier->supplier_type, ['pharmacy', 'both', 'general'], true),
            'lab' => in_array($supplier->supplier_type, ['laboratory', 'both', 'general', 'equipment', 'reagent', 'consumable'], true),
            'radiology' => in_array($supplier->supplier_type, ['radiology', 'both', 'general'], true),
            default => true,
        };

        if (!$eligible) {
            abort(404);
        }
    }

    protected function validateSupplier(Request $request): array
    {
        $types = implode(',', $this->supplierAllowedTypes());

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
