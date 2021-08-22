<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\TagTeams\RetireController;
use App\Http\Requests\TagTeams\RetireRequest;
use App\Models\TagTeam;
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
        $tagTeam = TagTeam::factory()->bookable()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam))
            ->assertRedirect(route('tag-teams.index'));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertCount(1, $tagTeam->retirements);
            $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertCount(1, $wrestler->retirements);
                $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            }
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_suspended_tag_team_and_its_wrestlers_and_redirects($administrators)
    {
        $tagTeam = TagTeam::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam))
            ->assertRedirect(route('tag-teams.index'));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertCount(1, $tagTeam->retirements);
            $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            }
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_an_unbookable_tag_team_and_its_wrestlers_and_redirects($administrators)
    {
        $tagTeam = TagTeam::factory()->unbookable()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam))
            ->assertRedirect(route('tag-teams.index'));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            }
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
    public function invoke_throws_exception_for_retiring_a_retired_tag_team($administrators)
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
    public function invoke_throws_exception_for_retiring_a_future_employed_tag_team($administrators)
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
    public function invoke_throws_exception_for_retiring_a_released_tag_team($administrators)
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
    public function invoke_throws_exception_for_retiring_an_unemployed_tag_team($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('tag-teams.retire', $tagTeam));
    }
}
