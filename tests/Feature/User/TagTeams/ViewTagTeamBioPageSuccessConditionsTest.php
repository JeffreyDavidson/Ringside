<?php

namespace Tests\Feature\User\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group users
 */
class ViewTagTeamBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_can_view_their_tag_team_profile()
    {
        $signedInUser = $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create(['user_id' => $signedInUser->id]);

        $response = $this->get(route('tagteams.show', $tagteam));

        $response->assertOk();
    }
}
