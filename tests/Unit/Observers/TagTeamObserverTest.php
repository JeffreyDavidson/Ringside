<?php

namespace Tests\Unit\Observers;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 * @group observers
 */
class TagTeamObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_tag_team_status_is_calculated_correctly()
    {
        $tagTeam = TagTeamFactory::new()->create();
        $this->assertEquals('unemployed', $tagTeam->status);

        $tagTeam->employ(Carbon::tomorrow()->toDateTimeString());
        $this->assertEquals('future-employment', $tagTeam->status);

        $tagTeam->employ(Carbon::today()->toDateTimeString());
        $this->assertEquals('bookable', $tagTeam->status);

        $tagTeam = TagTeamFactory::new()->suspended()->create();
        $this->assertEquals('suspended', $tagTeam->status);

        $tagTeam = TagTeamFactory::new()->retired->create();
        $this->assertEquals('retired', $tagTeam->status);
    }
}
