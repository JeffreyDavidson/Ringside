<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecoverInjuredWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_recover_an_injured_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->delete(route('wrestlers.recover', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertNotNull($wrestler->fresh()->previousInjury->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_recover_an_injured_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->delete(route('wrestlers.recover', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_recover_an_injured_wrestler()
    {
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->delete(route('wrestlers.recover', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_injured_wrestler_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->delete(route('wrestlers.recover', $wrestler));

        $response->assertStatus(403);
    }
}
