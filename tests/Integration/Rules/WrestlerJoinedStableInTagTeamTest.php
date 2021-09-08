<?php

namespace Tests\Integration\Rules;

use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Rules\WrestlerJoinedStableInTagTeam;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group rules
 */
class WrestlerJoinedStableInTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function a_wrestler_not_on_a_current_tag_team_added_to_the_stable_can_join_a_stable()
    {
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()
            ->hasAttached(Wrestler::factory()->count(2), ['joined_at' => now()->toDateTimeString()])
            ->create();

        $this->assertTrue((new WrestlerJoinedStableInTagTeam([$tagTeam->id], [$wrestler->id]))->passes());
    }

    /**
     * @test
     */
    public function a_wrestler_on_a_current_tag_team_being_added_to_the_stable_cannot_join_a_stable_as_a_wrestler()
    {
        $tagTeam = TagTeam::factory()
            ->hasAttached(Wrestler::factory()->count(2), ['joined_at' => now()->toDateTimeString()])
            ->create();
        $wrestler = $tagTeam->currentWrestlers->first();

        $this->assertFalse((new WrestlerJoinedStableInTagTeam([$tagTeam->id], [$wrestler->id]))->passes());
    }
}
