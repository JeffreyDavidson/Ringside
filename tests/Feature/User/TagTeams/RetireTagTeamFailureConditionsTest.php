<?php

namespace Tests\Feature\User\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group users
 */
class RetireTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_retire_a_tag_team()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->put(route('tagteams.retire', $tagteam));

        $response->assertForbidden();
    }
}
