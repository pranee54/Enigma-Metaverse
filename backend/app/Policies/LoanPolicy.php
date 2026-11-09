<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['hr', 'finance', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['hr', 'finance']);
    }

    public function approve(User $user, Loan $loan): bool
    {
        return $user->hasRole('finance');
    }
}
