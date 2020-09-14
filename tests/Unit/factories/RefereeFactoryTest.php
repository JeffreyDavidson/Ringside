<?php

use App\Enums\RefereeStatus;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group srm
 * @group roster
 * @group factories
 */
class RefereeFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function default_referee_is_unemployed()
    {
        $referee = Referee::factory()->create();

        $this->assertEquals(RefereeStatus::UNEMPLOYED, $referee->status);
    }

    /** @test */
    public function an_unemployed_referee_has_zero_employments()
    {
        $referee = Referee::factory()->unemployed()->create();

        $this->assertEquals(RefereeStatus::UNEMPLOYED, $referee->status);
        $this->assertCount(0, $referee->employments);
    }

    /** @test */
    public function a_released_referee_has_a_previous_employment()
    {
        $referee = Referee::factory()->released()->create();

        tap($referee->fresh(), function ($referee) {
            $this->assertEquals(RefereeStatus::RELEASED, $referee->status);
            $this->assertCount(1, $referee->employments);

            $employment = $referee->employments->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertTrue($employment->ended_at->gt($employment->started_at));
        });
    }

    /** @test */
    public function a_future_employed_referee_has_an_mployment()
    {
        $referee = Referee::factory()->withFutureEmployment()->create();

        tap($referee->fresh(), function ($referee) {
            $this->assertEquals(RefereeStatus::FUTURE_EMPLOYMENT, $referee->status);
            $this->assertCount(1, $referee->employments);

            $employment = $referee->employments->first();

            $this->assertTrue($employment->started_at->isFuture());
            $this->assertNull($employment->ended_at);
        });
    }

    /** @test */
    public function a_bookable_referee_has_an_active_employment()
    {
        $referee = Referee::factory()->bookable()->create();

        tap($referee->fresh(), function ($referee) {
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
            $this->assertCount(1, $referee->employments);

            $employment = $referee->employments->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertNull($employment->ended_at);
        });
    }

    /** @test */
    public function a_suspended_referee_has_an_active_employment_and_active_suspension()
    {
        $referee = Referee::factory()->suspended()->create();

        tap($referee->fresh(), function ($referee) {
            $this->assertEquals(RefereeStatus::SUSPENDED, $referee->status);
            $this->assertCount(1, $referee->employments);
            $this->assertCount(1, $referee->suspensions);

            $employment = $referee->employments->first();
            $suspension = $referee->suspensions->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertNull($employment->ended_at);
            $this->assertTrue($suspension->started_at->isPast());
            $this->assertNull($suspension->ended_at);
            $this->assertTrue($suspension->started_at->gt($employment->started_at));
        });
    }

    /** @test */
    public function a_retired_referee_has_a_previous_employment_and_active_retirement()
    {
        $referee = Referee::factory()->retired()->create();

        tap($referee->fresh(), function ($referee) {
            $this->assertEquals(RefereeStatus::RETIRED, $referee->status);
            $this->assertCount(1, $referee->employments);
            $this->assertCount(1, $referee->retirements);

            $employment = $referee->employments->first();
            $retirement = $referee->retirements->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertTrue($employment->ended_at->isPast());
            $this->assertTrue($employment->started_at->lt($employment->ended_at));
            $this->assertTrue($retirement->started_at->isPast());
            $this->assertNull($retirement->ended_at);
            $this->assertTrue($retirement->started_at->eq($employment->ended_at));
        });
    }

    /** @test */
    public function an_injured_referee_has_an_active_employment_and_active_suspension()
    {
        $referee = Referee::factory()->injured()->create();

        tap($referee->fresh(), function ($referee) {
            $this->assertEquals(RefereeStatus::INJURED, $referee->status);
            $this->assertCount(1, $referee->employments);
            $this->assertCount(1, $referee->injuries);

            $employment = $referee->employments->first();
            $injury = $referee->injuries->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertNull($employment->ended_at);
            $this->assertTrue($injury->started_at->isPast());
            $this->assertNull($injury->ended_at);
            $this->assertTrue($injury->started_at->gt($employment->started_at));
        });
    }
}
