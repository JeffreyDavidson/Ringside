<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\TagTeams\EmployController;
use App\Http\Requests\TagTeams\EmployRequest;
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
class EmployControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_future_employed_tag_team_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $response = $this->employRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
            $this->assertCount(1, $tagTeam->employments);
            $this->assertEquals(
                $now->toDateTimeString(),
                $tagTeam->employments->first()->started_at->toDateTimeString()
            );
            $tagTeam->currentWrestlers->each(
                fn (Wrestler $wrestler) => $this->assertTrue($wrestler->isCurrentlyEmployed())
            );
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_an_unemployed_tag_team_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = Tagteam::factory()->unemployed()->create();

        $response = $this->employRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagteamStatus::BOOKABLE, $tagTeam->status);
            $this->assertCount(1, $tagTeam->employments);
            $this->assertEquals($now->toDateTimeString(), $tagTeam->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_released_tagteam_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = Tagteam::factory()->released()->create();

        $response = $this->employRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagteamStatus::BOOKABLE, $tagTeam->status);
            $this->assertCount(2, $tagTeam->employments);
            $this->assertEquals($now->toDateTimeString(), $tagTeam->employments->last()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            EmployController::class,
            '__invoke',
            EmployRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_employ_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->employRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->employRequest($tagTeam)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_bookable_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->bookable()->create();

        $this->employRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_retired_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->retired()->create();

        $this->employRequest($tagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_suspended_tag_team_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->suspended()->create();

        $this->employRequest($tagTeam);
    }
}
