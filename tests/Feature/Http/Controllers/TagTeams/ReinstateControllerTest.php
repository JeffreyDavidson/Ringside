<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\TagTeams\ReinstateController;
use App\Http\Requests\TagTeams\ReinstateRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
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
     * @dataProvider administrators
     */
    public function invoke_reinstates_a_suspended_tag_team_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->suspended()->create();

        $response = $this->reinstateRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
            $this->assertEquals($now->toDateTimeString(), $tagTeam->fresh()->suspensions()->latest()->first()->ended_at->toDateTimeString());
            $tagTeam->currentWrestlers->each(
                fn (Wrestler $wrestler) => $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status)
            );
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            ReinstateController::class,
            '__invoke',
            ReinstateRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->reinstateRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->reinstateRequest($tagTeam)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_bookable_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->bookable()->create();

        $this->reinstateRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_future_employed_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $this->reinstateRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_an_unemployed_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->reinstateRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_released_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->released()->create();

        $this->reinstateRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_retired_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->retired()->create();

        $this->reinstateRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_suspended_tag_team_with_a_non_suspended_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->suspended()->create();
        $firstWrestler = $tagTeam->currentWrestlers->first();
        $firstWrestler->reinstate();
        $firstWrestler->save();

        $this->reinstateRequest($tagTeam);
    }
}
