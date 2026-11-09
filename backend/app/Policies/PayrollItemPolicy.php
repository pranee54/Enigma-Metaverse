<?php

namespace App\Policies;

use App\Models\PayrollItem;
use App\Models\User;

class PayrollItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['hr', 'finance', 'auditor', 'employee']);
    }

    public function view(User $user, PayrollItem $payslip): bool
    {
        if ($user->hasAnyRole(['hr', 'finance', 'auditor'])) {
            return true;
        }

        return $user->hasRole('employee')
            && $user->employee_id !== null
            && (int) $user->employee_id === (int) $payslip->employee_id;
    }
}
