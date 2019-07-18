<?php

namespace Tests\Feature\Admin\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group admins
 */
class ViewTagTeamBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_tag_team_profile()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->get(route('tagteams.show', $tagteam));

        $response->assertViewIs('tagteams.show');
        $this->assertTrue($response->data('tagteam')->is($tagteam));
    }
}
