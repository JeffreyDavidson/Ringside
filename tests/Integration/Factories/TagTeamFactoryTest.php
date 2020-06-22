<?php

namespace Tests\Integration\Factories;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Wrestler;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use Tests\Factories\TagTeamFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $now = now();
        Carbon::setTestNow($now);

        $tagTeam = TagTeamFactory::new()->create();

        $this->assertCount(2, $tagTeam->wrestlers);
    }

    /** @test */
    public function a_bookable_tag_team_is_employed_at_current_datetime()
    {
        $now = now();
        Carbon::setTestNow($now);

        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
        $this->assertCount(1, $tagTeam->employments);
        $this->assertEquals($now->toDateTimeString(), $tagTeam->employments->first()->started_at->toDateTimeString());
    }

    /** @test */
    public function a_bookable_tag_team_has_two_wrestlers_and_employed_at_same_current_datetime_as_tag_team()
    {
        $now = now();
        Carbon::setTestNow($now);

        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $this->assertCount(2, $tagTeam->wrestlers);
        $wrestler1 = $tagTeam->wrestlers[0];
        $wrestler2 = $tagTeam->wrestlers[1];
        $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler1->status);
        $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler2->status);
        $this->assertCount(1, $wrestler1->employments);
        $this->assertCount(1, $wrestler2->employments);
        $this->assertEquals($now->toDateTimeString(), $wrestler1->employments->first()->started_at->toDateTimeString());
        $this->assertEquals($now->toDateTimeString(), $wrestler2->employments->first()->started_at->toDateTimeString());
        $this->assertEquals(
            $tagTeam->employments->first()->started_at->toDateTimeString(),
            $wrestler1->employments->first()->started_at->toDateTimeString()
        );
        $this->assertEquals(
            $tagTeam->employments->first()->started_at->toDateTimeString(),
            $wrestler2->employments->first()->started_at->toDateTimeString()
        );
    }

    /** @test */
    public function a_pending_employment_tag_team_is_employed_in_the_future()
    {
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $this->assertCount(2, $tagTeam->wrestlers);
        $wrestler1 = $tagTeam->wrestlers[0];
        $wrestler2 = $tagTeam->wrestlers[1];

        $this->assertEquals(TagTeamStatus::PENDING_EMPLOYMENT, $tagTeam->status);
        $this->assertCount(1, $tagTeam->employments);
        $this->assertTrue($tagTeam->employments->first()->started_at->isFuture());
    }

    /** @test */
    public function a_pending_employment_tag_team_has_two_wrestlers_and_employed_at_same_current_datetime_as_tag_team()
    {
        $now = now()->addDay();
        Carbon::setTestNow($now);

        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $this->assertCount(2, $tagTeam->wrestlers);
        $wrestler1 = $tagTeam->wrestlers[0];
        $wrestler2 = $tagTeam->wrestlers[1];
        $this->assertEquals(WrestlerStatus::PENDING_EMPLOYMENT, $wrestler1->status);
        $this->assertEquals(WrestlerStatus::PENDING_EMPLOYMENT, $wrestler2->status);
        $this->assertCount(1, $wrestler1->employments);
        $this->assertCount(1, $wrestler2->employments);
        $this->assertEquals($now->toDateTimeString(), $wrestler1->employments->first()->started_at->toDateTimeString());
        $this->assertEquals($now->toDateTimeString(), $wrestler2->employments->first()->started_at->toDateTimeString());
        $this->assertEquals(
            $tagTeam->employments->first()->started_at->toDateTimeString(),
            $wrestler1->employments->first()->started_at->toDateTimeString()
        );
        $this->assertEquals(
            $tagTeam->employments->first()->started_at->toDateTimeString(),
            $wrestler2->employments->first()->started_at->toDateTimeString()
        );
    }

    /** @test */
    public function a_released_tag_team_is_employed_in_the_past_and_has_an_ended_employment_date()
    {
        $tagTeam = TagTeamFactory::new()->released()->create();

        $this->assertEquals(TagTeamStatus::RELEASED, $tagTeam->status);
        $this->assertCount(1, $tagTeam->employments);
        $this->assertTrue($tagTeam->employments->first()->started_at->isPast());
        $this->assertTrue($tagTeam->employments->first()->ended_at->isPast());
    }

    /** @test */
    public function a_suspended_tag_team_has_employment_and_an_active_suspension()
    {
        $tagTeam = TagTeamFactory::new()->suspended()->create();

        $this->assertEquals(TagTeamStatus::SUSPENDED, $tagTeam->status);
        $this->assertCount(1, $tagTeam->employments);
        $this->assertCount(1, $tagTeam->suspensions);
        $this->assertTrue($tagTeam->suspensions->first()->started_at->gt($tagTeam->employments->first()->started_at));
    }
}
