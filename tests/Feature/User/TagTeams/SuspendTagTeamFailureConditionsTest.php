<?php

namespace Tests\Feature\User\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group users
 */
class SuspendTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_suspend_a_tag_team()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->put(route('tagteams.suspend', $tagteam));

        $response->assertForbidden();
    }
}
