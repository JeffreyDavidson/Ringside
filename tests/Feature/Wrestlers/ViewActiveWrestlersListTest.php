<?php

namespace Tests\Feature\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewActiveWrestlersListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_active_wrestlers()
    {
        $this->actAs('administrator');
        $activeWrestlers = factory(Wrestler::class, 3)->states('active')->create();
        $inactiveWrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->get(route('wrestlers.index'));

        $response->assertOk();
        $response->assertSee(e($activeWrestlers[0]->name));
        $response->assertSee(e($activeWrestlers[1]->name));
        $response->assertSee(e($activeWrestlers[2]->name));
        $response->assertDontSee(e($inactiveWrestler->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_active_wrestlers()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->get(route('wrestlers.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_active_wrestlers()
    {
        $wrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->get(route('wrestlers.index'));

        $response->assertRedirect('/login');
    }
}
