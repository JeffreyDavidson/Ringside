<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\UnretireController;
use App\Http\Requests\Referees\UnretireRequest;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_unretires_a_referee_and_redirects()
    {
        $referee = Referee::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->retirements->last()->ended_at);
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
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
    public function a_basic_user_cannot_unretire_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([UnretireController::class], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_unretire_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->patch(action([UnretireController::class], $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_a_bookable_referee()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_a_future_employed_referee()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_an_injured_referee()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_a_released_referee()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_a_suspended_referee()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_an_unemployed_referee()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $referee));
    }
}
