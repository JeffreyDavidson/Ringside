<?php

namespace Tests\Feature\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeactivateActiveWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_deactivate_an_active_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->post(route('wrestlers.deactivate', $wrestler));

        $response->assertRedirect(route('wrestlers.index', ['state' => 'inactive']));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertFalse($wrestler->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_deactivate_an_active_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->post(route('wrestlers.deactivate', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_deactivate_an_active_wrestler()
    {
        $wrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->post(route('wrestlers.deactivate', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_inactive_wrestler_cannot_be_deactivated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->post(route('wrestlers.deactivate', $wrestler));

        $response->assertStatus(403);
    }
}
