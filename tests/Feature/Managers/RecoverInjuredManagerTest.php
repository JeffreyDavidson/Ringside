<?php

namespace Tests\Feature\Manager;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecoverInjuredManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_recover_an_injured_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->delete(route('managers.recover', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertNotNull($manager->fresh()->previousInjury->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_recover_an_injured_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->delete(route('managers.recover', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_recover_an_injured_manager()
    {
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->delete(route('managers.recover', $manager));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_injured_manager_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->delete(route('managers.recover', $manager));

        $response->assertStatus(403);
    }
}
