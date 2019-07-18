<?php

namespace Tests\Feature\User\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group users
 */
class DeleteTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_delete_a_tagteam()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->delete(route('tagteams.destroy', $tagteam));

        $response->assertForbidden();
    }
}
