<?php

namespace Tests\Feature;

use App\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewActiveTagTeamsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_active_tag_teams()
    {
        $this->actAs('administrator');
        $activeTagTeams = factory(TagTeam::class, 3)->states('active')->create();
        $inactiveTagTeam = factory(TagTeam::class)->states('inactive')->create();

        $response = $this->get(route('tagteams.index'));

        $response->assertOk();
        $response->assertSee(e($activeTagTeams[0]->name));
        $response->assertSee(e($activeTagTeams[1]->name));
        $response->assertSee(e($activeTagTeams[2]->name));
        $response->assertDontSee(e($inactiveTagTeam->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_active_tag_teams()
    {
        $this->actAs('basic-user');
        $wrestler = factory(TagTeam::class)->states('active')->create();

        $response = $this->get(route('tagteams.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_active_tag_teams()
    {
        $wrestler = factory(TagTeam::class)->states('active')->create();

        $response = $this->get(route('tagteams.index'));

        $response->assertRedirect('/login');
    }
}
