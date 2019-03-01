<?php

namespace Tests\Feature\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivateInactiveWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_activate_an_inactive_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->post(route('wrestlers.activate', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertTrue($wrestler->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_activate_an_inactive_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->post(route('wrestlers.activate', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_activate_an_inactive_wrestler()
    {
        $wrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->post(route('wrestlers.activate', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_active_wrestler_cannot_be_activated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->post(route('wrestlers.activate', $wrestler));

        $response->assertStatus(403);
    }
}
