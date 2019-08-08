<?php

namespace Tests\Feature\Generic\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
 */
class ReinstateTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_tag_team_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->put(route('tagteams.reinstate', $tagteam));

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_introduction_tag_team_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('pending-introduction')->create();

        $response = $this->put(route('tagteams.reinstate', $tagteam));

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_tag_team_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->put(route('tagteams.reinstate', $tagteam));

        $response->assertForbidden();
    }
}
