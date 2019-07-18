<?php

namespace Tests\Feature\Guest\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group guests
 */
class ViewTagTeamBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_a_tag_team_profile()
    {
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->get(route('tagteams.show', ['tagteam' => $tagteam]));

        $response->assertRedirect(route('login'));
    }
}
