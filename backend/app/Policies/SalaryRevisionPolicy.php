<?php

namespace App\Policies;

use App\Models\SalaryRevision;
use App\Models\User;

class SalaryRevisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['hr', 'finance', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('hr');
    }

    public function approve(User $user, SalaryRevision $revision): bool
    {
        return $user->hasRole('finance') && $revision->initiated_by !== $user->id;
    }
}
