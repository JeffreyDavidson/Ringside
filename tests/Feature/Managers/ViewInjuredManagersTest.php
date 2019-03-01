<?php

namespace Tests\Feature\Manager;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewInjuredManagersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_injured_managers()
    {
        $this->actAs('administrator');
        $injuredManagers = factory(Manager::class, 3)->states('injured')->create();
        $activeManager = factory(Manager::class)->states('active')->create();

        $response = $this->get(route('managers.index', ['state' => 'injured']));

        $response->assertOk();
        $response->assertSee(e($injuredManagers[0]->full_name));
        $response->assertSee(e($injuredManagers[1]->full_name));
        $response->assertSee(e($injuredManagers[2]->full_name));
        $response->assertDontSee(e($activeManager->full_name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_injured_managers()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->get(route('managers.index', ['state' => 'injured']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_injured_managers()
    {
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->get(route('managers.index', ['state' => 'injured']));

        $response->assertRedirect('/login');
    }
}
