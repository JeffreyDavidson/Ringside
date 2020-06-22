<?php

namespace Tests\Integration\Factories;

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

class TagTeamFactoryTest extends TestCase
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
    public function a_tag_team_always_consists_of_two_wrestlers()
    {
        $tagTeam = TagTeamFactory::new()->create();

        $this->assertCount(2, $tagTeam->wrestlers);
    }

    /** @test */
    public function a_tag_teams_bookable_employment_is_in_the_past()
    {
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
        $this->assertCount(1, $tagTeam->employments);

        $employment = $tagTeam->employments->first();

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_bookable_tag_team_employed_at_same_current_datetime_as_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $tagTeam->wrestlers->each(function ($wrestler) use ($tagTeam) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);

            $tagTeamEmployment = $tagTeam->employments[0];
            $wrestlerEmployment = $wrestler->employments[0];

            $this->assertTrue($tagTeamEmployment->started_at->equalTo($wrestlerEmployment->started_at));
            $this->assertNull($tagTeamEmployment->ended_at);
        });
    }

    /** @test */
    public function a_pending_employment_is_in_the_future()
    {
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $this->assertEquals(TagTeamStatus::PENDING_EMPLOYMENT, $tagTeam->status);
        $this->assertCount(1, $tagTeam->employments);

        $employment = $tagTeam->employments->first();

        $this->assertTrue($employment->started_at->isFuture());
        $this->assertNull($employment->ended_at);
    }

    /** @test */
    public function a_pending_employment_tag_team_employs_at_same_current_datetime_as_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $tagTeam->wrestlers->each(function ($wrestler) use ($tagTeam) {
            $this->assertEquals(WrestlerStatus::PENDING_EMPLOYMENT, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);

            $tagTeamEmployment = $tagTeam->employments[0];
            $wrestlerEmployment = $wrestler->employments[0];

            $this->assertTrue($tagTeamEmployment->started_at->equalTo($wrestlerEmployment->started_at));
            $this->assertNull($wrestlerEmployment->ended_at);
        });
    }

    /** @test */
    public function a_tag_teams_released_employment_in_the_past()
    {
        $tagTeam = TagTeamFactory::new()->released()->create();

        $this->assertEquals(TagTeamStatus::RELEASED, $tagTeam->status);
        $this->assertCount(1, $tagTeam->employments);

        $employment = $tagTeam->employments->first();

        $this->assertTrue($employment->started_at->isPast());
        $this->assertTrue($employment->ended_at->isPast());

        $this->assertTrue($employment->started_at->lt($employment->ended_at));
    }

    /** @test */
    public function wrestlers_are_released_at_same_time_as_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->released()->create();

        $tagTeam->wrestlers->each(function ($wrestler) use ($tagTeam) {
            $this->assertEquals(WrestlerStatus::RELEASED, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);

            $tagTeamEmployment = $tagTeam->employments[0];
            $wrestlerEmployment = $wrestler->employments[0];

            $this->assertTrue($tagTeamEmployment->started_at->equalTo($wrestlerEmployment->started_at));
            $this->assertTrue($tagTeamEmployment->ended_at->equalTo($wrestlerEmployment->ended_at));
        });
    }

    /** @test */
    public function a_tag_team_suspension_is_started_after_their_employment_starts()
    {
        $tagTeam = TagTeamFactory::new()->suspended()->create();

        $this->assertEquals(TagTeamStatus::SUSPENDED, $tagTeam->status);
        $this->assertCount(1, $tagTeam->employments);
        $this->assertCount(1, $tagTeam->suspensions);

        $employment = $tagTeam->employments->first();
        $suspension = $tagTeam->suspension->first();

        $this->assertTrue($suspension->started_at->gt($employment->started_at));
    }

    /** @test */
    public function wrestlers_are_suspended_at_same_time_as_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->released()->create();

        $tagTeam->wrestlers->each(function ($wrestler) use ($tagTeam) {
            $this->assertEquals(WrestlerStatus::SUSPENDED, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);
            $this->assertCount(1, $wrestler->suspension);

            $tagTeamEmployment = $tagTeam->employments[0];
            $tagTeamSuspension = $tagTeam->suspensions[0];
            $wrestlerEmployment = $wrestler->employments[0];
            $wrestlerSuspension = $wrestler->suspensions[0];

            $this->assertTrue($tagTeamEmployment->started_at->equalTo($wrestlerEmployment->started_at));
            $this->assertTrue($tagTeamSuspension->started_at->equalTo($wrestlerSuspension->started_at));
        });
    }

    /** @test */
    public function a_tag_teams_retirement_is_started_after_their_employment_starts()
    {
        $tagTeam = TagTeamFactory::new()->retired()->create();

        $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);
        $this->assertCount(1, $tagTeam->employments);
        $this->assertCount(1, $tagTeam->retirements);

        $employment = $tagTeam->employments->first();
        $retirement = $tagTeam->retirements->first();

        $this->assertTrue($employment->started_at->isPast());
        $this->assertNull($employment->ended_at);

        $this->assertTrue($employment->started_at->lt($retirement->started_at));
    }

    /** @test */
    public function wrestlers_are_retired_at_same_time_as_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->released()->create();

        $tagTeam->wrestlers->each(function ($wrestler) use ($tagTeam) {
            $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);
            $this->assertCount(1, $wrestler->retirements);

            $tagTeamEmployment = $tagTeam->employments[0];
            $wrestlerEmployment = $wrestler->employments[0];
            $tagTeamRetirement = $tagTeam->retirements[0];
            $wrestlerRetirement = $wrestler->retirements[0];

            $this->assertTrue($tagTeamEmployment->started_at->equalTo($wrestlerEmployment->started_at));
            $this->assertTrue($tagTeamRetirement->ended_at->equalTo($wrestlerRetirement->ended_at));
        });
    }

    /** @test */
    public function an_unemployed_tag_team_has_no_employments()
    {
        $tagTeam = TagTeamFactory::new()->unemployed()->create();

        $this->assertEquals(TagTeamStatus::UNEMPLOYED, $tagTeam->status);
        $this->assertCount(0, $tagTeam->employments);
    }

    /** @test */
    public function wrestlers_are_unemployed_for_an_unemployed_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->unemployed()->create();

        $tagTeam->wrestlers->each(function ($wrestler) use ($tagTeam) {
            $this->assertEquals(WrestlerStatus::UNEMPLOYED, $wrestler->status);
            $this->assertCount(0, $wrestler->employments);
        });
    }
}
