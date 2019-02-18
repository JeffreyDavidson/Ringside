<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SuspendWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_suspend_a_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->post(route('wrestlers.suspend', $wrestler));

        $response->assertRedirect(route('wrestlers.index', ['state' => 'suspended']));
        $this->assertEquals(today()->toDateTimeString(), $wrestler->fresh()->suspension->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->post(route('wrestlers.suspend', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_suspend_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->post(route('wrestlers.suspend', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_suspended_wrestler_cannot_be_suspended_again()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->post(route('wrestlers.suspend', $wrestler));

        $response->assertStatus(403);
    }
}
