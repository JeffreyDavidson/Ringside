<?php

namespace Tests\Feature;

use App\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RetireManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_retire_a_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->post(route('managers.retire', $manager));

        $response->assertRedirect(route('managers.index', ['state' => 'retired']));
        $this->assertEquals(today()->toDateTimeString(), $manager->fresh()->retirement->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->create();

        $response = $this->post(route('managers.retire', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_retire_a_manager()
    {
        $manager = factory(Manager::class)->create();

        $response = $this->post(route('managers.retire', $manager));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_retired_manager_cannot_be_retired_again()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->post(route('managers.retire', $manager));

        $response->assertStatus(403);
    }
}
