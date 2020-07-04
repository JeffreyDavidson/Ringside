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
class DeleteStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_delete_an_active_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->active()->create();

        $response = $this->deleteRequest($stable);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_pending_introduction_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->pendingIntroduction()->create();

        $response = $this->deleteRequest($stable);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_retired_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->retired()->create();

        $response = $this->deleteRequest($stable);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_an_active_stable()
    {
        $stable = StableFactory::new()->active()->create();

        $response = $this->delete($stable);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_delete_a_pending_introduction_stable()
    {
        $stable = StableFactory::new()->pendingIntroduction()->create();

        $response = $this->deleteRequest($stable);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_delete_a_retired_stable()
    {
        $stable = StableFactory::new()->retired()->create();

        $response = $this->deleteRequest($stable);

        $response->assertRedirect(route('login'));
    }
}
