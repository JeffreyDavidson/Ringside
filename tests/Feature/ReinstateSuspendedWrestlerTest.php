<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReinstateSuspendedWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_reinstate_a_suspended_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->delete(route('wrestlers.reinstate', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertNotNull($wrestler->fresh()->previousSuspension->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_suspended_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->delete(route('wrestlers.reinstate', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_suspended_wrestler()
    {
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->delete(route('wrestlers.reinstate', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_suspended_wrestler_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->delete(route('wrestlers.reinstate', $wrestler));

        $response->assertStatus(403);
    }
}
