<?php

namespace Tests\Integration\Factories;

use App\Enums\WrestlerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group factories
 */
class WrestlerFactoryTest extends TestCase
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
    public function a_bookable_wrestler_is_employed_in_the_past_and_has_no_end_date()
    {
        $wrestler = WrestlerFactory::new()->bookable()->create();

        $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
        $this->assertCount(1, $wrestler->employments);

        $employment = $wrestler->employments[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_pending_employment_is_in_the_future()
    {
        $wrestler = WrestlerFactory::new()->pendingEmployment()->create();

        $this->assertEquals(WrestlerStatus::PENDING_EMPLOYMENT, $wrestler->status);
        $this->assertCount(1, $wrestler->employments);

        $employment = $wrestler->employments[0];

        $this->assertTrue($employment->started_at->isFuture());
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_released_wrestler_is_employed_in_the_past()
    {
        $wrestler = WrestlerFactory::new()->released()->create();

        $this->assertEquals(WrestlerStatus::RELEASED, $wrestler->status);
        $this->assertCount(1, $wrestler->employments);

        $employment = $wrestler->employments[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertTrue($employment->ended_at->isPast());
        $this->assertTrue($employment->started_at->lt($employment->ended_at));
    }

    /** @test */
    public function a_suspended_wrestler_has_employment_and_an_active_suspension()
    {
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $this->assertEquals(WrestlerStatus::SUSPENDED, $wrestler->status);
        $this->assertCount(1, $wrestler->employments);
        $this->assertCount(1, $wrestler->suspensions);

        $employment = $wrestler->employments[0];
        $suspension = $wrestler->suspensions[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertTrue($suspension->started_at->gt($employment->started_at));
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_retired_wrestler_has_employment_and_an_active_retirement()
    {
        $wrestler = WrestlerFactory::new()->retired()->create();

        $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
        $this->assertCount(1, $wrestler->employments);
        $this->assertCount(1, $wrestler->retirements);

        $employment = $wrestler->employments[0];
        $retirement = $wrestler->retirements[0];

        $this->assertTrue($employment->started_at->lt($employment->ended_at));
        $this->assertTrue($retirement->started_at->equalTo($employment->ended_at));
    }

    /** @test */
    public function an_injured_wrestler_has_employment_and_an_active_injury()
    {
        $wrestler = WrestlerFactory::new()->injured()->create();

        $this->assertEquals(WrestlerStatus::INJURED, $wrestler->status);
        $this->assertCount(1, $wrestler->employments);
        $this->assertCount(1, $wrestler->injuries);

        $employment = $wrestler->employments[0];
        $injury = $wrestler->injuries[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);
        $this->assertTrue($injury->started_at->gt($employment->started_at));
    }

    /** @test */
    public function an_unemployed_wrestler_has_no_employments()
    {
        $wrestler = WrestlerFactory::new()->unemployed()->create();

        $this->assertEquals(WrestlerStatus::UNEMPLOYED, $wrestler->status);
        $this->assertCount(0, $wrestler->employments);
    }
}
