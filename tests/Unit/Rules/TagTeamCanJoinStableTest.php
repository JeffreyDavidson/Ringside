<?php

namespace Tests\Unit\Rules;

use App\Models\Stable;
use App\Models\TagTeam;
use App\Rules\TagTeamCanJoinStable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group rules
 */
class TagTeamCanJoinStableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_unactivated_tag_team_can_join_an_unactivated_stable()
    {
        $stable = Stable::factory()->unactivated()->create();
        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->assertTrue((new TagTeamCanJoinStable($stable))->passes(null, $tagTeam->id));
    }

    /** @test */
    public function a_tag_team_in_an_active_stable_cannot_join_another_ctive_stable()
    {
        $stableA = Stable::factory()->active()->create();
        $stableB = Stable::factory()->active()->create();

        $this->assertFalse((new TagTeamCanJoinStable($stableB))->passes(null, $stableA->currentTagTeams->first()->id));
    }
}
