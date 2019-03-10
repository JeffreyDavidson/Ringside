<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeactivateActiveStableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_deactivate_an_active_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('active')->create();

        $response = $this->post(route('stables.deactivate', $stable));

        $response->assertRedirect(route('stables.index', ['state' => 'inactive']));
        tap($stable->fresh(), function ($stable) {
            $this->assertFalse($stable->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_deactivate_an_active_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('active')->create();

        $response = $this->post(route('stables.deactivate', $stable));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_deactivate_an_active_stable()
    {
        $stable = factory(Stable::class)->states('active')->create();

        $response = $this->post(route('stables.deactivate', $stable));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_inactive_stable_cannot_be_deactivated()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('inactive')->create();

        $response = $this->post(route('stables.deactivate', $stable));

        $response->assertStatus(403);
    }
}
