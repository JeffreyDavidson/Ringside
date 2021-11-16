<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-rosters
 */
class SuspendControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_suspends_a_tag_team_and_their_tag_team_partners_and_redirects()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([SuspendController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertCount(1, $tagTeam->suspensions);
            $this->assertEquals(TagTeamStatus::suspended(), $tagTeam->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_suspend_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([SuspendController::class], $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_suspend_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->patch(action([SuspendController::class], $tagTeam))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonsuspendableTagTeamTypes
     */
    public function invoke_throws_exception_for_suspending_a_non_suspendable_tag_team($factoryState)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([SuspendController::class], $tagTeam));
    }

    public function nonsuspendableTagTeamTypes()
    {
        return [
            'suspended tag team' => ['suspended'],
            'unemployed tag team' => ['unemployed'],
            'released tag team' => ['released'],
            'with future employed tag team' => ['withFutureEmployment'],
            'retired tag team' => ['retired'],
        ];
    }
}
