<?php

namespace Tests\Integration\Factories;

use App\Enums\StableStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\StableFactory;
use Tests\TestCase;

/**
 * @group factories
 */
class StableFactoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up test environment for this class.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        \Event::fake();
    }

    /** @test */
    public function a_stable_always_consists_of_at_least_three_members()
    {
        $stable = StableFactory::new()->create();

        $this->assertCount(3, $stable->members);
    }

    /** @test */
    public function a_stables_active_activation_is_in_the_past()
    {
        $stable = StableFactory::new()->active()->create();

        $this->assertEquals(StableStatus::ACTIVE, $stable->status);
        $this->assertCount(1, $stable->activations);

        $activation = $stable->activations->first();

        $this->assertTrue($activation->started_at->isPast());
        $this->assertNull($activation->ended_at);
    }

    /** @test */
    public function a_active_stable_activated_at_same_current_datetime_employment_as_stable()
    {
        $stable = StableFactory::new()->active()->create();

        $stable->members->each(function ($member) use ($stable) {
            $this->assertEquals(class_basename(get_class($member)).'Status::ACTIVE', $member->status);
            $this->assertCount(1, $member->employments);

            $stableEmployment = $stable->activations[0];
            $memberEmployment = $member->employments[0];

            $this->assertTrue($stableEmployment->started_at->equalTo($memberEmployment->started_at));
            $this->assertNull($stableEmployment->ended_at);
        });
    }

    /** @test */
    public function a_future_employment_is_in_the_future()
    {
        $stable = StableFactory::new()->pendingEmployment()->create();

        $this->assertEquals(StableStatus::FUTURE_EMPLOYMENT, $stable->status);
        $this->assertCount(1, $stable->activations);

        $employment = $stable->activations->first();

        $this->assertTrue($employment->started_at->isFuture());
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_future_employment_stable_employs_at_same_current_datetime_as_stable()
    {
        $stable = StableFactory::new()->pendingEmployment()->create();

        $stable->wrestlers->each(function ($wrestler) use ($stable) {
            $this->assertEquals(WrestlerStatus::FUTURE_EMPLOYMENT, $wrestler->status);
            $this->assertCount(1, $wrestler->activations);

            $stableEmployment = $stable->activations[0];
            $wrestlerEmployment = $wrestler->activations[0];

            $this->assertTrue($stableEmployment->started_at->equalTo($wrestlerEmployment->started_at));
            $this->assertNull($wrestlerEmployment->ended_at);
        });
    }

    /** @test */
    public function a_stables_released_employment_in_the_past()
    {
        $stable = StableFactory::new()->released()->create();

        $this->assertEquals(StableStatus::RELEASED, $stable->status);
        $this->assertCount(1, $stable->activations);

        $employment = $stable->activations->first();

        $this->assertTrue($employment->started_at->isPast());
        $this->assertTrue($employment->ended_at->isPast());

        $this->assertTrue($employment->started_at->lt($employment->ended_at));
    }

    /** @test */
    public function wrestlers_are_released_at_same_time_as_stable()
    {
        $stable = StableFactory::new()->released()->create();

        $stable->wrestlers->each(function ($wrestler) use ($stable) {
            $this->assertEquals(WrestlerStatus::RELEASED, $wrestler->status);
            $this->assertCount(1, $wrestler->activations);

            $stableEmployment = $stable->activations[0];
            $wrestlerEmployment = $wrestler->activations[0];

            $this->assertTrue($stableEmployment->started_at->equalTo($wrestlerEmployment->started_at));
            $this->assertTrue($stableEmployment->ended_at->equalTo($wrestlerEmployment->ended_at));
        });
    }

    /** @test */
    public function a_stable_suspension_is_started_after_their_employment_starts()
    {
        $stable = StableFactory::new()->suspended()->create();

        $this->assertEquals(StableStatus::SUSPENDED, $stable->status);
        $this->assertCount(1, $stable->activations);
        $this->assertCount(1, $stable->suspensions);

        $employment = $stable->activations->first();
        $suspension = $stable->suspensions->first();

        $this->assertTrue($suspension->started_at->gt($employment->started_at));
    }

    /** @test */
    public function wrestlers_are_suspended_at_same_time_as_stable()
    {
        $stable = StableFactory::new()->suspended()->create();

        $stable->wrestlers->each(function ($wrestler) use ($stable) {
            $this->assertEquals(WrestlerStatus::SUSPENDED, $wrestler->status);
            $this->assertCount(1, $wrestler->activations);
            $this->assertCount(1, $wrestler->suspensions);

            $stableEmployment = $stable->activations[0];
            $stableSuspension = $stable->suspensions[0];
            $wrestlerEmployment = $wrestler->activations[0];
            $wrestlerSuspension = $wrestler->suspensions[0];

            $this->assertTrue($stableEmployment->started_at->equalTo($wrestlerEmployment->started_at));
            $this->assertTrue($stableSuspension->started_at->equalTo($wrestlerSuspension->started_at));
        });
    }

    /** @test */
    public function a_stables_retirement_is_started_after_their_employment_starts()
    {
        $stable = StableFactory::new()->retired()->create();

        $this->assertEquals(StableStatus::RETIRED, $stable->status);
        $this->assertCount(1, $stable->activations);
        $this->assertCount(1, $stable->retirements);

        $employment = $stable->activations->first();
        $retirement = $stable->retirements->first();

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNotNull($employment->ended_at);

        $this->assertTrue($employment->started_at->lt($retirement->started_at));
    }

    /** @test */
    public function wrestlers_are_retired_at_same_time_as_stable()
    {
        $stable = StableFactory::new()->retired()->create();

        $stable->wrestlers->each(function ($wrestler) use ($stable) {
            $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            $this->assertCount(1, $wrestler->activations);
            $this->assertCount(1, $wrestler->retirements);

            $stableEmployment = $stable->activations[0];
            $wrestlerEmployment = $wrestler->activations[0];
            $stableRetirement = $stable->retirements[0];
            $wrestlerRetirement = $wrestler->retirements[0];

            $this->assertTrue($stableEmployment->started_at->equalTo($wrestlerEmployment->started_at));
            $this->assertTrue($stableEmployment->ended_at->equalTo($wrestlerEmployment->ended_at));
            $this->assertTrue($stableRetirement->started_at->equalTo($wrestlerRetirement->started_at));
        });
    }

    /** @test */
    public function an_unemployed_stable_has_no_activations()
    {
        $stable = StableFactory::new()->unemployed()->create();

        $this->assertEquals(StableStatus::UNEMPLOYED, $stable->status);
        $this->assertCount(0, $stable->activations);
    }

    /** @test */
    public function wrestlers_are_unemployed_for_an_unemployed_stable()
    {
        $stable = StableFactory::new()->unemployed()->create();

        $stable->wrestlers->each(function ($wrestler) use ($stable) {
            $this->assertEquals(WrestlerStatus::UNEMPLOYED, $wrestler->status);
            $this->assertCount(0, $wrestler->activations);
        });
    }
}
