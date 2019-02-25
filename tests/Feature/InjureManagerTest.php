<?php

namespace Tests\Feature;

use App\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InjureManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_injure_a_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->post(route('managers.injure', $manager));

        $response->assertRedirect(route('managers.index', ['state' => 'injured']));
        $this->assertEquals(today()->toDateTimeString(), $manager->fresh()->injury->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_injure_a_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->create();

        $response = $this->post(route('managers.injure', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_injure_a_manager()
    {
        $manager = factory(Manager::class)->create();

        $response = $this->post(route('managers.injure', $manager));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_injured_manager_cannot_be_injured_again()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->post(route('managers.injure', $manager));

        $response->assertStatus(403);
    }
}
