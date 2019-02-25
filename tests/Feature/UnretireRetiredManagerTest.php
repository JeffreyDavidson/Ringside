<?php

namespace Tests\Feature;

use App\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnretireRetiredManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->delete(route('managers.unretire', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertNotNull($manager->fresh()->previousRetirement->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->delete(route('managers.unretire', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_manager()
    {
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->delete(route('managers.unretire', $manager));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_retired_manager_cannot_unretire()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->delete(route('managers.unretire', $manager));

        $response->assertStatus(403);
    }
}
