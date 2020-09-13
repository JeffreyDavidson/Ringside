<?php

use App\Enums\TagTeamStatus;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTeamFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function default_tag_team_is_unemployed()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->assertEquals(TagTeamStatus::UNEMPLOYED, $tagTeam->status);
    }

    /** @test */
    public function an_unemployed_tag_team_has_zero_employments()
    {
        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->assertEquals(TagTeamStatus::UNEMPLOYED, $tagTeam->status);
        $this->assertCount(0, $tagTeam->employments);
    }

    /** @test */
    public function a_released_tag_team_has_a_previous_employment()
    {
        $tagTeam = TagTeam::factory()->released()->create();

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::RELEASED, $tagTeam->status);
            $this->assertCount(1, $tagTeam->employments);

            $employment = $tagTeam->employments->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertTrue($employment->ended_at->gt($employment->started_at));
        });
    }

    /** @test */
    public function a_future_employed_tag_team_has_an_mployment()
    {
        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();
        dd($tagTeam);

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::FUTURE_EMPLOYMENT, $tagTeam->status);
            $this->assertCount(1, $tagTeam->employments);

            $employment = $tagTeam->employments->first();

            $this->assertTrue($employment->started_at->isFuture());
            $this->assertNull($employment->ended_at);
        });
    }

    /** @test */
    public function a_future_employed_tag_team_without_wrestlers_has_an_mployment_but_no_current_wrestlers()
    {
        $tagTeam = TagTeam::factory()->withFutureEmploymentWithoutWrestlers()->create();

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::FUTURE_EMPLOYMENT, $tagTeam->status);
            $this->assertCount(1, $tagTeam->employments);

            $employment = $tagTeam->employments->first();

            $this->assertTrue($employment->started_at->isFuture());
            $this->assertNull($employment->ended_at);

            $this->assertEmpty($tagTeam->wrestlers);
        });
    }

    /** @test */
    public function a_bookable_tag_team_has_an_active_employment()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
            $this->assertCount(1, $tagTeam->employments);

            $employment = $tagTeam->employments->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertNull($employment->ended_at);
        });
    }

    /** @test */
    public function a_suspended_tag_team_has_an_active_employment_and_active_suspension()
    {
        $tagTeam = TagTeam::factory()->suspended()->create();

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::SUSPENDED, $tagTeam->status);
            $this->assertCount(1, $tagTeam->employments);
            $this->assertCount(1, $tagTeam->suspensions);

            $employment = $tagTeam->employments->first();
            $suspension = $tagTeam->suspensions->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertNull($employment->ended_at);
            $this->assertTrue($suspension->started_at->isPast());
            $this->assertNull($suspension->ended_at);
            $this->assertTrue($suspension->started_at->gt($employment->started_at));
        });
    }

    /** @test */
    public function a_retired_tag_team_has_a_previous_employment_and_active_retirement()
    {
        $tagTeam = TagTeam::factory()->retired()->create();

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);
            $this->assertCount(1, $tagTeam->employments);
            $this->assertCount(1, $tagTeam->retirements);

            $employment = $tagTeam->employments->first();
            $retirement = $tagTeam->retirements->first();

            $this->assertTrue($employment->started_at->isPast());
            $this->assertTrue($employment->ended_at->isPast());
            $this->assertTrue($employment->started_at->lt($employment->ended_at));
            $this->assertTrue($retirement->started_at->isPast());
            $this->assertNull($retirement->ended_at);
            $this->assertTrue($retirement->started_at->eq($employment->ended_at));
        });
    }
}
