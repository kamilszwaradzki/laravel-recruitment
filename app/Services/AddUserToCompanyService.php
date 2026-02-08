<?php

namespace App\Services;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;

class AddUserToCompanyService
{
    public function add(Company $company, User $user): void
    {
        $role = $company->users()->exists()
            ? CompanyRole::MEMBER
            : CompanyRole::CAPTAIN;

        $company->users()->attach($user->id, [
            'role' => $role,
        ]);
    }
}
