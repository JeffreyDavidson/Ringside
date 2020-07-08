<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\TagTeamFactory;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function invoke_restores_a_deleted_tag_team_and_redirects()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertNull($tagTeam->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeamFactory::new()->softDeleted()->create();

        $this->restoreRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->softDeleted()->create();

        $this->restoreRequest($tagTeam)->assertRedirect(route('login'));
    }
}
