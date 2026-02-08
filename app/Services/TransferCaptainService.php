<?php

namespace App\Services;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransferCaptainService
{
    public function transfer(Company $company, User $from, User $to): void
    {
        DB::transaction(function () use ($company, $from, $to) {
            $company->users()->updateExistingPivot($from->id, [
                'role' => CompanyRole::MEMBER,
            ]);

            $company->users()->updateExistingPivot($to->id, [
                'role' => CompanyRole::CAPTAIN,
            ]);
        });
    }
}
