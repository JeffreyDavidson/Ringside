<?php

namespace Tests\Feature\TagTeams;

use Carbon\Carbon;
use App\Enums\Role;
use Tests\TestCase;
use Tests\Factories\TagTeamFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group roster
 */
class UnretireTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_tag_team()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->retired()->create();

        $response = $this->unretireRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertEquals($now->toDateTimeString(), $tagTeam->fresh()->retirements()->latest()->first()->ended_at);
    }

    /** @test */
    public function unretiring_a_tag_team_makes_both_wrestlers_bookable()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->retired()->create();

        $response = $this->unretireRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertCount(2, $tagTeam->fresh()->currentWrestlers->filter->isBookable());
    }
}
