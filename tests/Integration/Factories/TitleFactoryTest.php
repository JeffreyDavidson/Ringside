<?php

namespace Tests\Integration\Factories;

use App\Enums\TitleStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TitleFactory;
use Tests\TestCase;

/**
 * @group factories
 */
class TitleFactoryTest extends TestCase
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
    public function a_title_is_unactivated_by_default()
    {
        $title = TitleFactory::new()->create();

        $this->assertEquals(TitleStatus::UNACTIVATED, $title->status);
    }

    /** @test */
    public function an_unactivated_title_has_no_activations()
    {
        $title = TitleFactory::new()->unactivated()->create();

        $this->assertCount(0, $title->activations);
    }

    /** @test */
    public function an_active_title_has_a_status_of_active()
    {
        $title = TitleFactory::new()->active()->create();

        $this->assertEquals(TitleStatus::ACTIVE, $title->status);
    }

    /** @test */
    public function an_active_title_has_been_activated_in_the_past_and_has_no_end_date()
    {
        $title = TitleFactory::new()->active()->create();

        $this->assertCount(1, $title->activations);

        $activation = $title->activations[0];

        $this->assertTrue($activation->started_at->isPast());
        $this->assertNull($activation->ended_at);
    }

    /** @test */
    public function a_title_with_a_future_activation_has_a_status_of_future_activation()
    {
        $title = TitleFactory::new()->withFutureActivation()->create();

        $this->assertEquals(TitleStatus::FUTURE_ACTIVATION, $title->status);
    }

    /** @test */
    public function a_future_activated_title_has_a_activation_in_the_future_and_no_end_date()
    {
        $title = TitleFactory::new()->withFutureActivation()->create();

        $this->assertCount(1, $title->activations);

        $activation = $title->activations[0];

        $this->assertTrue($activation->started_at->isFuture());
        $this->assertNull($activation->ended_at);
    }

    /** @test */
    public function an_inactive_title_has_a_status_of_finactive()
    {
        $title = TitleFactory::new()->inactive()->create();

        $this->assertEquals(TitleStatus::INACTIVE, $title->status);
    }

    /** @test */
    public function an_inactive_title_has_an_activation_in_the_past()
    {
        $title = TitleFactory::new()->inactive()->create();

        $this->assertCount(1, $title->activations);

        $activation = $title->activations[0];

        $this->assertTrue($activation->started_at->isPast());
        $this->assertTrue($activation->ended_at->isPast());
        $this->assertTrue($activation->started_at->lt($activation->ended_at));
    }

    /** @test */
    public function a_retired_title_has_has_a_status_of_retired()
    {
        $title = TitleFactory::new()->retired()->create();

        $this->assertEquals(TitleStatus::RETIRED, $title->status);
    }

    /** @test */
    public function a_retired_title_has_activation_and_an_active_retirement()
    {
        $title = TitleFactory::new()->retired()->create();

        $this->assertCount(1, $title->activations);
        $this->assertCount(1, $title->retirements);

        $activation = $title->activations[0];
        $retirement = $title->retirements[0];

        $this->assertTrue($activation->started_at->lt($activation->ended_at));
        $this->assertTrue($retirement->started_at->equalTo($activation->ended_at));
    }
}
