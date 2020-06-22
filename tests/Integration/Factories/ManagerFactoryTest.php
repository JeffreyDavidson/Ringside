<?php

namespace Tests\Integration\Factories;

use App\Enums\ManagerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;

class ManagerFactoryTest extends TestCase
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
    public function a_available_manager_is_employed_in_the_past_and_has_no_end_date()
    {
        $manager = ManagerFactory::new()->available()->create();

        $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
        $this->assertCount(1, $manager->employments);

        $employment = $manager->employments[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_pending_employment_is_in_the_future()
    {
        $manager = ManagerFactory::new()->pendingEmployment()->create();

        $this->assertEquals(ManagerStatus::PENDING_EMPLOYMENT, $manager->status);
        $this->assertCount(1, $manager->employments);

        $employment = $manager->employments[0];

        $this->assertTrue($employment->started_at->isFuture());
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_released_manager_is_employed_in_the_past()
    {
        $manager = ManagerFactory::new()->released()->create();

        $this->assertEquals(ManagerStatus::RELEASED, $manager->status);
        $this->assertCount(1, $manager->employments);

        $employment = $manager->employments[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertTrue($employment->ended_at->isPast());
        $this->assertTrue($employment->started_at->lt($employment->ended_at));
    }

    /** @test */
    public function a_suspended_manager_has_employment_and_an_active_suspension()
    {
        $manager = ManagerFactory::new()->suspended()->create();

        $this->assertEquals(ManagerStatus::SUSPENDED, $manager->status);
        $this->assertCount(1, $manager->employments);
        $this->assertCount(1, $manager->suspensions);

        $employment = $manager->employments[0];
        $suspension = $manager->suspensions[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertTrue($suspension->started_at->gt($employment->started_at));
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_retired_manager_has_employment_and_an_active_retirement()
    {
        $manager = ManagerFactory::new()->retired()->create();

        $this->assertEquals(ManagerStatus::RETIRED, $manager->status);
        $this->assertCount(1, $manager->employments);
        $this->assertCount(1, $manager->retirements);

        $employment = $manager->employments[0];
        $retirement = $manager->retirements[0];

        $this->assertTrue($employment->started_at->lt($employment->ended_at));
        $this->assertTrue($retirement->started_at->equalTo($employment->ended_at));
    }

    /** @test */
    public function an_injured_manager_has_employment_and_an_active_injury()
    {
        $manager = ManagerFactory::new()->injured()->create();

        $this->assertEquals(ManagerStatus::INJURED, $manager->status);
        $this->assertCount(1, $manager->employments);
        $this->assertCount(1, $manager->injuries);

        $employment = $manager->employments[0];
        $injury = $manager->injuries[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);
        $this->assertTrue($injury->started_at->gt($employment->started_at));
    }

    /** @test */
    public function an_unemployed_manager_has_no_employments()
    {
        $manager = ManagerFactory::new()->unemployed()->create();

        $this->assertEquals(ManagerStatus::UNEMPLOYED, $manager->status);
        $this->assertCount(0, $manager->employments);
    }
}
