<?php

namespace Tests\Feature\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewRetiredWrestlersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_retired_wrestlers()
    {
        $this->actAs('administrator');
        $retiredWrestlers = factory(Wrestler::class, 3)->states('retired')->create();
        $activeWrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'retired']));

        $response->assertOk();
        $response->assertSee(e($retiredWrestlers[0]->name));
        $response->assertSee(e($retiredWrestlers[1]->name));
        $response->assertSee(e($retiredWrestlers[2]->name));
        $response->assertDontSee(e($activeWrestler->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_retired_wrestlers()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'retired']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_retired_wrestlers()
    {
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->get(route('wrestlers.index', ['state' => 'retired']));

        $response->assertRedirect('/login');
    }
}
