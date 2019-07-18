<?php

namespace Tests\Feature\Guest\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group guests
 */
class RetireTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_retire_a_tag_team()
    {
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->put(route('tagteams.retire', $tagteam));

        $response->assertRedirect(route('login'));
    }
}
