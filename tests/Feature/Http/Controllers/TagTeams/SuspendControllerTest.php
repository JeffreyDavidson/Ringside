<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Requests\TagTeams\SuspendRequest;
use App\Models\TagTeam;
use Carbon\Carbon;
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
     * @dataProvider administrators
     */
    public function invoke_suspends_a_tag_team_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->bookable()->create();

        $response = $this->suspendRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertEquals($now->toDateTimeString(), $tagTeam->fresh()->currentSuspension->started_at);
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            SuspendController::class,
            '__invoke',
            SuspendRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->suspendRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_suspend_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->suspendRequest($tagTeam)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_suspended_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->suspended()->create();

        $this->suspendRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_an_unemployed_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->suspendRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_released_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->released()->create();

        $this->suspendRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_future_employed_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $this->suspendRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_retired_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->retired()->create();

        $this->suspendRequest($tagTeam);
    }
}
