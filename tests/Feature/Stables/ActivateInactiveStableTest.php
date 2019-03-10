<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivateInactiveStableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_activate_an_inactive_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('inactive')->create();

        $response = $this->post(route('stables.activate', $stable));

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) {
            $this->assertTrue($stable->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_activate_an_inactive_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('inactive')->create();

        $response = $this->post(route('stables.activate', $stable));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_activate_an_inactive_stable()
    {
        $stable = factory(Stable::class)->states('inactive')->create();

        $response = $this->post(route('stables.activate', $stable));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_active_stable_cannot_be_activated()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('active')->create();

        $response = $this->post(route('stables.activate', $stable));

        $response->assertStatus(403);
    }
}
