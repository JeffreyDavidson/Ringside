<?php

use App\Enums\WrestlerStatus;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WrestlerFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function default_wrestler_is_unemployed()
    {
        $wrestler = Wrestler::factory()->create();

        $this->assertEquals(WrestlerStatus::UNEMPLOYED, $wrestler->status);
    }

    /** @test */
    public function an_unemployed_wrestler_has_zero_employments()
    {
        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->assertEquals(WrestlerStatus::UNEMPLOYED, $wrestler->status);
        $this->assertCount(0, $wrestler->employments);
    }

    /** @test */
    public function a_released_wrestler_has_a_previous_employment()
    {
        $wrestler = Wrestler::factory()->released()->create();

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(WrestlerStatus::RELEASED, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);

            $employment = $wrestler->employments->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertTrue($employment->ended_at->gt($employment->started_at));
        });
    }

    /** @test */
    public function a_future_employed_wrestler_has_an_mployment()
    {
        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(WrestlerStatus::FUTURE_EMPLOYMENT, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);

            $employment = $wrestler->employments->first();

            $this->assertTrue($employment->started_at->isFuture());
            $this->assertNull($employment->ended_at);
        });
    }

    /** @test */
    public function a_bookable_wrestler_has_an_active_employment()
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);

            $employment = $wrestler->employments->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertNull($employment->ended_at);
        });
    }

    /** @test */
    public function a_suspended_wrestler_has_an_active_employment_and_active_suspension()
    {
        $wrestler = Wrestler::factory()->suspended()->create();

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(WrestlerStatus::SUSPENDED, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);
            $this->assertCount(1, $wrestler->suspensions);

            $employment = $wrestler->employments->first();
            $suspension = $wrestler->suspensions->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertNull($employment->ended_at);
            $this->assertTrue($suspension->started_at->isPast());
            $this->assertNull($suspension->ended_at);
            $this->assertTrue($suspension->started_at->gt($employment->started_at));
        });
    }

    /** @test */
    public function a_retired_wrestler_has_a_previous_employment_and_active_retirement()
    {
        $wrestler = Wrestler::factory()->retired()->create();

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);
            $this->assertCount(1, $wrestler->retirements);

            $employment = $wrestler->employments->first();
            $retirement = $wrestler->retirements->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertTrue($employment->ended_at->isPast());
            $this->assertTrue($employment->started_at->lt($employment->ended_at));
            $this->assertTrue($retirement->started_at->isPast());
            $this->assertNull($retirement->ended_at);
            $this->assertTrue($retirement->started_at->eq($employment->ended_at));
        });
    }

    /** @test */
    public function an_injured_wrestler_has_an_active_employment_and_active_suspension()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(WrestlerStatus::INJURED, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);
            $this->assertCount(1, $wrestler->injuries);

            $employment = $wrestler->employments->first();
            $injury = $wrestler->injuries->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertNull($employment->ended_at);
            $this->assertTrue($injury->started_at->isPast());
            $this->assertNull($injury->ended_at);
            $this->assertTrue($injury->started_at->gt($employment->started_at));
        });
    }
}
