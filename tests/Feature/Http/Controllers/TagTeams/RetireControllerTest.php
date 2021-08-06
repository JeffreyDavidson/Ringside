<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\TagTeams\RetireController;
use App\Http\Requests\TagTeams\RetireRequest;
use App\Models\TagTeam;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class RetireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_bookable_tag_team_and_its_wrestlers_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $tagTeam = TagTeam::factory()->bookable()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam))
            ->assertRedirect(route('tag-teams.index'));

        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);
            $this->assertEquals($now->toDateTimeString(), $tagTeam->retirements->first()->started_at->toDateTimeString());

            $tagTeam->currentWrestlers->each(function ($wrestler) use ($now) {
                $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
                $this->assertEquals($now->toDateTimeString(), $wrestler->retirements->first()->started_at->toDateTimeString());
            });
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_suspended_tag_team_and_its_wrestlers_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $tagTeam = TagTeam::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam))
            ->assertRedirect(route('tag-teams.index'));

        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);
            $this->assertEquals($now->toDateTimeString(), $tagTeam->retirements->first()->started_at->toDateTimeString());

            $tagTeam->currentWrestlers->each(function ($wrestler) use ($now) {
                $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $wrestler->retirements->first()->started_at->toDateTimeString()
                );
            });
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_an_unbookable_tag_team_and_its_wrestlers_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $tagTeam = TagTeam::factory()->unbookable()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam))
            ->assertRedirect(route('tag-teams.index'));

        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);
            $this->assertEquals($now->toDateTimeString(), $tagTeam->retirements->first()->started_at->toDateTimeString());

            $tagTeam->currentWrestlers->each(function ($wrestler) use ($now) {
                $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $wrestler->retirements->first()->started_at->toDateTimeString()
                );
            });
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(RetireController::class, '__invoke', RetireRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_retire_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('tag-teams.retire', $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_retire_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->patch(route('tag-teams.retire', $tagTeam))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_retired_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_future_employed_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_released_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_unemployed_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam));
    }
}
