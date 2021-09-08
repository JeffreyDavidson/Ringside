<?php

namespace Tests\Integration\Rules;

use App\Models\Employment;
use App\Models\Stable;
use App\Models\Wrestler;
use App\Rules\WrestlerCanJoinStable;
use Carbon\Carbon;
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
    public function a_wrestler_not_in_the_database_cannot_join_an_unactivated_stable()
    {
        $stable = Stable::factory()->unactivated()->create();

        $this->assertFalse((new WrestlerCanJoinStable($stable))->passes(null, 99999));
    }

    /**
     * @test
     */
    public function a_wrestler_cannot_be_a_member_of_multiple_active_stable()
    {
        $stableA = Stable::factory()->active()->create();
        $stableB = Stable::factory()->active()->create();

        $this->assertFalse(
            (new WrestlerCanJoinStable($stableB))->passes(null, $stableA->currentWrestlers->first()->id)
        );
    }

    /**
     * @test
     */
    public function a_wrestler_with_future_employment_cannot_join_a_stable_when_wrestler_start_date_is_after_the_stable_start_date()
    {
        $wrestler = Wrestler::factory()->has(Employment::factory()->started(Carbon::parse('+2 weeks')))->create();
        $stable = Stable::factory()->create();

        $this->assertFalse(
            (new WrestlerCanJoinStable($stable, Carbon::parse('+1 week')))->passes(null, $wrestler->id)
        );
    }
}
