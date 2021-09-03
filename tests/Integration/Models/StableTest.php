<?php

namespace Tests\Integration\Models;

use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 */
class StableTest extends TestCase
{
    use RefreshDatabase,
        Concerns\RetirableContractTests;

    private $activeStable;
    private $futureActivatedStable;
    private $retiredStable;
    private $unactivatedStable;

    public function setUp(): void
    {
        parent::setUp();

        $this->activeStable = Stable::factory()->active()->create();
        $this->futureActivatedStable = Stable::factory()->withFutureActivation()->create();
        $this->retiredStable = Stable::factory()->retired()->create();
        $this->unactivatedStable = Stable::factory()->unactivated()->create();
    }

    protected function getActivatable()
    {
        return Stable::factory()->active()->create();
    }

    protected function getRetirable()
    {
        return Stable::factory()->retired()->create();
    }

    /**
     * @test
     */
    public function it_can_get_active_stables()
    {
        $activeStables = Stable::active()->get();

        $this->assertCount(1, $activeStables);
        $this->assertCollectionHas($activeStables, $this->activeStable);
        $this->assertCollectionDoesntHave($activeStables, $this->futureActivatedStable);
        $this->assertCollectionDoesntHave($activeStables, $this->retiredStable);
        $this->assertCollectionDoesntHave($activeStables, $this->unactivatedStable);
    }

    /**
     * @test
     */
    public function it_can_get_future_activated_stables()
    {
        $futureActiveStables = Stable::withFutureActivation()->get();

        $this->assertCount(1, $futureActiveStables);
        $this->assertCollectionHas($futureActiveStables, $this->futureActivatedStable);
        $this->assertCollectionDoesntHave($futureActiveStables, $this->activeStable);
        $this->assertCollectionDoesntHave($futureActiveStables, $this->retiredStable);
        $this->assertCollectionDoesntHave($futureActiveStables, $this->unactivatedStable);
    }

    /**
     * @test
     */
    public function it_can_get_retired_stables()
    {
        $retiredStables = Stable::retired()->get();

        $this->assertCount(1, $retiredStables);
        $this->assertCollectionHas($retiredStables, $this->retiredStable);
        $this->assertCollectionDoesntHave($retiredStables, $this->activeStable);
        $this->assertCollectionDoesntHave($retiredStables, $this->futureActivatedStable);
        $this->assertCollectionDoesntHave($retiredStables, $this->unactivatedStable);
    }

    /**
     * @test
     */
    public function it_can_get_unactivated_stables()
    {
        $unactivatedStables = Stable::unactivated()->get();

        $this->assertCount(1, $unactivatedStables);
        $this->assertCollectionHas($unactivatedStables, $this->unactivatedStable);
        $this->assertCollectionDoesntHave($unactivatedStables, $this->activeStable);
        $this->assertCollectionDoesntHave($unactivatedStables, $this->futureActivatedStable);
        $this->assertCollectionDoesntHave($unactivatedStables, $this->retiredStable);
    }
}
