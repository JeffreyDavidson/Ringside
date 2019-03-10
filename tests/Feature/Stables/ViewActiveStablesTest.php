<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewActiveStablesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_active_stables()
    {
        $this->actAs('administrator');
        $activeStables = factory(Stable::class, 3)->states('active')->create();
        $inactiveStable = factory(Stable::class)->states('inactive')->create();

        $response = $this->get(route('stables.index'));

        $response->assertOk();
        $response->assertSee(e($activeStables[0]->name));
        $response->assertSee(e($activeStables[1]->name));
        $response->assertSee(e($activeStables[2]->name));
        $response->assertDontSee(e($inactiveStable->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_active_stables()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Stable::class)->states('active')->create();

        $response = $this->get(route('stables.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_active_stables()
    {
        $wrestler = factory(Stable::class)->states('active')->create();

        $response = $this->get(route('stables.index'));

        $response->assertRedirect('/login');
    }
}
