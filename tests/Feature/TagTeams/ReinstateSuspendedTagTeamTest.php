<?php

namespace Tests\Feature\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReinstateSuspendedTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_reinstate_a_suspended_tag_team()
    {
        $this->withoutExceptionHandling();
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->delete(route('tagteams.reinstate', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertNotNull($tagteam->fresh()->previousSuspension->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_suspended_tag_team()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->delete(route('tagteams.reinstate', $tagteam));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_suspended_tag_team()
    {
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->delete(route('tagteams.reinstate', $tagteam));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_suspended_tag_team_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->delete(route('tagteams.reinstate', $tagteam));

        $response->assertStatus(403);
    }
}
