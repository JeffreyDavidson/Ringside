<?php

namespace Tests\Feature\Guest\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group guests
 */
class UnretireTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_unretire_a_tag_team()
    {
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->put(route('tagteams.unretire', $tagteam));

        $response->assertRedirect(route('login'));
    }
}
