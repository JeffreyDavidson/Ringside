<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteStableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->delete(route('stables.destroy', $stable));

        $this->assertSoftDeleted('stables', ['name' => $stable->name]);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->create();

        $response = $this->delete(route('stables.destroy', $stable));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_delete_a_stable()
    {
        $stable = factory(Stable::class)->create();

        $response = $this->delete(route('stables.destroy', $stable));

        $response->assertRedirect('/login');
    }
}
