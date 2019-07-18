<?php

namespace Tests\Feature\Generic\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
 */
class RetireTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_already_retired_tag_team_cannot_be_retired()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->put(route('tagteams.retire', $tagteam));

        $response->assertForbidden();
    }
}
