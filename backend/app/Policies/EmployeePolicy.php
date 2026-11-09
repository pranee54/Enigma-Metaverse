<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['hr', 'finance', 'auditor']);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['hr', 'finance', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('hr');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasRole('hr');
    }

    public function updateSalary(User $user, Employee $employee): bool
    {
        return $user->hasRole('hr');
    }
}
