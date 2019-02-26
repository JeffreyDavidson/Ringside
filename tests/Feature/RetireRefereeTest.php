<?php

namespace Tests\Feature;

use App\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RetireRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_retire_a_referee()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->post(route('referees.retire', $referee));

        $response->assertRedirect(route('referees.index', ['state' => 'retired']));
        $this->assertEquals(today()->toDateTimeString(), $referee->fresh()->retirement->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->create();

        $response = $this->post(route('referees.retire', $referee));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_retire_a_referee()
    {
        $referee = factory(Referee::class)->create();

        $response = $this->post(route('referees.retire', $referee));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_retired_referee_cannot_be_retired_again()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->post(route('referees.retire', $referee));

        $response->assertStatus(403);
    }
}
