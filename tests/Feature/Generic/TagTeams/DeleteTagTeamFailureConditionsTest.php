<?php

namespace Tests\Feature\Generic\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generic
 */
class DeleteTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_already_deleted_tag_team_cannot_be_deleted()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create()->delete();

        $response = $this->delete(route('tagteams.destroy', $tagteam));

        $response->assertNotFound();
    }
}
