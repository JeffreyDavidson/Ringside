<?php

namespace Tests\Feature\User\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group users
 * @group roster
 */
class UnretireTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_unretire_a_tag_team()
    {
        $this->actAs('basic-user');
        $tagTeam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->unretireRequest($tagTeam);

        $response->assertForbidden();
    }
}
