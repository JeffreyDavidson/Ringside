<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RetireStableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_retire_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->post(route('stables.retire', $stable));

        $response->assertRedirect(route('stables.index', ['state' => 'retired']));
        $this->assertEquals(today()->toDateTimeString(), $stable->fresh()->retirement->started_at);
    }

    /** @test */
    public function both_wrestlers_are_retired_when_the_stable_retires()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->post(route('stables.retire', $stable));

        $this->assertCount(1, $stable->wrestlers[0]->retirements);
        $this->assertCount(1, $stable->tagteams[0]->retirements);
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->create();

        $response = $this->post(route('stables.retire', $stable));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_retire_a_stable()
    {
        $stable = factory(Stable::class)->create();

        $response = $this->post(route('stables.retire', $stable));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_retired_stable_cannot_be_retired_again()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->post(route('stables.retire', $stable));

        $response->assertStatus(403);
    }
}
