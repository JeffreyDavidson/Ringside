<?php

namespace Tests\Integration\Rules;

use App\Models\Stable;
use App\Models\Title;
use App\Rules\ActivationStartDateCanBeChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group rules
 */
class ActivationtStartDateCanBeChangedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function an_unactivated_title_start_date_can_be_changed()
    {
        $title = Title::factory()->unactivated()->create();

        $this->assertTrue((new ActivationStartDateCanBeChanged($title))->passes(null, now()->toDateTimeString()));
    }

    /**
     * @test
     */
    public function a_future_activated_title_start_date_can_be_changed()
    {
        $title = Title::factory()->withFutureActivation()->create();

        $this->assertTrue((new ActivationStartDateCanBeChanged($title))->passes(null, now()->toDateTimeString()));
    }

    /**
     * @test
     */
    public function an_active_title_start_date_cannot_be_changed()
    {
        $title = Title::factory()->active()->create();

        $this->assertFalse((new ActivationStartDateCanBeChanged($title))->passes(null, now()->toDateTimeString()));
    }

    /**
     * @test
     */
    public function an_inactive_title_start_date_cannot_be_changed()
    {
        $title = Title::factory()->inactive()->create();

        $this->assertFalse((new ActivationStartDateCanBeChanged($title))->passes(null, now()->toDateTimeString()));
    }

    /**
     * @test
     */
    public function an_unactivated_stable_start_date_can_be_changed()
    {
        $stable = Stable::factory()->unactivated()->create();

        $this->assertTrue((new ActivationStartDateCanBeChanged($stable))->passes(null, now()->toDateTimeString()));
    }

    /**
     * @test
     */
    public function a_future_activated_stable_start_date_can_be_changed()
    {
        $stable = Stable::factory()->withFutureActivation()->create();

        $this->assertTrue((new ActivationStartDateCanBeChanged($stable))->passes(null, now()->toDateTimeString()));
    }

    /**
     * @test
     */
    public function an_active_stable_start_date_cannot_be_changed()
    {
        $stable = Stable::factory()->active()->create();

        $this->assertFalse((new ActivationStartDateCanBeChanged($stable))->passes(null, now()->toDateTimeString()));
    }
}
