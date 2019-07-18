<?php

namespace Tests\Feature\Admin\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group admins
 */
class ReinstateTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_reinstate_a_suspended_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->put(route('tagteams.reinstate', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertEquals(now()->toDateTimeString(), $tagteam->fresh()->suspensions()->latest()->first()->ended_at);
    }
}
