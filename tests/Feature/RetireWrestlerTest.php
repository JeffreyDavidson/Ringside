<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RetireWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_retire_a_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->post(route('wrestler.retire', $wrestler));

        $response->assertRedirect(route('retired-wrestlers.index'));
        $this->assertEquals(today()->toDateTimeString(), $wrestler->fresh()->retirement->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->post(route('wrestler.retire', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_retire_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->post(route('wrestler.retire', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_retired_wrestler_cannot_be_retired_again()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->post(route('wrestler.retire', $wrestler));

        $this->assertCount(1, $wrestler->fresh()->retirements);
    }
}
