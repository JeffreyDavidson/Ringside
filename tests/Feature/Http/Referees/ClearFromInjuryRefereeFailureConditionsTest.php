<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use App\Exceptions\CannotBeClearedFromInjuryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class ClearFromInjuryRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_clear_an_injured_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_clear_an_injured_referee()
    {
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_bookable_referee_cannot_be_cleared_from_an_injury()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_referee_cannot_be_cleared_from_an_injury()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->pendingEmployment()->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_referee_cannot_be_cleared_from_an_injury()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->retired()->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_referee_cannot_be_cleared_from_an_injury()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->suspended()->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertForbidden();
    }
}
