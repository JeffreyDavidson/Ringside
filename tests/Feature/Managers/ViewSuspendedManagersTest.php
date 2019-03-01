<?php

namespace Tests\Feature\Manager;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewSuspendedManagersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_suspended_managers()
    {
        $this->actAs('administrator');
        $suspendedManagers = factory(Manager::class, 3)->states('suspended')->create();
        $activeManager = factory(Manager::class)->states('active')->create();

        $response = $this->get(route('managers.index', ['state' => 'suspended']));

        $response->assertOk();
        $response->assertSee(e($suspendedManagers[0]->full_name));
        $response->assertSee(e($suspendedManagers[1]->full_name));
        $response->assertSee(e($suspendedManagers[2]->full_name));
        $response->assertDontSee(e($activeManager->full_name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_suspended_managers()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Manager::class)->states('suspended')->create();

        $response = $this->get(route('managers.index', ['state' => 'suspended']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_suspended_managers()
    {
        $wrestler = factory(Manager::class)->states('suspended')->create();

        $response = $this->get(route('managers.index', ['state' => 'suspended']));

        $response->assertRedirect('/login');
    }
}
