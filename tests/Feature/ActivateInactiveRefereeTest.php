<?php

namespace Tests\Feature;

use App\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivateInactiveRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_activate_an_inactive_referee()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('inactive')->create();

        $response = $this->post(route('referees.activate', $referee));

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) {
            $this->assertTrue($referee->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_activate_an_inactive_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('inactive')->create();

        $response = $this->post(route('referees.activate', $referee));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_activate_an_inactive_referee()
    {
        $referee = factory(Referee::class)->states('inactive')->create();

        $response = $this->post(route('referees.activate', $referee));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_active_referee_cannot_be_activated()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('active')->create();

        $response = $this->post(route('referees.activate', $referee));

        $response->assertStatus(403);
    }
}
