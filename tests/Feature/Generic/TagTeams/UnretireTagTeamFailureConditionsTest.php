<?php

namespace Tests\Feature\Generic\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
 */
class UnretireTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_tag_team_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->put(route('tagteams.unretire', $tagteam));

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_tag_team_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->put(route('tagteams.unretire', $tagteam));

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_introduction_tag_team_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('pending-introduction')->create();

        $response = $this->put(route('tagteams.unretire', $tagteam));

        $response->assertForbidden();
    }
}
