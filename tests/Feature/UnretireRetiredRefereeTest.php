<?php

namespace Tests\Feature;

use App\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnretireRetiredRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_referee()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->delete(route('referees.unretire', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertNotNull($referee->fresh()->previousRetirement->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->delete(route('referees.unretire', $referee));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_referee()
    {
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->delete(route('referees.unretire', $referee));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_retired_referee_cannot_unretire()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->delete(route('referees.unretire', $referee));

        $response->assertStatus(403);
    }
}
