<?php

namespace Tests\Feature;

use App\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewSuspendedTagTeamsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_suspended_tag_teams()
    {
        $this->actAs('administrator');
        $suspendedTagTeams = factory(TagTeam::class, 3)->states('suspended')->create();
        $activeTagTeam = factory(TagTeam::class)->states('active')->create();

        $response = $this->get(route('tagteams.index', ['state' => 'suspended']));

        $response->assertOk();
        $response->assertSee(e($suspendedTagTeams[0]->name));
        $response->assertSee(e($suspendedTagTeams[1]->name));
        $response->assertSee(e($suspendedTagTeams[2]->name));
        $response->assertDontSee(e($activeTagTeam->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_suspended_tag_teams()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->get(route('tagteams.index', ['state' => 'suspended']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_suspended_tag_teams()
    {
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->get(route('tagteams.index', ['state' => 'suspended']));

        $response->assertRedirect('/login');
    }
}
