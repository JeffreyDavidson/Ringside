<?php

namespace Tests\Feature;

use App\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecoverInjuredRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_recover_an_injured_referee()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->delete(route('referees.recover', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertNotNull($referee->fresh()->previousInjury->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_recover_an_injured_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->delete(route('referees.recover', $referee));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_recover_an_injured_referee()
    {
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->delete(route('referees.recover', $referee));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_injured_referee_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->delete(route('referees.recover', $referee));

        $response->assertStatus(403);
    }
}
