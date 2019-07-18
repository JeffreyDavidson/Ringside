<?php

namespace Tests\Feature\SuperAdmin\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group superadmins
 */
class ViewTagTeamBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_view_a_tag_team_profile()
    {
        $this->actAs('super-administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->get(route('tagteams.show', ['tagteam' => $tagteam]));

        $response->assertViewIs('tagteams.show');
        $this->assertTrue($response->data('tagteam')->is($tagteam));
    }
}
