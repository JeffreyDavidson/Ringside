<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SuspendStableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_suspend_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->post(route('stables.suspend', $stable));

        $response->assertRedirect(route('stables.index', ['state' => 'suspended']));
        $this->assertEquals(today()->toDateTimeString(), $stable->fresh()->suspension->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->create();

        $response = $this->post(route('stables.suspend', $stable));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_suspend_a_stable()
    {
        $stable = factory(Stable::class)->create();

        $response = $this->post(route('stables.suspend', $stable));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_suspended_stable_cannot_be_suspended_again()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('suspended')->create();

        $response = $this->post(route('stables.suspend', $stable));

        $response->assertStatus(403);
    }
}
