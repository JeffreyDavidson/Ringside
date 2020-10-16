<?php

namespace Tests\Unit\Observers;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $tagTeam = TagTeam::factory()->unemployed()->create();
        $this->assertEquals('unemployed', $tagTeam->status);

        $tagTeam->employ(Carbon::tomorrow()->toDateTimeString());
        $this->assertEquals('future-employment', $tagTeam->status);

        $tagTeam->employ(Carbon::today()->toDateTimeString());
        $this->assertEquals('bookable', $tagTeam->status);

        $tagTeam->suspend();
        $this->assertEquals('suspended', $tagTeam->status);

        $tagTeam->reinstate();
        $this->assertEquals('bookable', $tagTeam->status);

        $tagTeam->retire();
        $this->assertEquals('retired', $tagTeam->status);

        $tagTeam->unretire();
        $this->assertEquals('bookable', $tagTeam->status);
    }
}