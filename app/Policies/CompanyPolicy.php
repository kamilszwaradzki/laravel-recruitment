<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Company;

class CompanyPolicy
{
    public function manageMembers(User $user, Company $company): bool
    {
        return $company->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'captain')
            ->exists();
    }
}
