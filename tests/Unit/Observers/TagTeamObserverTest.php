<?php

namespace Tests\Unit\Observers;

use App\Models\TagTeam;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class TagTeamObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_tag_team_status_is_calculated_correctly()
    {
        $tagTeam = factory(TagTeam::class)->create();
        $this->assertEquals('pending-employment', $tagTeam->status);

        $tagTeam->employ(Carbon::tomorrow()->toDateTimeString());
        $this->assertEquals('pending-employment', $tagTeam->status);

        $tagTeam->employ(Carbon::today()->toDateTimeString());
        $this->assertEquals('bookable', $tagTeam->status);

        $tagTeam = factory(TagTeam::class)->states('suspended')->create();
        $this->assertEquals('suspended', $tagTeam->status);

        $tagTeam = factory(TagTeam::class)->states('retired')->create();
        $this->assertEquals('retired', $tagTeam->status);
    }
}
