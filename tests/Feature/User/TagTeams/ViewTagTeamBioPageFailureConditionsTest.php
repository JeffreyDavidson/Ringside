<?php

namespace Tests\Feature\User\TagTeams;

use App\Models\User;
use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group users
 * @group roster
 */
class ViewTagTeamBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_another_users_tag_team_profile()
    {
        $this->actAs('basic-user');
        $otherUser = factory(User::class)->create();
        $tagTeam = factory(TagTeam::class)->create(['user_id' => $otherUser->id]);

        $response = $this->showRequest($tagTeam);

        $response->assertForbidden();
    }
}
