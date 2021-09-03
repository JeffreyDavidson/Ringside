<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\TagTeams\ReinstateController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Requests\TagTeams\ReinstateRequest;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class ReinstateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_reinstates_a_suspended_tag_team_and_its_tag_team_partners_and_redirects()
    {
        $tagTeam = TagTeam::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertNotNull($tagTeam->suspensions->last()->ended_at);
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertNotNull($wrestler->suspensions->last()->ended_at);
                $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ReinstateController::class, '__invoke', ReinstateRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_reinstate_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([ReinstateController::class], $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_reinstate_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->patch(action([ReinstateController::class], $tagTeam))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_bookable_tag_team()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_future_employed_tag_team()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_an_unemployed_tag_team()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_released_tag_team()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_retired_tag_team()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $tagTeam));
    }
}
