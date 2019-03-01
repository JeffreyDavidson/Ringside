<?php

namespace Tests\Feature\Manager;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeactivateActiveManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_deactivate_an_active_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('active')->create();

        $response = $this->post(route('managers.deactivate', $manager));

        $response->assertRedirect(route('managers.index', ['state' => 'inactive']));
        tap($manager->fresh(), function ($manager) {
            $this->assertFalse($manager->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_deactivate_an_active_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('active')->create();

        $response = $this->post(route('managers.deactivate', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_deactivate_an_active_manager()
    {
        $manager = factory(Manager::class)->states('active')->create();

        $response = $this->post(route('managers.deactivate', $manager));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_inactive_manager_cannot_be_deactivated()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('inactive')->create();

        $response = $this->post(route('managers.deactivate', $manager));

        $response->assertStatus(403);
    }
}
