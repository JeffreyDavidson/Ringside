<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class TagTeamControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function index_returns_a_view()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([TagTeamsController::class, 'index']))
            ->assertOk()
            ->assertViewIs('tagteams.index')
            ->assertSeeLivewire('tag-teams.employed-tag-teams')
            ->assertSeeLivewire('tag-teams.future-employed-and-unemployed-tag-teams')
            ->assertSeeLivewire('tag-teams.released-tag-teams')
            ->assertSeeLivewire('tag-teams.suspended-tag-teams')
            ->assertSeeLivewire('tag-teams.retired-tag-teams');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_tag_teams_index_page()
    {
        $this
            ->actAs(Role::BASIC)
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
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([TagTeamsController::class, 'show'], $tagTeam))
            ->assertViewIs('tagteams.show')
            ->assertViewHas('tagTeam', $tagTeam);
    }

    /**
     * @test
     */
    public function a_basic_user_can_view_their_tag_team_profile()
    {
        $this->actAs(Role::BASIC);
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
            ->actAs(Role::BASIC)
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
            ->actAs(Role::ADMINISTRATOR)
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
            ->actAs(Role::BASIC)
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
