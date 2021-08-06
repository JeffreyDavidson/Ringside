<?php

use App\Enums\ManagerStatus;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group srm
 * @group roster
 * @group factories
 */
class ManagerFactoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function default_manager_is_unemployed()
    {
        $manager = Manager::factory()->create();

        $this->assertEquals(ManagerStatus::UNEMPLOYED, $manager->status);
    }

    /**
     * @test
     */
    public function an_unemployed_manager_has_zero_employments()
    {
        $manager = Manager::factory()->unemployed()->create();

        $this->assertEquals(ManagerStatus::UNEMPLOYED, $manager->status);
        $this->assertCount(0, $manager->employments);
    }

    /**
     * @test
     */
    public function a_released_manager_has_a_previous_employment()
    {
        $manager = Manager::factory()->released()->create();

        $this->assertEquals(ManagerStatus::RELEASED, $manager->status);
        $this->assertCount(1, $manager->employments);

        $employment = $manager->employments->first();

        $this->assertTrue($employment->started_at->isPast());
        $this->assertTrue($employment->ended_at->gt($employment->started_at));
    }

    /**
     * @test
     */
    public function a_future_employed_manager_has_an_mployment()
    {
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->assertEquals(ManagerStatus::FUTURE_EMPLOYMENT, $manager->status);
        $this->assertCount(1, $manager->employments);

        $employment = $manager->employments->first();

        $this->assertTrue($employment->started_at->isFuture());
        $this->assertNull($employment->ended_at);
    }

    /**
     * @test
     */
    public function an_available_manager_has_an_active_employment()
    {
        $manager = Manager::factory()->available()->create();

        $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
        $this->assertCount(1, $manager->employments);

        $employment = $manager->employments->first();

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);
    }

    /**
     * @test
     */
    public function a_suspended_manager_has_an_active_employment_and_active_suspension()
    {
        $manager = Manager::factory()->suspended()->create();

        $this->assertEquals(ManagerStatus::SUSPENDED, $manager->status);
        $this->assertCount(1, $manager->employments);
        $this->assertCount(1, $manager->suspensions);

        $employment = $manager->employments->first();
        $suspension = $manager->suspensions->first();

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);
        $this->assertTrue($suspension->started_at->isPast());
        $this->assertNull($suspension->ended_at);
        $this->assertTrue($suspension->started_at->gt($employment->started_at));
    }

    /**
     * @test
     */
    public function a_retired_manager_has_a_previous_employment_and_active_retirement()
    {
        $manager = Manager::factory()->retired()->create();

        $this->assertEquals(ManagerStatus::RETIRED, $manager->status);
        $this->assertCount(1, $manager->employments);
        $this->assertCount(1, $manager->retirements);

        $employment = $manager->employments->first();
        $retirement = $manager->retirements->first();

        $this->assertTrue($employment->started_at->isPast());
        $this->assertTrue($employment->ended_at->isPast());
        $this->assertTrue($employment->started_at->lt($employment->ended_at));
        $this->assertTrue($retirement->started_at->isPast());
        $this->assertNull($retirement->ended_at);
        $this->assertTrue($retirement->started_at->eq($employment->ended_at));
    }

    /**
     * @test
     */
    public function an_injured_manager_has_an_active_employment_and_active_suspension()
    {
        $manager = Manager::factory()->injured()->create();

        $this->assertEquals(ManagerStatus::INJURED, $manager->status);
        $this->assertCount(1, $manager->employments);
        $this->assertCount(1, $manager->injuries);

        $employment = $manager->employments->first();
        $injury = $manager->injuries->first();

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);
        $this->assertTrue($injury->started_at->isPast());
        $this->assertNull($injury->ended_at);
        $this->assertTrue($injury->started_at->gt($employment->started_at));
    }
}
