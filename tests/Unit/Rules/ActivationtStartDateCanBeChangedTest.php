<?php

namespace Tests\Unit\Rules;

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

    /** @test */
    public function an_unactivated_titles_start_date_can_be_changed()
    {
        $title = Title::factory()->unactivated()->create();

        $this->assertTrue((new ActivationStartDateCanBeChanged($title))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function a_future_activated_titles_start_date_can_be_changed()
    {
        $title = Title::factory()->withFutureActivation()->create();

        $this->assertTrue((new ActivationStartDateCanBeChanged($title))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function an_active_titles_start_date_cannot_be_changed()
    {
        $title = Title::factory()->active()->create();

        $this->assertFalse((new ActivationStartDateCanBeChanged($title))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function an_unactivated_stables_start_date_can_be_changed()
    {
        $stable = Stable::factory()->unactivated()->create();

        $this->assertTrue((new ActivationStartDateCanBeChanged($stable))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function a_future_activated_stables_start_date_can_be_changed()
    {
        $stable = Stable::factory()->withFutureActivation()->create();

        $this->assertTrue((new ActivationStartDateCanBeChanged($stable))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function an_active_stables_start_date_cannot_be_changed()
    {
        $stable = Stable::factory()->active()->create();

        $this->assertFalse((new ActivationStartDateCanBeChanged($stable))->passes(null, now()->toDateTimeString()));
    }
}
