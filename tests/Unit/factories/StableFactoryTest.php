<?php

use App\Enums\StableStatus;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 * @group factories
 */
class StableFactoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function default_stable_is_unactivated()
    {
        $stable = Stable::factory()->create();

        $this->assertEquals(StableStatus::UNACTIVATED, $stable->status);
    }

    /**
     * @test
     */
    public function an_unactivated_stable_has_zero_activations()
    {
        $stable = Stable::factory()->unactivated()->create();

        $this->assertEquals(StableStatus::UNACTIVATED, $stable->status);
        $this->assertCount(0, $stable->activations);
    }

    /**
     * @test
     */
    public function an_inactive_stable_has_a_previous_activation()
    {
        $stable = Stable::factory()->inactive()->create();

        $this->assertEquals(StableStatus::INACTIVE, $stable->status);
        $this->assertCount(1, $stable->activations);

        $activation = $stable->activations->first();

        $this->assertTrue($activation->started_at->isPast());
        $this->assertTrue($activation->ended_at->gt($activation->started_at));
    }

    /**
     * @test
     */
    public function an_inactive_stable_removes_current_members()
    {
        $stable = Stable::factory()->inactive()->create();

        $this->assertTrue($stable->wrestlers->every(function ($wrestler, $key) {
            return $wrestler->pivot->left_at->isPast();
        }));
        $this->assertTrue($stable->tagTeams->every(function ($tagTeam, $key) {
            return $tagTeam->pivot->left_at->isPast();
        }));
    }

    /**
     * @test
     */
    public function a_future_employed_stable_has_an_mployment()
    {
        $stable = Stable::factory()->withFutureActivation()->create();

        $this->assertEquals(StableStatus::FUTURE_ACTIVATION, $stable->status);
        $this->assertCount(1, $stable->activations);

        $activation = $stable->activations->first();

        $this->assertTrue($activation->started_at->isFuture());
        $this->assertNull($activation->ended_at);
    }

    /**
     * @test
     */
    public function an_active_stable_has_an_active_activation()
    {
        $stable = Stable::factory()->active()->create();

        $this->assertEquals(StableStatus::ACTIVE, $stable->status);
        $this->assertCount(1, $stable->activations);

        $activation = $stable->activations->first();

        $this->assertTrue($activation->started_at->isPast());
        $this->assertNull($activation->ended_at);
    }

    /**
     * @test
     */
    public function a_retired_stable_has_a_previous_activation_and_active_retirement()
    {
        $stable = Stable::factory()->retired()->create();

        $this->assertEquals(StableStatus::RETIRED, $stable->status);
        $this->assertCount(1, $stable->activations);
        $this->assertCount(1, $stable->retirements);

        $activation = $stable->activations->first();
        $retirement = $stable->retirements->first();

        $this->assertTrue($activation->started_at->isPast());
        $this->assertTrue($activation->ended_at->isPast());
        $this->assertTrue($activation->started_at->lt($activation->ended_at));
        $this->assertTrue($retirement->started_at->isPast());
        $this->assertNull($retirement->ended_at);
        $this->assertTrue($retirement->started_at->eq($activation->ended_at));
    }
}
