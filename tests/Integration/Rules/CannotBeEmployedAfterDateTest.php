<?php

namespace Tests\Integration\Rules;

use App\Models\Wrestler;
use App\Rules\CannotBeEmployedAfterDate;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group rules
 */
class CannotBeEmployedAfterDateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function an_wrestler_without_a_start_date_can_join_a_tag_team()
    {
        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->assertTrue((new CannotBeEmployedAfterDate(null))->passes(null, $wrestler->id));
    }

    /**
     * @test
     */
    public function an_unemployed_wrestler_with_a_start_date_cannot_join_a_tag_team()
    {
        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->assertTrue((new CannotBeEmployedAfterDate(now()->toDateTimeString()))->passes(null, $wrestler->id));
    }

    /**
     * @test
     */
    public function a_bookable_wrestler_can_join_at_tag_team()
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        $this->assertTrue((new CannotBeEmployedAfterDate(now()->toDateTimeString()))->passes(null, $wrestler->id));
    }

    /**
     * @test
     */
    public function a_wrestler_cannot_have_a_current_employment_date_after_the_tag_team_start_date()
    {
        $dateStarted = Carbon::now()->subDays(2);

        $wrestler = Wrestler::factory()->hasEmployments(['started_at' => $dateStarted->copy()->addDay()->toDateTimeString()])->create();

        $this->assertFalse((new CannotBeEmployedAfterDate($dateStarted->toDateTimeString()))->passes(null, $wrestler->id));
    }

    /**
     * @test
     */
    public function a_wrestler_can_have_a_future_employment_date_before_the_tag_team_start_date()
    {
        $dateStarted = now()->addDay()->toDateTimeString();

        $wrestler = Wrestler::factory()->hasEmployments(['started_at' => $dateStarted])->create();

        $this->assertTrue((new CannotBeEmployedAfterDate(now()->addWeek()->toDateTimeString()))->passes(null, $wrestler->id));
    }

    /**
     * @test
     */
    public function a_wrestler_cannot_have_a_future_employment_date_after_the_tag_team_start_date()
    {
        $dateStarted = now()->addDays(4)->toDateTimeString();

        $wrestler = Wrestler::factory()->hasEmployments(['started_at' => $dateStarted])->create();

        $this->assertFalse((new CannotBeEmployedAfterDate(now()->addDays(3)->toDateTimeString()))->passes(null, $wrestler->id));
    }
}
