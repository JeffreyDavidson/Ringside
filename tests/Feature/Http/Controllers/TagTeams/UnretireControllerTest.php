<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\TagTeams\UnretireController;
use App\Http\Requests\TagTeams\UnretireRequest;
use App\Models\TagTeam;
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
    public function invoke_unretires_a_retired_tag_team_and_its_tag_team_partners_and_redirects($administrators)
    {
        $tagTeam = TagTeam::factory()->retired()->create();

        $this
            ->actAs($administrators)
            ->patch(action([UnretireController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertNotNull($tagTeam->retirements->last()->ended_at);
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertNotNull($wrestler->retirements->last()->ended_at);
                $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(UnretireController::class, '__invoke', UnretireRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_unretire_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([UnretireController::class], $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_unretire_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->patch(action([UnretireController::class], $tagTeam))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_unretiring_a_bookable_tag_team($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->bookable()->create();

        $this
            ->actAs($administrators)
            ->patch(action([UnretireController::class], $tagTeam));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_unretiring_a_future_employed_tag_team($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $this
            ->actAs($administrators)
            ->patch(action([UnretireController::class], $tagTeam));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_unretiring_a_released_tag_team($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->released()->create();

        $this
            ->actAs($administrators)
            ->patch(action([UnretireController::class], $tagTeam));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_unretiring_a_suspended_tag_team($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->suspended()->create();

        $this
            ->actAs($administrators)
            ->patch(action([UnretireController::class], $tagTeam));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_unretiring_an_unemployed_tag_team($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this
            ->actAs($administrators)
            ->patch(action([UnretireController::class], $tagTeam));
    }
}
