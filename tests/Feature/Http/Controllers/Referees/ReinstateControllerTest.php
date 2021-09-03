<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\ReinstateController;
use App\Http\Requests\Referees\ReinstateRequest;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class ReinstateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_reinstates_a_suspended_referee_and_redirects()
    {
        $referee = Referee::factory()->suspended()->create();

        $this->assertNull($referee->currentSuspension->ended_at);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->suspensions->last()->ended_at);
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
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
    public function a_basic_user_cannot_reinstate_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->create();

        $this->patch(action([ReinstateController::class], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_reinstate_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->patch(action([ReinstateController::class], $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_bookable_referee()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_an_unemployed_referee()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_an_injured_referee()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_released_referee()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_future_employed_referee()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_retired_referee()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $referee));
    }
}
