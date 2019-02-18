<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewSuspendedWrestlersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_suspended_wrestlers()
    {
        $this->actAs('administrator');
        $suspendedWrestlers = factory(Wrestler::class, 3)->states('suspended')->create();
        $activeWrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'suspended']));

        $response->assertOk();
        $response->assertSee(e($suspendedWrestlers[0]->name));
        $response->assertSee(e($suspendedWrestlers[1]->name));
        $response->assertSee(e($suspendedWrestlers[2]->name));
        $response->assertDontSee(e($activeWrestler->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_suspended_wrestlers()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'suspended']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_suspended_wrestlers()
    {
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'suspended']));

        $response->assertRedirect('/login');
    }
}
