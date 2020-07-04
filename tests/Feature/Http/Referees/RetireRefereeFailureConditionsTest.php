<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use App\Exceptions\CannotBeRetiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class RetireRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_retire_a_bookable_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->retireRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_retire_an_injured_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->retireRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_suspended_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->suspended()->create();

        $response = $this->retireRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_referee()
    {
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->retireRequest($referee);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_retire_a_suspended_referee()
    {
        $referee = RefereeFactory::new()->suspended()->create();

        $response = $this->retireRequest($referee);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_retire_an_injured_referee()
    {
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->retireRequest($referee);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_retired_referee_cannot_be_retired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeRetiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->retired()->create();

        $response = $this->retireRequest($referee);
    }
}
