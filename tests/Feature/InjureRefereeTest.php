<?php

namespace Tests\Feature;

use App\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InjureRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_injure_a_referee()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->post(route('referees.injure', $referee));

        $response->assertRedirect(route('referees.index', ['state' => 'injured']));
        $this->assertEquals(today()->toDateTimeString(), $referee->fresh()->injury->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_injure_a_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->create();

        $response = $this->post(route('referees.injure', $referee));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_injure_a_referee()
    {
        $referee = factory(Referee::class)->create();

        $response = $this->post(route('referees.injure', $referee));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_injured_referee_cannot_be_injured_again()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->post(route('referees.injure', $referee));

        $response->assertStatus(403);
    }
}
