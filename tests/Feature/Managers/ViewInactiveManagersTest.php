<?php

namespace Tests\Feature\Manager;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewInactiveManagersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_inactive_managers()
    {
        $this->actAs('administrator');
        $inactiveManagers = factory(Manager::class, 3)->states('inactive')->create();
        $activeManager = factory(Manager::class)->states('active')->create();

        $response = $this->get(route('managers.index', ['state' => 'inactive']));

        $response->assertOk();
        $response->assertSee(e($inactiveManagers[0]->full_name));
        $response->assertSee(e($inactiveManagers[1]->full_name));
        $response->assertSee(e($inactiveManagers[2]->full_name));
        $response->assertDontSee(e($activeManager->full_name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_inactive_managers()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('inactive')->create();

        $response = $this->get(route('managers.index', ['state' => 'inactive']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_inactive_managers()
    {
        $manager = factory(Manager::class)->states('inactive')->create();

        $response = $this->get(route('managers.index', ['state' => 'inactive']));

        $response->assertRedirect('/login');
    }
}
