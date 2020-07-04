<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class EmployTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_employ_a_pending_employment_tag_team_with_wrestlers($adminRoles)
    {
        $this->actAs($adminRoles);

        $tagTeam = TagTeamFactory::new()->pendingEmployment()->withWrestlers(
            WrestlerFactory::new()->count(2)->pendingEmployment()
        )->create();

        $response = $this->employRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertTrue($tagTeam->isCurrentlyEmployed());
            $tagTeam->currentWrestlers->each(
                fn (Wrestler $wrestler) => $this->assertTrue($wrestler->isCurrentlyEmployed())
            );
        });
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
