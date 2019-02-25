<?php

namespace Tests\Feature;

use App\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReinstateSuspendedManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_reinstate_a_suspended_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->delete(route('managers.reinstate', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertNotNull($manager->fresh()->previousSuspension->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_suspended_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->delete(route('managers.reinstate', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_suspended_manager()
    {
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->delete(route('managers.reinstate', $manager));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_suspended_manager_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->delete(route('managers.reinstate', $manager));

        $response->assertStatus(403);
    }
}
