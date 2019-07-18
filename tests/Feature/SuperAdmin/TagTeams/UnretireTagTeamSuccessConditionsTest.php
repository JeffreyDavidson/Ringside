<?php

namespace Tests\Feature\SuperAdmin\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group superadmins
 */
class UnretireTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_unretire_a_retired_tag_team()
    {
        $this->actAs('super-administrator');
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->put(route('tagteams.unretire', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertEquals(now()->toDateTimeString(), $tagteam->fresh()->retirements()->latest()->first()->ended_at);
    }
}
