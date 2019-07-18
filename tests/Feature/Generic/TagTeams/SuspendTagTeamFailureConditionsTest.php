<?php

namespace Tests\Feature\Generic\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
 */
class SuspendTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_already_suspended_tag_team_cannot_be_suspended()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->put(route('tagteams.suspend', $tagteam));

        $response->assertForbidden();
    }
}
