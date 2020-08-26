<?php

namespace Tests\Unit\Observers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\StableFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 * @group observers
 */
class StableObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_stable_has_a_default_status_of_pending_employment()
    {
        $stable = StableFactory::new()->create();

        $this->assertEquals('pending-activation', $stable->status);
    }

    /** @test */
    public function an_employed_stable_with_a_current_retirement_has_a_status_of_retired()
    {
        $stable = StableFactory::new()->retired()->create();

        $this->assertEquals('retired', $stable->status);
    }

    /** @test */
    public function a_stable_with_a_current_suspension_has_a_status_of_suspended()
    {
        $stable = StableFactory::new()->suspended()->create();

        $this->assertEquals('suspended', $stable->status);
    }

    /** @test */
    public function a_stable_employed_at_the_current_time_or_in_the_past_without_being_currently_injured_or_retired_or_suspended_has_a_status_of_bookable()
    {
        $stable = StableFactory::new()->activated()->create();

        $this->assertEquals('bookable', $stable->status);
    }
}
