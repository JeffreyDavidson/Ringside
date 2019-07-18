<?php

namespace Tests\Feature\SuperAdmin\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group superadmins
 */
class SuspendTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_suspend_a_bookable_tag_team()
    {
        $this->actAs('super-administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->put(route('tagteams.suspend', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertEquals(now()->toDateTimeString(), $tagteam->fresh()->suspension->started_at);
    }
}
