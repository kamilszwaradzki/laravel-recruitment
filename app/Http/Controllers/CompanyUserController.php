<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\AddUserToCompanyService;
use App\Services\TransferCaptainService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CompanyUserController extends Controller
{
    use AuthorizesRequests;

    public function index(Company $company)
    {
        $users = $company->users()->paginate(10);
        $availableUsers = User::whereDoesntHave('companies', function($query) use ($company) {
            $query->where('company_id', $company->id);
        })->get();

        return view('companies.users.index', compact('company', 'users', 'availableUsers'));
    }

    public function attach(Request $request, Company $company, AddUserToCompanyService $addUserToCompanyService)
    {
        if ($company->users()->exists()) {
            $this->authorize('manageMembers', $company);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        $addUserToCompanyService->add($company, $user);

        return redirect()->route('companies.users.index', $company)
            ->with('success', 'User assigned to company successfully.');
    }

    public function detach(Company $company, User $user)
    {
        $this->authorize('manageMembers', $company);
        // Sprawdź czy zalogowany user należy do tej firmy
        if (!auth()->user()->companies()->where('company_id', $company->id)->exists()) {
            abort(403, 'You must be a member of this company to remove users.');
        }
        $member = $company->users()->findOrFail($user->id);
        if ($member->pivot->isCaptain()) {
            throw new DomainException('Captain cannot remove himself');
        }

        $company->users()->detach($user->id);

        return redirect()->route('companies.users.index', $company)
            ->with('success', 'User removed from company successfully.');
    }

    public function transferCaptain(Company $company, User $user, TransferCaptainService $transferCaptainService)
    {
        $this->authorize('manageMembers', $company);

        $transferCaptainService->transfer($company, auth()->user(), $user);

        return redirect()->route('companies.users.index', $company)
            ->with('success', 'User transfered role successfully.');
    }
}
