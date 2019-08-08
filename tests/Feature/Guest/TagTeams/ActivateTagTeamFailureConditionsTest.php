<?php

namespace Tests\Feature\Guest\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group guests
 */
class ActivateTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_activate_an_pending_introduction_tag_team()
    {
        $tagteam = factory(TagTeam::class)->states('pending-introduction')->create();

        $response = $this->put(route('tagteams.activate', $tagteam));

        $response->assertRedirect(route('login'));
    }
}
