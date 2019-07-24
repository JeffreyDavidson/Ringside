<?php

namespace Tests\Feature\Guest\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group guests
 */
class RetireManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_retire_a_bookable_manager()
    {
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->put(route('managers.retire', $manager));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_retire_a_suspended_manager()
    {
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->put(route('managers.retire', $manager));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_retire_an_injured_manager()
    {
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->put(route('managers.retire', $manager));

        $response->assertRedirect(route('login'));
    }
}
