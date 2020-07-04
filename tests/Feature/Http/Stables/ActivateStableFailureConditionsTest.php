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
class ActivateStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_activate_a_pending_introduction_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->pendingIntroduction()->create();

        $response = $this->activateRequest($stable);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_activate_a_pending_introduction_stable()
    {
        $stable = StableFactory::new()->pendingIntroduction()->create();

        $response = $this->introduceRequest($stable);

        $response->assertRedirect(route('login'));
    }
}
