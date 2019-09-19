<?php

namespace Tests\Feature\Guest\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group guests
 * @group roster
 */
class ViewManagerBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_a_manager_profile()
    {
        $manager = factory(Manager::class)->create();

        $response = $this->showRequest($manager);

        $response->assertRedirect(route('login'));
    }
}
