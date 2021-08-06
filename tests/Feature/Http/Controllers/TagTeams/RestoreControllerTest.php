<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_restores_a_deleted_tag_team_and_redirects()
    {
        $tagTeam = TagTeam::factory()->softDeleted()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(route('tag-teams.restore', $tagTeam))
            ->assertRedirect(route('tag-teams.index'));

        $this->assertNull($tagTeam->fresh()->deleted_at);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_restore_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->softDeleted()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('tag-teams.restore', $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_restore_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->softDeleted()->create();

        $this->patch(route('tag-teams.restore', $tagTeam))
            ->assertRedirect(route('login'));
    }
}
