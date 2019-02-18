<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewInactiveWrestlersListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_inactive_wrestlers()
    {
        $this->actAs('administrator');
        $inactiveWrestlers = factory(Wrestler::class, 3)->states('inactive')->create();
        $activeWrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'inactive']));

        $response->assertOk();
        $response->assertSee(e($inactiveWrestlers[0]->name));
        $response->assertSee(e($inactiveWrestlers[1]->name));
        $response->assertSee(e($inactiveWrestlers[2]->name));
        $response->assertDontSee(e($activeWrestler->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_active_wrestlers()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'inactive']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_active_wrestlers()
    {
        $wrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'inactive']));

        $response->assertRedirect('/login');
    }
}
