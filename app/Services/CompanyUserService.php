<?php

namespace App\Services;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyUserService
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

    public function transferCaptain(Company $company, User $from, User $to): void
    {
        if ($from->id === $to->id) {
            throw new \DomainException("You can't transfer captain role to yourself.");
        }

        DB::transaction(function () use ($company, $from, $to) {
            $pivotFrom = $company->users()->find($from->id);
            $pivotTo   = $company->users()->find($to->id);

            if (!$pivotFrom || !$pivotTo) {
                throw new \DomainException("One of the users is not a member of this company.");
            }

            $company->users()->updateExistingPivot($from->id, [
                'role' => CompanyRole::MEMBER,
            ]);

            $company->users()->updateExistingPivot($to->id, [
                'role' => CompanyRole::CAPTAIN,
            ]);
        });
    }
}
