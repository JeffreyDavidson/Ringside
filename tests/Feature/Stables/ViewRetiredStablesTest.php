<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewRetiredStablesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_retired_stables()
    {
        $this->actAs('administrator');
        $retiredStables = factory(Stable::class, 3)->states('retired')->create();
        $activeStable = factory(Stable::class)->states('active')->create();

        $response = $this->get(route('stables.index', ['state' => 'retired']));

        $response->assertOk();
        $response->assertSee(e($retiredStables[0]->name));
        $response->assertSee(e($retiredStables[1]->name));
        $response->assertSee(e($retiredStables[2]->name));
        $response->assertDontSee(e($activeStable->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_retired_stables()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->get(route('stables.index', ['state' => 'retired']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_retired_stables()
    {
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->get(route('stables.index', ['state' => 'retired']));

        $response->assertRedirect('/login');
    }
}
