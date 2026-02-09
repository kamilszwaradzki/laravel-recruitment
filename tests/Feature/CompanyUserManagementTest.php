<?php

namespace Tests\Feature;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private CompanyUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CompanyUserService::class);
    }

    /** @test */
    public function first_user_in_company_becomes_captain()
    {
        $company = Company::create(['name' => 'Example Company']);
        $user = User::create(array_merge([
                'name' => 'John Doe',
                'email' => 'john@doe.com',
                'email_verified_at' => now(),
            ], ['password' => Hash::make('test!')]));

        $this->service->add($company, $user);

        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'role'       => CompanyRole::CAPTAIN->value,
        ]);

        $this->assertTrue(
            $company->users()->first()->pivot->isCaptain()
        );
    }

    /** @test */
    public function second_and_later_users_get_member_role()
    {
        $company = Company::create(['name' => 'Example Company']);
        $captain = User::create(array_merge([
            'name' => 'Captain Doe',
            'email' => 'captain@doe.com',
            'email_verified_at' => now(),
        ], ['password' => Hash::make('testCaptain!')]));
        $member  = User::create(array_merge([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'email_verified_at' => now(),
        ], ['password' => Hash::make('testMember!')]));


        $this->service->add($company, $captain); // pierwszy → captain
        $this->service->add($company, $member);  // drugi → member

        $this->assertEquals(
            CompanyRole::CAPTAIN,
            $company->users()->where('user_id', $captain->id)->first()->pivot->role
        );

        $this->assertEquals(
            CompanyRole::MEMBER,
            $company->users()->where('user_id', $member->id)->first()->pivot->role
        );
    }

    /** @test */
    public function cannot_add_user_that_is_already_a_member()
    {
        $company = Company::create(['name' => 'Example Company']);
        $user = User::create(array_merge([
                'name' => 'User Doe',
                'email' => 'user@doe.com',
                'email_verified_at' => now(),
            ], ['password' => Hash::make('test!')]));

        $this->service->add($company, $user);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User is already a member of this company.');

        $this->service->add($company, $user); // drugi raz → wyjątek
    }

    /** @test */
    public function non_captain_cannot_transfer_captain_role()
    {
        $company  = Company::create(['name' => 'Example Company']);
        $captain  = User::create(array_merge([
                'name' => 'Captain Doe',
                'email' => 'captain@doe.com',
                'email_verified_at' => now(),
            ], ['password' => Hash::make('testCaptain!')]));
        $member   = User::create(array_merge([
                'name' => 'Member Doe',
                'email' => 'member@doe.com',
                'email_verified_at' => now(),
            ], ['password' => Hash::make('testMember!')]));

        $this->service->add($company, $captain); // captain
        $this->service->add($company, $member);  // member

        $this->actingAs($member);

        $response = $this->postJson(
            route('companies.users.transfer', [$company, $member]),
            []
        );

        $response->assertForbidden(); // 403

    }

    /** @test */
    public function non_captain_cannot_remove_member()
    {
        $company = Company::create(['name' => 'Example Company']);
        $captain = User::create(array_merge([
            'name' => 'Captain Doe',
            'email' => 'captain@doe.com',
            'email_verified_at' => now(),
        ], ['password' => Hash::make('testCaptain!')]));
        $member = User::create(array_merge([
            'name' => 'Member Doe',
            'email' => 'member@doe.com',
            'email_verified_at' => now(),
        ], ['password' => Hash::make('testMember!')]));

        $this->service->add($company, $captain);
        $this->service->add($company, $member);

        $this->actingAs($member);

        $response = $this->deleteJson(
            route('companies.users.detach', [$company, $member]),
            []
        );

        $response->assertForbidden(); // 403
    }

    /** @test */
    public function captain_can_transfer_role_to_another_member()
    {
        $company  = Company::create(['name' => 'Example Company']);
        $oldCaptain = User::create(array_merge([
                'name' => 'Captain Old',
                'email' => 'captain@old.com',
                'email_verified_at' => now(),
            ], ['password' => Hash::make('testOldCaptain!')]));
        $newCaptain = User::create(array_merge([
                'name' => 'Captain New',
                'email' => 'captain@new.com',
                'email_verified_at' => now(),
            ], ['password' => Hash::make('testNewCaptain!')]));

        $this->service->add($company, $oldCaptain); // captain
        $this->service->add($company, $newCaptain); // member

        $this->actingAs($oldCaptain);

        $this->service->transferCaptain($company, $oldCaptain, $newCaptain);

        $this->assertFalse(
            $company->users()->where('user_id', $oldCaptain->id)->first()->pivot->isCaptain()
        );

        $this->assertTrue(
            $company->users()->where('user_id', $newCaptain->id)->first()->pivot->isCaptain()
        );

        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id'    => $newCaptain->id,
            'role'       => CompanyRole::CAPTAIN->value,
        ]);
    }

    /** @test */
    public function captain_cannot_transfer_to_non_member()
    {
        $company     = Company::create(['name' => 'Example Company']);
        $captain     =  User::create(array_merge([
                'name' => 'Captain Doe',
                'email' => 'captain@doe.com',
                'email_verified_at' => now(),
            ], ['password' => Hash::make('testCaptain!')]));
        $outsider    =  User::create(array_merge([
                'name' => 'Outsider Doe',
                'email' => 'outsider@doe.com',
                'email_verified_at' => now(),
            ], ['password' => Hash::make('testOutsider!')]));

        $this->service->add($company, $captain);

        $this->expectException(\DomainException::class);

        $this->service->transferCaptain($company, $captain, $outsider);
    }

    /** @test */
    public function captain_can_remove_member()
    {
        $company = Company::create(['name' => 'Example Company']);
        $captain = User::create(array_merge([
            'name' => 'Captain Doe',
            'email' => 'captain@doe.com',
            'email_verified_at' => now(),
        ], ['password' => Hash::make('testCaptain!')]));
        $member = User::create(array_merge([
            'name' => 'Member Doe',
            'email' => 'member@doe.com',
            'email_verified_at' => now(),
        ], ['password' => Hash::make('testMember!')]));

        $this->service->add($company, $captain);
        $this->service->add($company, $member);

        $this->actingAs($captain);
        $this->service->remove($company, $member);

        $this->assertDatabaseMissing('company_user', [
            'company_id' => $company->id,
            'user_id' => $member->id,
        ]);
    }

    /** @test */
    public function captain_cannot_remove_themselves()
    {
        $company = Company::create(['name' => 'Example Company']);
        $captain = User::create(array_merge([
            'name' => 'Captain Doe',
            'email' => 'captain@doe.com',
            'email_verified_at' => now(),
        ], ['password' => Hash::make('testCaptain!')]));

        $this->service->add($company, $captain);

        $this->actingAs($captain);
        $this->expectException(\DomainException::class);
        $this->service->remove($company, $captain);
    }
}