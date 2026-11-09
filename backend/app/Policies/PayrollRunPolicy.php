<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['hr', 'finance', 'auditor']);
    }

    public function view(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasAnyRole(['hr', 'finance', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('hr');
    }

    public function calculate(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasRole('hr');
    }

    public function validate(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasRole('hr');
    }

    public function submit(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasRole('hr');
    }

    public function approve(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasRole('finance') && $payrollRun->prepared_by !== $user->id;
    }

    public function finalise(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasRole('finance');
    }

    public function markPaid(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasRole('finance');
    }

    public function reconcile(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasRole('finance');
    }
}
