<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use App\Exceptions\CannotBeEmployedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class EmployRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_employ_a_pending_employment_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->pendingEmployment()->create();

        $response = $this->employRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_a_referee()
    {
        $referee = RefereeFactory::new()->create();

        $response = $this->employRequest($referee);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_bookable_referee_cannot_be_employed()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeEmployedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->employRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_referee_cannot_be_employed()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeEmployedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->retired()->create();

        $response = $this->employRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_referee_cannot_be_employed()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeEmployedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->suspended()->create();

        $response = $this->employRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_referee_cannot_be_employed()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeEmployedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->employRequest($referee);

        $response->assertForbidden();
    }
}
