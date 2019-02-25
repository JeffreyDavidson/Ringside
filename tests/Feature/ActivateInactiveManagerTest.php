<?php

namespace Tests\Feature;

use App\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivateInactiveManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_activate_an_inactive_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('inactive')->create();

        $response = $this->post(route('managers.activate', $manager));

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) {
            $this->assertTrue($manager->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_activate_an_inactive_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('inactive')->create();

        $response = $this->post(route('managers.activate', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_activate_an_inactive_manager()
    {
        $manager = factory(Manager::class)->states('inactive')->create();

        $response = $this->post(route('managers.activate', $manager));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_active_manager_cannot_be_activated()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('active')->create();

        $response = $this->post(route('managers.activate', $manager));

        $response->assertStatus(403);
    }
}
