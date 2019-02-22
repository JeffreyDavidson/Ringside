<?php

namespace Tests\Feature;

use App\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewRetiredTagTeamsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_retired_tag_teams()
    {
        $this->actAs('administrator');
        $retiredTagTeams = factory(TagTeam::class, 3)->states('retired')->create();
        $activeTagTeam = factory(TagTeam::class)->states('active')->create();

        $response = $this->get(route('tagteams.index', ['state' => 'retired']));

        $response->assertOk();
        $response->assertSee(e($retiredTagTeams[0]->name));
        $response->assertSee(e($retiredTagTeams[1]->name));
        $response->assertSee(e($retiredTagTeams[2]->name));
        $response->assertDontSee(e($activeTagTeam->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_retired_tag_teams()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->get(route('tagteams.index', ['state' => 'retired']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_retired_tag_teams()
    {
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->get(route('tagteams.index', ['state' => 'retired']));

        $response->assertRedirect('/login');
    }
}
