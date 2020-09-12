<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\TagTeams\UnretireController;
use App\Http\Requests\TagTeams\UnretireRequest;
use App\Models\TagTeam;
use Carbon\Carbon;
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
     * @dataProvider administrators
     */
    public function invoke_unretires_a_retired_tag_team_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->retired()->create();

        $response = $this->unretireRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
            $this->assertCount(1, $tagTeam->retirements);
            $this->assertEquals($now->toDateTimeString(), $tagTeam->retirements->first()->ended_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->unretireRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->unretireRequest($tagTeam)->assertRedirect(route('login'));
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            UnretireController::class,
            '__invoke',
            UnretireRequest::class
        );
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_bookable_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->bookable()->create();

        $this->unretireRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_future_employed_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $this->unretireRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_released_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->released()->create();

        $this->unretireRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_suspended_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->suspended()->create();

        $this->unretireRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_unemployed_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->unretireRequest($tagTeam);
    }
}
