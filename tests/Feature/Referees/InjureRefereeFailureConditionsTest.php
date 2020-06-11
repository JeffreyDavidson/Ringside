<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use App\Exceptions\CannotBeInjuredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class InjureRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_injure_a_bookable_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->injureRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_injure_a_bookable_referee()
    {
        $referee = RefereeFactory::new()->create();

        $response = $this->injureRequest($referee);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_already_injured_referee_cannot_be_injured()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeInjuredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->injureRequest($referee);

        $response->assertForbidden();
    }
}
