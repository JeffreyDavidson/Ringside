<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group roster
 */
class StableObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_stable_has_a_default_status_of_pending_employment()
    {
        $stable = factory(Stable::class)->create();

        $this->assertEquals('pending-employment', $stable->status);
    }

    /** @test */
    public function an_employed_stable_with_a_current_retirement_has_a_status_of_retired()
    {
        $stable = factory(Stable::class)->states('retired')->create();

        $this->assertEquals('retired', $stable->status);
    }

    /** @test */
    public function a_stable_with_a_current_suspension_has_a_status_of_suspended()
    {
        $stable = factory(Stable::class)->states('suspended')->create();

        $this->assertEquals('suspended', $stable->status);
    }

    /** @test */
    public function a_stable_with_a_current_injury_has_a_status_of_injured()
    {
        $stable = factory(Stable::class)->states('injured')->create();

        $this->assertEquals('injured', $stable->status);
    }

    /** @test */
    public function a_stable_employed_at_the_current_time_or_in_the_past_without_being_currently_injured_or_retired_or_suspended_has_a_status_of_bookable()
    {
        $stable = factory(Stable::class)->states('employed')->create();

        $this->assertEquals('bookable', $stable->status);
    }
}
