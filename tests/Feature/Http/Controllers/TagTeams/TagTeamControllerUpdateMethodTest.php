<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamRequestDataFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class TagTeamControllerUpdateMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function edit_returns_a_view()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([TagTeamsController::class, 'edit'], $tagTeam))
            ->assertViewIs('tagteams.edit')
            ->assertViewHas('tagTeam', $tagTeam);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_editing_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(Role::basic())
            ->get(action([TagTeamsController::class, 'edit'], $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_editing_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->get(action([TagTeamsController::class, 'edit'], $tagTeam))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function updates_a_tag_team_and_redirects()
    {
        $tagTeam = TagTeam::factory()->create(['name' => 'Old Tag Team Name']);

        $this
            ->actAs(Role::administrator())
            ->from(action([TagTeamsController::class, 'edit'], $tagTeam))
            ->put(
                action([TagTeamsController::class, 'update'], $tagTeam),
                TagTeamRequestDataFactory::new()->withTagTeam($tagTeam)->create([
                    'name' => 'New Tag Team Name',
                ])
            )
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals('New Tag Team Name', $tagTeam->name);
        });
    }

    /**
     * @test
     */
    public function wrestlers_of_tag_team_are_synced_when_tag_team_is_updated()
    {
        $tagTeam = TagTeam::factory()->create();
        $formerTagTeamPartners = $tagTeam->currentWrestlers;
        $newTagTeamPartners = Wrestler::factory()->count(2)->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([TagTeamsController::class, 'edit'], $tagTeam))
            ->put(
                action([TagTeamsController::class, 'update'], $tagTeam),
                TagTeamRequestDataFactory::new()->create(['wrestlers' => $newTagTeamPartners->pluck('id')->toArray()])
            )
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) use ($formerTagTeamPartners, $newTagTeamPartners) {
            $this->assertCount(4, $tagTeam->wrestlers);
            $this->assertCount(2, $tagTeam->currentWrestlers);
            $this->assertCollectionHas($tagTeam->currentWrestlers, $newTagTeamPartners[0]);
            $this->assertCollectionHas($tagTeam->currentWrestlers, $newTagTeamPartners[1]);
            $this->assertCollectionDoesntHave($tagTeam->currentWrestlers, $formerTagTeamPartners[0]);
            $this->assertCollectionDoesntHave($tagTeam->currentWrestlers, $formerTagTeamPartners[1]);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_update_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(Role::basic())
            ->from(action([TagTeamsController::class, 'edit'], $tagTeam))
            ->put(action([TagTeamsController::class, 'update'], $tagTeam), TagTeamRequestDataFactory::new()->create())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_update_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->from(action([TagTeamsController::class, 'edit'], $tagTeam))
            ->put(action([TagTeamsController::class, 'update'], $tagTeam), TagTeamRequestDataFactory::new()->create())
            ->assertRedirect(route('login'));
    }
}
