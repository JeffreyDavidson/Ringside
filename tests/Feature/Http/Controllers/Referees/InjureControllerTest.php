<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Referees\InjureController;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Requests\Referees\InjureRequest;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class InjureControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_injures_a_bookable_referee_and_redirects()
    {
        $referee = Referee::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertCount(1, $referee->injuries);
            $this->assertEquals(RefereeStatus::INJURED, $referee->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(InjureController::class, '__invoke', InjureRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_injure_a_referee()
    {
        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->actAs(Role::BASIC)
            ->patch(action([InjureController::class], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_injure_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->patch(action([InjureController::class], $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_injuring_an_unemployed_referee()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_injuring_a_suspended_referee()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_injuring_a_released_referee()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_injuring_a_future_employed_referee()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_injuring_a_retired_referee()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $referee));
    }

    /**
     * @test
     * @dataProvider noninjurableRefereeTypes
     */
    public function invoke_throws_exception_for_injuring_a_non_injurable_referee($factoryState)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $referee));
    }

    public function noninjurableRefereeTypes()
    {
        return [
            'unemployed referee' => ['unemployed'],
            'suspended referee' => ['suspended'],
            'released referee' => ['released'],
            'with future employed referee' => ['withFutureEmployment'],
            'retired referee' => ['retired'],
            'injured referee' => ['injured'],
        ];
    }
}
