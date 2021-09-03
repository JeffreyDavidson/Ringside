<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\RetireController;
use App\Http\Requests\Referees\RetireRequest;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class RetireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_retires_a_bookable_referee_and_redirects()
    {
        $referee = Referee::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertCount(1, $referee->retirements);
            $this->assertEquals(RefereeStatus::RETIRED, $referee->status);
        });
    }

    /**
     * @test
     */
    public function invoke_retires_an_injured_referee_and_redirects()
    {
        $referee = Referee::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertCount(1, $referee->retirements);
            $this->assertEquals(RefereeStatus::RETIRED, $referee->status);
        });
    }

    /**
     * @test
     */
    public function invoke_retires_a_suspended_referee_and_redirects()
    {
        $referee = Referee::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertCount(1, $referee->retirements);
            $this->assertEquals(RefereeStatus::RETIRED, $referee->status);
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
    public function a_basic_user_cannot_retire_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([RetireController::class], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_retire_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->patch(action([RetireController::class], $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_retiring_a_retired_referee()
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_retiring_a_future_employed_referee()
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_retiring_an_released_referee()
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_retiring_an_unemployed_referee()
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $referee));
    }
}
