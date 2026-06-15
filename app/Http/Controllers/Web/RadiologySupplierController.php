<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\ManagesModuleSuppliers;

class RadiologySupplierController extends Controller
{
    use ManagesModuleSuppliers;

    protected function supplierModule(): string
    {
        return 'radiology';
    }

    protected function supplierRoutePrefix(): string
    {
        return 'radiology.suppliers';
    }

    protected function supplierViewPrefix(): string
    {
        return 'radiology.suppliers';
    }

    protected function supplierAllowedTypes(): array
    {
        return ['radiology', 'both', 'general'];
    }

    protected function supplierDefaultType(): string
    {
        return 'radiology';
    }

    protected function supplierPermissions(): array
    {
        return ['view_radiology_suppliers', 'manage_radiology_suppliers'];
    }
}
