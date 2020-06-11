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
class RetireStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_retire_an_active_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->active()->create();

        $response = $this->retireRequest($stable);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_an_active_stable()
    {
        $stable = StableFactory::new()->active()->create();

        $response = $this->retireRequest($stable);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_pending_introduction_stable_cannot_be_retired()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->put(route('stables.retire', $stable));

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_stable_cannot_be_retired()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->put(route('stables.retire', $stable));

        $response->assertForbidden();
    }
}
