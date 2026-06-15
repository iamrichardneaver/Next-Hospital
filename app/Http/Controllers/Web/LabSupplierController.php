<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\ManagesModuleSuppliers;

class LabSupplierController extends Controller
{
    use ManagesModuleSuppliers;

    protected function supplierModule(): string
    {
        return 'lab';
    }

    protected function supplierRoutePrefix(): string
    {
        return 'lab.suppliers';
    }

    protected function supplierViewPrefix(): string
    {
        return 'lab.suppliers';
    }

    protected function supplierAllowedTypes(): array
    {
        return ['laboratory', 'both', 'general', 'equipment', 'reagent', 'consumable'];
    }

    protected function supplierDefaultType(): string
    {
        return 'laboratory';
    }

    protected function supplierPermissions(): array
    {
        return ['view_lab_suppliers', 'manage_lab_suppliers'];
    }
}
