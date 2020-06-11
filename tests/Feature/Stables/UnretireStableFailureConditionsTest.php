<?php

namespace Tests\Feature\Stables;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\StableFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 */
class UnretireStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->retired()->create();

        $response = $this->retireRequest($stable);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_stable()
    {
        $stable = StableFactory::new()->retired()->create();

        $response = $this->retireRequest($stable);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_bookable_stable_cannot_be_unretired()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->put(route('stables.unretire', $stable));

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_introduction_stable_cannot_be_unretired()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->put(route('stables.unretire', $stable));

        $response->assertForbidden();
    }
}
