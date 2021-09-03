<?php

namespace Tests\Integration\Rules;

use App\Models\Stable;
use App\Models\Wrestler;
use App\Rules\WrestlerCanJoinStable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group rules
 */
class WrestlerCanJoinStableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function an_unactivated_tag_team_can_join_an_unactivated_stable()
    {
        $stable = Stable::factory()->unactivated()->create();
        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->assertTrue((new WrestlerCanJoinStable($stable))->passes(null, $wrestler->id));
    }

    /**
     * @test
     */
    public function a_tag_team_in_an_active_stable_cannot_join_another_ctive_stable()
    {
        $stableA = Stable::factory()->active()->create();
        $stableB = Stable::factory()->active()->create();

        $this->assertFalse((new WrestlerCanJoinStable($stableB))->passes(null, $stableA->currentWrestlers->first()->id));
    }
}
