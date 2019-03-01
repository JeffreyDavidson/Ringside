<?php

namespace Tests\Feature\Manager;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SuspendManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_suspend_a_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->post(route('managers.suspend', $manager));

        $response->assertRedirect(route('managers.index', ['state' => 'suspended']));
        $this->assertEquals(today()->toDateTimeString(), $manager->fresh()->suspension->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->create();

        $response = $this->post(route('managers.suspend', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_suspend_a_manager()
    {
        $manager = factory(Manager::class)->create();

        $response = $this->post(route('managers.suspend', $manager));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_suspended_manager_cannot_be_suspended_again()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->post(route('managers.suspend', $manager));

        $response->assertStatus(403);
    }
}
