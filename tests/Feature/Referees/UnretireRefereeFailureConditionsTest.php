<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use App\Exceptions\CannotBeUnretiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class UnretireRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->retired()->create();

        $response = $this->unretireRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_referee()
    {
        $referee = RefereeFactory::new()->retired()->create();

        $response = $this->unretireRequest($referee);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_bookable_referee_cannot_be_unretired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeUnretiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->unretireRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_referee_cannot_be_unretired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeUnretiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->pendingEmployment()->create();

        $response = $this->unretireRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_referee_cannot_be_unretired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeUnretiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->suspended()->create();

        $response = $this->unretireRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_referee_cannot_be_unretired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeUnretiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->unretireRequest($referee);

        $response->assertForbidden();
    }
}
