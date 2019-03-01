<?php

namespace Tests\Feature\Manager;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->delete(route('managers.destroy', $manager));

        $this->assertSoftDeleted('managers', ['first_name' => $manager->first_name, 'last_name' => $manager->last_name]);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->create();

        $response = $this->delete(route('managers.destroy', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_delete_a_manager()
    {
        $manager = factory(Manager::class)->create();

        $response = $this->delete(route('managers.destroy', $manager));

        $response->assertRedirect('/login');
    }
}
