<?php

namespace Tests\Feature;

use App\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewActiveManagersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_active_managers()
    {
        $this->actAs('administrator');
        $activeManagers = factory(Manager::class, 3)->states('active')->create();
        $inactiveManager = factory(Manager::class)->states('inactive')->create();

        $response = $this->get(route('managers.index'));

        $response->assertOk();
        $response->assertSee(e($activeManagers[0]->full_name));
        $response->assertSee(e($activeManagers[1]->full_name));
        $response->assertSee(e($activeManagers[2]->full_name));
        $response->assertDontSee(e($inactiveManager->full_name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_active_managers()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Manager::class)->states('active')->create();

        $response = $this->get(route('managers.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_active_managers()
    {
        $wrestler = factory(Manager::class)->states('active')->create();

        $response = $this->get(route('managers.index'));

        $response->assertRedirect('/login');
    }
}
