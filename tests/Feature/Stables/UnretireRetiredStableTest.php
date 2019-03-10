<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnretireRetiredStableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->delete(route('stables.unretire', $stable));

        $response->assertRedirect(route('stables.index'));

        $this->assertNotNull($stable->fresh()->previousRetirement->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->delete(route('stables.unretire', $stable));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_stable()
    {
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->delete(route('stables.unretire', $stable));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_retired_stable_cannot_unretire()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->delete(route('stables.unretire', $stable));

        $response->assertStatus(403);
    }

    /** @test */
    public function unretiring_a_stable_makes_both_wrestlers_active()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('retired')->create();
        $stable->wrestlers->first()->unretire();

        $response = $this->delete(route('stables.unretire', $stable));

        $response->assertRedirect(route('stables.index'));
        $this->assertCount(1, $stable->wrestlers->filter->isActive());
        $this->assertCount(1, $stable->tagteams->filter->isActive());
    }
}
