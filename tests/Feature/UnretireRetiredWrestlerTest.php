<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnretireRetiredWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->delete(route('wrestlers.unretire', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertNotNull($wrestler->fresh()->previousRetirement->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->delete(route('wrestlers.unretire', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_wrestler()
    {
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->delete(route('wrestlers.unretire', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_retired_wrestler_cannot_unretire()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->delete(route('wrestlers.unretire', $wrestler));

        $response->assertStatus(403);
    }
}
