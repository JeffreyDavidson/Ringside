<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\TagTeams\UnretireController;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**ss
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_unretires_a_retired_tag_team_and_its_tag_team_partners_and_redirects()
    {
        $tagTeam = TagTeam::factory()->retired()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([UnretireController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertNotNull($tagTeam->retirements->last()->ended_at);
            $this->assertEquals(TagTeamStatus::bookable(), $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertNotNull($wrestler->retirements->last()->ended_at);
                $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
            }
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_unretire_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([UnretireController::class], $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_unretire_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->patch(action([UnretireController::class], $tagTeam))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonunretirableTagTeamTypes
     */
    public function invoke_throws_exception_for_unretiring_a_non_unretirable_tag_team($factoryState)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([UnretireController::class], $tagTeam));
    }

    public function nonunretirableTagTeamTypes()
    {
        return [
            'bookable tag team' => ['bookable'],
            'with future employed tag team' => ['withFutureEmployment'],
            'released tag team' => ['released'],
            'suspended tag team' => ['suspended'],
            'unemployed tag team' => ['unemployed'],
        ];
    }
}
