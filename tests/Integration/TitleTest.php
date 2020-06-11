<?php

namespace Tests\Integration;

use Carbon\Carbon;
use Tests\TestCase;
use Tests\Factories\TitleFactory;
use Illuminate\Support\Facades\Event;
use App\Exceptions\CannotBeActivatedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 */
class TitleTest extends TestCase
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
        Event::fake();
    }

    /** @test */
    public function an_active_title_can_be_retired()
    {
        $title = TitleFactory::new()->active()->create();

        $this->assertTrue($title->canBeRetired());
    }

    /** @test */
    public function an_inactive_title_can_be_retired()
    {
        $title = TitleFactory::new()->inactive()->create();

        $this->assertTrue($title->canBeRetired());
    }

    /** @test */
    public function an_unactivated_title_cannot_be_retired()
    {
        $title = TitleFactory::new()->unactivated()->create();

        $this->assertFalse($title->canBeRetired());
    }

    /** @test */
    public function a_title_with_a_future_activation_cannot_be_retired()
    {
        $title = TitleFactory::new()->futureActivation()->create();

        $this->assertFalse($title->canBeRetired());
    }

    /** @test */
    public function a_retired_title_cannot_be_retired()
    {
        $title = TitleFactory::new()->retired()->create();

        $this->assertFalse($title->canBeRetired());
    }

    /** @test */
    public function a_retired_title_can_be_unretired()
    {
        $title = TitleFactory::new()->retired()->create();

        $this->assertTrue($title->canBeUnretired());
    }

    /** @test */
    public function an_active_title_cannot_be_unretired()
    {
        $title = TitleFactory::new()->active()->create();

        $this->assertFalse($title->canBeUnretired());
    }

    /** @test */
    public function a_title_with_a_future_activation_cannot_be_unretired()
    {
        $title = TitleFactory::new()->futureActivation()->create();

        $this->assertFalse($title->canBeUnretired());
    }

    /** @test */
    public function an_unactivated_title_cannot_be_unretired()
    {
        $title = TitleFactory::new()->unactivated()->create();

        $this->assertFalse($title->canBeUnretired());
    }

    /** @test */
    public function an_inactive_title_cannot_be_unretired()
    {
        $title = TitleFactory::new()->inactive()->create();

        $this->assertFalse($title->canBeUnretired());
    }

    /** @test */
    public function a_title_can_be_retired()
    {
        $now = Carbon::now();

        $title = TitleFactory::new()->active()->create();

        $title->retire();

        $this->assertEquals($now->toDateTimeString(), $title->previousActivation->ended_at->toDateTimeString());
        $this->assertEquals($now->toDateTimeString(), $title->currentRetirement->started_at->toDateTimeString());
    }

    /** @test */
    public function a_title_can_be_retired_on_a_specific_date()
    {
        $yesterday = Carbon::now()->subDay();

        $title = TitleFactory::new()->active()->create();

        $title->retire($yesterday);

        $this->assertEquals($yesterday->toDateTimeString(), $title->previousActivation->ended_at->toDateTimeString());
        $this->assertEquals($yesterday->toDateTimeString(), $title->currentRetirement->started_at->toDateTimeString());
    }

    /** @test */
    public function a_title_is_unretired_on_the_current_date_by_default()
    {
        $now = Carbon::now();

        $title = TitleFactory::new()->retired()->create();

        $title->unretire();

        $this->assertEquals($now->toDateTimeString(), $title->currentActivation->started_at->toDateTimeString());
        $this->assertEquals($now->toDateTimeString(), $title->previousRetirement->ended_at->toDateTimeString());
    }

    /** @test */
    public function a_title_can_be_unretired_on_a_specific_date()
    {
        $yesterday = Carbon::now()->subDay();

        $title = TitleFactory::new()->retired()->create();

        $title->unretire($yesterday);

        $this->assertEquals($yesterday->toDateTimeString(), $title->currentActivation->started_at->toDateTimeString());
        $this->assertEquals($yesterday->toDateTimeString(), $title->previousRetirement->ended_at->toDateTimeString());
    }

    /** @test */
    public function an_active_title_cannot_be_activated()
    {
        $this->withoutExceptionHandling();

        $title = TitleFactory::new()->active()->create();

        $this->expectException(CannotBeActivatedException::class);

        $this->activateRequest($title);
    }
}
