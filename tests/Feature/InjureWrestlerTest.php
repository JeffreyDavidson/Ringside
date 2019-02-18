<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InjureWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_injure_a_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->post(route('wrestlers.injure', $wrestler));

        $response->assertRedirect(route('wrestlers.index', ['state' => 'injured']));
        $this->assertEquals(today()->toDateTimeString(), $wrestler->fresh()->injury->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_injure_a_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->post(route('wrestlers.injure', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_injure_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->post(route('wrestlers.injure', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_injured_wrestler_cannot_be_injured_again()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->post(route('wrestlers.injure', $wrestler));

        $response->assertStatus(403);
    }
}
