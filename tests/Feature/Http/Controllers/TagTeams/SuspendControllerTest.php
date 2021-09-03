<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Requests\TagTeams\SuspendRequest;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-rosters
 */
class SuspendControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_suspends_a_tag_team_and_their_tag_team_partners_and_redirects()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertCount(1, $tagTeam->suspensions);
            $this->assertEquals(TagTeamStatus::SUSPENDED, $tagTeam->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(SuspendController::class, '__invoke', SuspendRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_suspend_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(Role::BASIC)
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
     */
    public function suspending_a_suspended_tag_team_throws_an_exception()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function suspending_an_unemployed_tag_team_throws_an_exception()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function suspending_a_released_tag_team_throws_an_exception()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function suspending_a_future_employed_tag_team_throws_an_exception()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function suspending_a_retired_tag_team_throws_an_exception()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $tagTeam));
    }
}
