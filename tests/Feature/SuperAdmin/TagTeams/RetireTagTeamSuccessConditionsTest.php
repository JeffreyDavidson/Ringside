<?php

namespace Tests\Feature\SuperAdmin\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group superadmins
 */
class RetireTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_retire_a_bookable_tag_team()
    {
        $this->actAs('super-administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->put(route('tagteams.retire', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertEquals(now()->toDateTimeString(), $tagteam->fresh()->retirement->started_at);
    }

    /** @test */
    public function a_super_administrator_can_retire_a_suspended_tag_team()
    {
        $this->actAs('super-administrator');
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->put(route('tagteams.retire', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertEquals(now()->toDateTimeString(), $tagteam->fresh()->retirement->started_at);
    }
}
