<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use App\Models\User;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class TagTeamControllerTest extends TestCase
{
    /**
     * @test
     */
    public function index_returns_a_view()
    {
        $this
            ->actAs(ROLE::ADMINISTRATOR)
            ->get(action([TagTeamsController::class, 'index']))
            ->assertOk()
            ->assertViewIs('tagteams.index')
            ->assertSeeLivewire('tag-teams.tag-teams-list');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_tag_teams_index_page()
    {
        $this
            ->actAs(ROLE::BASIC)
            ->get(action([TagTeamsController::class, 'index']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_tag_teams_index_page()
    {
        $this
            ->get(action([TagTeamsController::class, 'index']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function show_returns_a_view()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(ROLE::ADMINISTRATOR)
            ->get(action([TagTeamsController::class, 'show'], $tagTeam))
            ->assertViewIs('tagteams.show')
            ->assertViewHas('tagTeam', $tagTeam);
    }

    /**
     * @test
     */
    public function a_basic_user_can_view_their_tag_team_profile()
    {
        $this->actAs(ROLE::BASIC);
        $tagTeam = TagTeam::factory()->create(['user_id' => auth()->user()]);

        $this
            ->get(action([TagTeamsController::class, 'show'], $tagTeam))
            ->assertOk();
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_another_users_tag_team_profile()
    {
        $tagTeam = TagTeam::factory()->create(['user_id' => User::factory()->create()->id]);

        $this
            ->actAs(ROLE::BASIC)
            ->get(action([TagTeamsController::class, 'index'], $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_a_tag_team_profile()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->get(action([TagTeamsController::class, 'show'], $tagTeam))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function deletes_a_tag_team_and_redirects()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(ROLE::ADMINISTRATOR)
            ->delete(action([TagTeamsController::class, 'destroy'], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        $this->assertSoftDeleted($tagTeam);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_delete_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(ROLE::BASIC)
            ->delete(action([TagTeamsController::class, 'destroy'], $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_delete_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->delete(action([TagTeamsController::class, 'destroy'], $tagTeam))
            ->assertRedirect(route('login'));
    }
}
