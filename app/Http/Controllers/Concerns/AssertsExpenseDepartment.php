<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Expense;

trait AssertsExpenseDepartment
{
    /**
     * @return array<string, string>
     */
    protected function getRoleDepartmentMap(): array
    {
        return [
            'pharmacist' => 'pharmacy',
            'lab_scientist' => 'lab',
            'lab_manager' => 'lab',
            'lab_technician' => 'lab',
            'radiologist' => 'radiology',
            'radiology_technician' => 'radiology',
            'receptionist' => 'reception',
            'nurse' => 'nursing',
            'cashier' => 'cashier',
        ];
    }

    protected function inferDepartmentFromRole(): string
    {
        $user = auth()->user();

        foreach ($this->getRoleDepartmentMap() as $role => $department) {
            if ($user->hasRole($role)) {
                return $department;
            }
        }

        return 'general';
    }

    /**
     * @return list<string>
     */
    protected function getAllowedDepartmentsForUser(): array
    {
        $user = auth()->user();
        $allowed = [];

        foreach ($this->getRoleDepartmentMap() as $role => $department) {
            if ($user->hasRole($role)) {
                $allowed[] = $department;
            }
        }

        return array_values(array_unique($allowed));
    }

    protected function assertUserCanSubmitForDepartment(string $department): void
    {
        if (!array_key_exists($department, Expense::DEPARTMENTS)) {
            abort(404, 'Unknown department for expense submission.');
        }

        $user = auth()->user();

        if ($user->hasRole(['admin', 'super_admin'])) {
            return;
        }

        if (!in_array($department, $this->getAllowedDepartmentsForUser(), true)) {
            abort(403, 'You cannot submit expenses for this department.');
        }
    }
}
