<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewSuspendedStablesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_suspended_stables()
    {
        $this->actAs('administrator');
        $suspendedStables = factory(Stable::class, 3)->states('suspended')->create();
        $activeStable = factory(Stable::class)->states('active')->create();

        $response = $this->get(route('stables.index', ['state' => 'suspended']));

        $response->assertOk();
        $response->assertSee(e($suspendedStables[0]->name));
        $response->assertSee(e($suspendedStables[1]->name));
        $response->assertSee(e($suspendedStables[2]->name));
        $response->assertDontSee(e($activeStable->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_suspended_stables()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('suspended')->create();

        $response = $this->get(route('stables.index', ['state' => 'suspended']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_suspended_stables()
    {
        $stable = factory(Stable::class)->states('suspended')->create();

        $response = $this->get(route('stables.index', ['state' => 'suspended']));

        $response->assertRedirect('/login');
    }
}
