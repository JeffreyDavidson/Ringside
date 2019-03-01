<?php

namespace Tests\Feature\Manager;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewRetiredManagersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_retired_managers()
    {
        $this->actAs('administrator');
        $retiredManagers = factory(Manager::class, 3)->states('retired')->create();
        $activeManager = factory(Manager::class)->states('active')->create();

        $response = $this->get(route('managers.index', ['state' => 'retired']));

        $response->assertOk();
        $response->assertSee(e($retiredManagers[0]->full_name));
        $response->assertSee(e($retiredManagers[1]->full_name));
        $response->assertSee(e($retiredManagers[2]->full_name));
        $response->assertDontSee(e($activeManager->full_name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_retired_managers()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->get(route('managers.index', ['state' => 'retired']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_retired_managers()
    {
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->get(route('managers.index', ['state' => 'retired']));

        $response->assertRedirect('/login');
    }
}
