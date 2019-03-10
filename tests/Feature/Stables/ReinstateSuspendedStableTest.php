<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReinstateSuspendedStableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_reinstate_a_suspended_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('suspended')->create();

        $response = $this->delete(route('stables.reinstate', $stable));

        $response->assertRedirect(route('stables.index'));
        $this->assertNotNull($stable->fresh()->previousSuspension->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_suspended_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('suspended')->create();

        $response = $this->delete(route('stables.reinstate', $stable));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_suspended_stable()
    {
        $stable = factory(Stable::class)->states('suspended')->create();

        $response = $this->delete(route('stables.reinstate', $stable));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_suspended_stable_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->delete(route('stables.reinstate', $stable));

        $response->assertStatus(403);
    }
}
