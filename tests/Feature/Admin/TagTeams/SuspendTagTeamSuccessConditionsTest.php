<?php

namespace Tests\Feature\Admin\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group admins
 */
class SuspendTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_suspend_a_bookable_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->put(route('tagteams.suspend', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertEquals(now()->toDateTimeString(), $tagteam->fresh()->suspension->started_at);
    }
}
