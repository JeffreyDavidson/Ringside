<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewInactiveStablesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_inactive_stables()
    {
        $this->actAs('administrator');
        $inactiveStables = factory(Stable::class, 3)->states('inactive')->create();
        $activeStable = factory(Stable::class)->states('active')->create();

        $response = $this->get(route('stables.index', ['state' => 'inactive']));

        $response->assertOk();
        $response->assertSee(e($inactiveStables[0]->name));
        $response->assertSee(e($inactiveStables[1]->name));
        $response->assertSee(e($inactiveStables[2]->name));
        $response->assertDontSee(e($activeStable->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_inactive_stables()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('inactive')->create();

        $response = $this->get(route('stables.index', ['state' => 'inactive']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_inactive_stables()
    {
        $stable = factory(Stable::class)->states('inactive')->create();

        $response = $this->get(route('stables.index', ['state' => 'inactive']));

        $response->assertRedirect('/login');
    }
}
