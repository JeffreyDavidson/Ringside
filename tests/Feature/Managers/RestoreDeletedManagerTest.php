<?php

namespace Tests\Feature\Manager;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestoreDeletedManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('managers.restore', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertNull($manager->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('managers.restore', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_manager()
    {
        $manager = factory(Manager::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('managers.restore', $manager));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_deleted_manager_cannot_be_restored()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.restore', $manager));

        $response->assertStatus(404);
    }
}
