<?php

namespace Tests\Feature\Admin\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group admins
 */
class UnretireTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->put(route('tagteams.unretire', $tagteam));
    
        $response->assertRedirect(route('tagteams.index'));
        $this->assertEquals(now()->toDateTimeString(), $tagteam->fresh()->retirements()->latest()->first()->ended_at);
    }
}
