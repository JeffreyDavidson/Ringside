<?php

namespace Tests\Integration\Factories;

use App\Enums\RefereeStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

class RefereeFactoryTest extends TestCase
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
    public function a_bookable_referee_is_employed_in_the_past_and_has_no_end_date()
    {
        $referee = RefereeFactory::new()->bookable()->create();

        $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
        $this->assertCount(1, $referee->employments);

        $employment = $referee->employments[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_pending_employment_is_in_the_future()
    {
        $referee = RefereeFactory::new()->pendingEmployment()->create();

        $this->assertEquals(RefereeStatus::PENDING_EMPLOYMENT, $referee->status);
        $this->assertCount(1, $referee->employments);

        $employment = $referee->employments[0];

        $this->assertTrue($employment->started_at->isFuture());
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_released_referee_is_employed_in_the_past()
    {
        $referee = RefereeFactory::new()->released()->create();

        $this->assertEquals(RefereeStatus::RELEASED, $referee->status);
        $this->assertCount(1, $referee->employments);

        $employment = $referee->employments[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertTrue($employment->ended_at->isPast());
        $this->assertTrue($employment->started_at->lt($employment->ended_at));
    }

    /** @test */
    public function a_suspended_referee_has_employment_and_an_active_suspension()
    {
        $referee = RefereeFactory::new()->suspended()->create();

        $this->assertEquals(RefereeStatus::SUSPENDED, $referee->status);
        $this->assertCount(1, $referee->employments);
        $this->assertCount(1, $referee->suspensions);

        $employment = $referee->employments[0];
        $suspension = $referee->suspensions[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertTrue($suspension->started_at->gt($employment->started_at));
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_retired_referee_has_employment_and_an_active_retirement()
    {
        $referee = RefereeFactory::new()->retired()->create();

        $this->assertEquals(RefereeStatus::RETIRED, $referee->status);
        $this->assertCount(1, $referee->employments);
        $this->assertCount(1, $referee->retirements);

        $employment = $referee->employments[0];
        $retirement = $referee->retirements[0];

        $this->assertTrue($employment->started_at->lt($employment->ended_at));
        $this->assertTrue($retirement->started_at->equalTo($employment->ended_at));
    }

    /** @test */
    public function an_injured_referee_has_employment_and_an_active_injury()
    {
        $referee = RefereeFactory::new()->injured()->create();

        $this->assertEquals(RefereeStatus::INJURED, $referee->status);
        $this->assertCount(1, $referee->employments);
        $this->assertCount(1, $referee->injuries);

        $employment = $referee->employments[0];
        $injury = $referee->injuries[0];

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);
        $this->assertTrue($injury->started_at->gt($employment->started_at));
    }

    /** @test */
    public function an_unemployed_referee_has_no_employments()
    {
        $referee = RefereeFactory::new()->unemployed()->create();

        $this->assertEquals(RefereeStatus::UNEMPLOYED, $referee->status);
        $this->assertCount(0, $referee->employments);
    }
}
