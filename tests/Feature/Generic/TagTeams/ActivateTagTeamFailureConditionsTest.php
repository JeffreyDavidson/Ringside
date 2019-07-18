<?php

namespace Tests\Feature\Generic\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
 */
class ActivateTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_tag_team_cannot_be_activated()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->put(route('tagteams.activate', $tagteam));

        $response->assertForbidden();
    }
}
