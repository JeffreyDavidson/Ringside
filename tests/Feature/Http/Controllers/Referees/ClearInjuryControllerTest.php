<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Referees\ClearInjuryController;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Requests\Referees\ClearInjuryRequest;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class ClearInjuryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_marks_an_injured_referee_as_being_cleared_and_redirects()
    {
        $referee = Referee::factory()->injured()->create();

        $this->assertNull($referee->injuries->last()->ended_at);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->injuries->last()->ended_at);
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ClearInjuryController::class, '__invoke', ClearInjuryRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_mark_an_injured_referee_as_cleared()
    {
        $referee = Referee::factory()->injured()->create();

        $this->actAs(Role::BASIC)
            ->patch(action([ClearInjuryController::class], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_mark_an_injured_referee_as_cleared()
    {
        $referee = Referee::factory()->injured()->create();

        $this
            ->patch(action([ClearInjuryController::class], $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_an_unemployed_referee()
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_bookable_referee()
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_future_employed_referee()
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_suspended_referee()
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_retired_referee()
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_released_referee()
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $referee));
    }
}
