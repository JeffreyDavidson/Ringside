<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Models\TagTeam;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-rosters
 */
class SuspendControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_suspends_a_tag_team_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->bookable()->create();

        $response = $this->suspendRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertEquals($now->toDateTimeString(), $tagTeam->fresh()->currentSuspension->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->suspendRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_suspend_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->suspendRequest($tagTeam)->assertRedirect(route('login'));
    }
}
