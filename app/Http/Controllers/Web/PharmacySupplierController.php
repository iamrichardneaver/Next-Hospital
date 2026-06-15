<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\ManagesModuleSuppliers;

class PharmacySupplierController extends Controller
{
    use ManagesModuleSuppliers;

    protected function supplierModule(): string
    {
        return 'pharmacy';
    }

    protected function supplierRoutePrefix(): string
    {
        return 'pharmacy.suppliers';
    }

    protected function supplierViewPrefix(): string
    {
        return 'pharmacy.suppliers';
    }

    protected function supplierAllowedTypes(): array
    {
        return ['pharmacy', 'both', 'general'];
    }

    protected function supplierDefaultType(): string
    {
        return 'pharmacy';
    }

    protected function supplierPermissions(): array
    {
        return ['view_pharmacy_suppliers', 'manage_pharmacy_suppliers'];
    }
}
