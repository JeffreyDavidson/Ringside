<?php

namespace Tests\Feature\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewInactiveTagTeamsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_inactive_tag_teams()
    {
        $this->actAs('administrator');
        $inactiveTagTeams = factory(TagTeam::class, 3)->states('inactive')->create();
        $activeTagTeam = factory(TagTeam::class)->states('active')->create();

        $response = $this->get(route('tagteams.index', ['state' => 'inactive']));

        $response->assertOk();
        $response->assertSee(e($inactiveTagTeams[0]->name));
        $response->assertSee(e($inactiveTagTeams[1]->name));
        $response->assertSee(e($inactiveTagTeams[2]->name));
        $response->assertDontSee(e($activeTagTeam->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_inactive_tag_teams()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->states('inactive')->create();

        $response = $this->get(route('tagteams.index', ['state' => 'inactive']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_inactive_tag_teams()
    {
        $tagteam = factory(TagTeam::class)->states('inactive')->create();

        $response = $this->get(route('tagteams.index', ['state' => 'inactive']));

        $response->assertRedirect('/login');
    }
}
