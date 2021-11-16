<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Http\Controllers\TagTeams\RestoreController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    public TagTeam $tagTeam;

    public function setUp(): void
    {
        parent::setUp();

        $this->tagTeam = TagTeam::factory()->softDeleted()->create();
    }

    /**
     * @test
     */
    public function invoke_restores_a_deleted_tag_team_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->patch(action([RestoreController::class], $this->tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        $this->assertNull($this->tagTeam->fresh()->deleted_at);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_restore_a_tag_team()
    {
        $this
            ->actAs(Role::basic())
            ->patch(action([RestoreController::class], $this->tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_restore_a_tag_team()
    {
        $this
            ->patch(action([RestoreController::class], $this->tagTeam))
            ->assertRedirect(route('login'));
    }
}
