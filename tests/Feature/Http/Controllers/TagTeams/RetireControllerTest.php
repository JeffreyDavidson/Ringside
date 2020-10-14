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

        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->bookable()->create();
        // dd($tagTeam->first()->employments);

        $response = $this->retireRequest($tagTeam);
        dd($response);

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);
            $this->assertEquals(
                $now->toDateTimeString(),
                $tagTeam->retirements->first()->started_at->toDateTimeString()
            );

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
    public function invoke_retires_a_suspended_tag_team_and_its_wrestlers_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->suspended()->create();

        $response = $this->retireRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));

        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);
            $this->assertEquals(
                $now->toDateTimeString(),
                $tagTeam->retirements->first()->started_at->toDateTimeString()
            );

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

        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->unbookable()->create();

        $response = $this->retireRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));

        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);
            $this->assertEquals(
                $now->toDateTimeString(),
                $tagTeam->retirements->first()->started_at->toDateTimeString()
            );

            $tagTeam->currentWrestlers->each(function ($wrestler) use ($now) {
                $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $wrestler->retirements->first()->started_at->toDateTimeString()
                );
            });
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            RetireController::class,
            '__invoke',
            RetireRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->retireRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->retireRequest($tagTeam)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_retired_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->retired()->create();

        $this->retireRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_future_employed_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $this->retireRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_released_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->released()->create();

        $this->retireRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_unemployed_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->retireRequest($tagTeam);
    }
}
