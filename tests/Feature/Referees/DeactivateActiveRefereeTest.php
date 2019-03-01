<?php

namespace Tests\Feature\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeactivateActiveRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_deactivate_an_active_referee()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('active')->create();

        $response = $this->post(route('referees.deactivate', $referee));

        $response->assertRedirect(route('referees.index', ['state' => 'inactive']));
        tap($referee->fresh(), function ($referee) {
            $this->assertFalse($referee->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_deactivate_an_active_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('active')->create();

        $response = $this->post(route('referees.deactivate', $referee));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_deactivate_an_active_referee()
    {
        $referee = factory(Referee::class)->states('active')->create();

        $response = $this->post(route('referees.deactivate', $referee));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_inactive_referee_cannot_be_deactivated()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('inactive')->create();

        $response = $this->post(route('referees.deactivate', $referee));

        $response->assertStatus(403);
    }
}
