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
class RestoreStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($stable);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_stable()
    {
        $stable = StableFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($stable);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_bookable_stable_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->put(route('stables.restore', $stable));

        $response->assertNotFound();
    }

    /** @test */
    public function a_pending_introduction_stable_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->put(route('stables.restore', $stable));

        $response->assertNotFound();
    }

    /** @test */
    public function a_retired_stable_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->put(route('stables.restore', $stable));

        $response->assertNotFound();
    }
}
