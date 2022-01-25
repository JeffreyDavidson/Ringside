<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\TagTeams\ReleaseController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-rosters
 */
class ReleaseControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_releases_a_bookable_tag_team_and_tag_team_partners_and_redirects()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertNotNull($tagTeam->employments->last()->ended_at);
            $this->assertEquals(TagTeamStatus::released(), $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertEquals(WrestlerStatus::released(), $wrestler->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_releases_a_suspended_tag_team_and_tag_team_partners_redirects()
    {
        $tagTeam = TagTeam::factory()->suspended()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertNotNull($tagTeam->suspensions->last()->ended_at);
            $this->assertNotNull($tagTeam->employments->last()->ended_at);
            $this->assertEquals(TagTeamStatus::released(), $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertEquals(WrestlerStatus::released(), $wrestler->status);
            }
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
            ->patch(action([ReleaseController::class], $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_release_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->patch(action([ReleaseController::class], $tagTeam))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_releasing_an_unemployed_tag_team()
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_releasing_a_future_employed_tag_team()
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_releasing_a_released_tag_team()
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->released()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $tagTeam));
    }

    /**
     * @test
     *
     * @dataProvider nonreleasableTagTeamTypes
     */
    public function invoke_throws_an_exception_for_releasing_a_non_releasable_tag_team($factoryState)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $tagTeam));
    }

    public function nonreleasableTagTeamTypes()
    {
        return [
            'unemployed tag team' => ['unemployed'],
            'with future employed tag team' => ['withFutureEmployment'],
            'released tag team' => ['released'],
            'retired tag team' => ['retired'],
        ];
    }
}
