<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class SuspendTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_suspend_a_bookable_tag_team()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $response = $this->suspendRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertEquals($now->toDateTimeString(), $tagTeam->fresh()->currentSuspension->started_at);
    }
}
