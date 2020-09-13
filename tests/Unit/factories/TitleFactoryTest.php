<?php

use App\Enums\TitleStatus;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function default_title_is_unactivated()
    {
        $title = Title::factory()->create();

        $this->assertEquals(TitleStatus::UNACTIVATED, $title->status);
    }

    /** @test */
    public function an_unactivated_title_has_zero_activations()
    {
        $title = Title::factory()->unactivated()->create();

        $this->assertEquals(TitleStatus::UNACTIVATED, $title->status);
        $this->assertCount(0, $title->activations);
    }

    /** @test */
    public function an_inactive_title_has_a_previous_activation()
    {
        $title = Title::factory()->inactive()->create();

        tap($title->fresh(), function ($title) {
            $this->assertEquals(TitleStatus::INACTIVE, $title->status);
            $this->assertCount(1, $title->activations);

            $activation = $title->activations->first();

            $this->assertTrue($activation->started_at->isPast());
            $this->assertTrue($activation->ended_at->gt($activation->started_at));
        });
    }

    /** @test */
    public function a_future_employed_title_has_an_mployment()
    {
        $title = Title::factory()->withFutureActivation()->create();

        tap($title->fresh(), function ($title) {
            $this->assertEquals(TitleStatus::FUTURE_ACTIVATION, $title->status);
            $this->assertCount(1, $title->activations);

            $activation = $title->activations->first();

            $this->assertTrue($activation->started_at->isFuture());
            $this->assertNull($activation->ended_at);
        });
    }

    /** @test */
    public function an_active_title_has_an_active_activation()
    {
        $title = Title::factory()->active()->create();

        tap($title->fresh(), function ($title) {
            $this->assertEquals(TitleStatus::ACTIVE, $title->status);
            $this->assertCount(1, $title->activations);

            $activation = $title->activations->first();

            $this->assertTrue($activation->started_at->isPast());
            $this->assertNull($activation->ended_at);
        });
    }

    /** @test */
    public function a_retired_title_has_a_previous_activation_and_active_retirement()
    {
        $title = Title::factory()->retired()->create();

        tap($title->fresh(), function ($title) {
            $this->assertEquals(TitleStatus::RETIRED, $title->status);
            $this->assertCount(1, $title->activations);
            $this->assertCount(1, $title->retirements);

            $activation = $title->activations->first();
            $retirement = $title->retirements->first();

            $this->assertTrue($activation->started_at->isPast());
            $this->assertTrue($activation->ended_at->isPast());
            $this->assertTrue($activation->started_at->lt($activation->ended_at));
            $this->assertTrue($retirement->started_at->isPast());
            $this->assertNull($retirement->ended_at);
            $this->assertTrue($retirement->started_at->eq($activation->ended_at));
        });
    }
}
