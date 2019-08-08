<?php

namespace Tests\Feature\Guest\Managers;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group guests
 */
class ActivateManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_activate_pending_introduction_manager()
    {
        $manager = factory(Manager::class)->states('pending-introduction')->create();

        $response = $this->put(route('managers.activate', $manager));

        $response->assertRedirect(route('login'));
    }
}
