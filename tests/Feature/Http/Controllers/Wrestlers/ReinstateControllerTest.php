<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\ReinstateRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class ReinstateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_reinstates_a_suspended_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->suspended()->create();

        $this->assertNull($wrestler->currentSuspension->ended_at);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertNotNull($wrestler->suspensions->last()->ended_at);
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function reinstating_a_suspended_wrestler_on_an_unbookable_tag_team_makes_tag_team_bookable()
    {
        $tagTeam = TagTeam::factory()->withSuspendedWrestler()->create();
        $wrestler = $tagTeam->currentWrestlers()->suspended()->first();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $wrestler));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
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
    public function a_basic_user_cannot_reinstate_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([ReinstateController::class], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_reinstate_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->patch(action([ReinstateController::class], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_bookable_wrestler()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_an_unemployed_wrestler()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_an_injured_wrestler()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_released_wrestler()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_future_employed_wrestler()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_retired_wrestler()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $wrestler));
    }
}
