<?php

namespace Tests\Feature\Admin\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group admins
 */
class DeleteTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->delete(route('tagteams.destroy', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertSoftDeleted('tag_teams', ['id' => $tagteam->id]);
    }
}
