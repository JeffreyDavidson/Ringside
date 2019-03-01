<?php

namespace Tests\Feature\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewInjuredWrestlersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_injured_wrestlers()
    {
        $this->actAs('administrator');
        $injuredWrestlers = factory(Wrestler::class, 3)->states('injured')->create();
        $activeWrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'injured']));

        $response->assertOk();
        $response->assertSee(e($injuredWrestlers[0]->name));
        $response->assertSee(e($injuredWrestlers[1]->name));
        $response->assertSee(e($injuredWrestlers[2]->name));
        $response->assertDontSee(e($activeWrestler->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_injured_wrestlers()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'injured']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_injured_wrestlers()
    {
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'injured']));

        $response->assertRedirect('/login');
    }
}
