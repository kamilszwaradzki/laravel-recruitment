<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Enums\CompanyRole;

class CompanyUser extends Pivot
{
    protected $table = 'company_user';

    protected $casts = [
        'role' => CompanyRole::class,
    ];

    public function isCaptain(): bool
    {
        return $this->role === CompanyRole::CAPTAIN;
    }
}
