<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\TagTeams\EmployController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Requests\TagTeams\EmployRequest;
use App\Models\TagTeam;
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
     */
    public function invoke_employs_an_unemployed_tag_team_and_their_tag_team_partners_and_redirects()
    {
        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->assertCount(0, $tagTeam->employments);
        $this->assertEquals(TagTeamStatus::UNEMPLOYED, $tagTeam->status);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertCount(1, $tagTeam->employments);
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertCount(1, $wrestler->employments);
                $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_employs_a_future_employed_tag_team_and_their_tag_team_partners_and_redirects()
    {
        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();
        $startedAt = $tagTeam->employments->last()->started_at;

        $this->assertTrue(now()->lt($startedAt));
        $this->assertEquals(TagTeamStatus::FUTURE_EMPLOYMENT, $tagTeam->status);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) use ($startedAt) {
            $this->assertTrue($tagTeam->currentEmployment->started_at->lt($startedAt));
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertTrue($wrestler->currentEmployment->started_at->lt($startedAt));
                $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_employs_a_released_tag_team_and_their_tag_team_partners_redirects()
    {
        $tagTeam = TagTeam::factory()->released()->create();

        $this->assertEquals(TagTeamStatus::RELEASED, $tagTeam->status);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertCount(2, $tagTeam->employments);
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertCount(2, $wrestler->employments);
                $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(EmployController::class, '__invoke', EmployRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_employ_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([EmployController::class], $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_employ_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->patch(action([EmployController::class], $tagTeam))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_employing_a_bookable_tag_team()
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->bookable()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_employing_a_retired_tag_team()
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->retired()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $tagTeam));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_employing_a_suspended_tag_team()
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->suspended()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $tagTeam));
    }
}
