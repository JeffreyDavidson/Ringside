<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class ReinstateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_reinstates_a_tag_team_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = TagTeamFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertEquals($now->toDateTimeString(), $tagTeam->fresh()->suspensions()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeamFactory::new()->create();

        $this->reinstateRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->create();

        $this->reinstateRequest($tagTeam)->assertRedirect(route('login'));
    }
}
