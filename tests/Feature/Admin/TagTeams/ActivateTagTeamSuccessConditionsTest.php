<?php

namespace Tests\Feature\Admin\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group admins
 */
class ActivateTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_activate_an_inactive_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('pending-introduction')->create();

        $response = $this->put(route('tagteams.activate', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        tap($tagteam->fresh(), function ($tagteam) {
            $this->assertTrue($tagteam->is_bookable);
        });
    }
}
