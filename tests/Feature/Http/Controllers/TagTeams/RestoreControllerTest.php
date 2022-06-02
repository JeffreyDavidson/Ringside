<?php

namespace Tests\Feature\Http\Controllers\Stables;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagTeam = TagTeam::factory()->trashed()->create();
    }

    /**
     * @test
     */
    public function invoke_restores_a_deleted_tag_team_and_redirects()
    {
        $this
            ->actAs(ROLE::ADMINISTRATOR)
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
            ->actAs(ROLE::BASIC)
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
