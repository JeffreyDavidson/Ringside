<?php

namespace Tests\Integration\Factories;

use App\Enums\WrestlerStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

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
    public function a_bookable_wrestler_is_employed_at_current_datetime()
    {
        $now = now();
        Carbon::setTestNow($now);

        $wrestler = WrestlerFactory::new()->bookable()->create();

        $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
        $this->assertCount(1, $wrestler->employments);
        $this->assertEquals($now->toDateTimeString(), $wrestler->employments->first()->started_at->toDateTimeString());
    }

    /** @test */
    public function a_pending_employment_wrestler_is_employed_in_the_future()
    {
        $wrestler = WrestlerFactory::new()->pendingEmployment()->create();

        $this->assertEquals(WrestlerStatus::PENDING_EMPLOYMENT, $wrestler->status);
        $this->assertCount(1, $wrestler->employments);
        $this->assertTrue($wrestler->employments->first()->started_at->isFuture());
    }

    /** @test */
    public function a_released_wrestler_is_employed_in_the_past_and_has_an_ended_employment_date()
    {
        $wrestler = WrestlerFactory::new()->released()->create();

        $this->assertEquals(WrestlerStatus::RELEASED, $wrestler->status);
        $this->assertCount(1, $wrestler->employments);
        $this->assertTrue($wrestler->employments->first()->started_at->isPast());
        $this->assertTrue($wrestler->employments->first()->ended_at->isPast());
    }

    /** @test */
    public function a_suspended_wrestler_has_employment_and_an_active_suspension()
    {
        $wrestler = WrestlerFactory::new()->suspended()->create();

        // $this->assertEquals(WrestlerStatus::SUSPENDED, $wrestler->status);
        // $this->assertCount(1, $wrestler->employments);
        // $this->assertCount(1, $wrestler->suspensions);
        // $this->assertTrue($wrestler->suspensions->first()->started_at->gt($wrestler->employments->first()->started_at));
    }
}
