<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\SuspendController;
use App\Http\Requests\Referees\SuspendRequest;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-rosters
 */
class SuspendControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_suspends_a_bookable_referee_and_redirects()
    {
        $referee = Referee::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertCount(1, $referee->suspensions);
            $this->assertEquals(RefereeStatus::SUSPENDED, $referee->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_suspend_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([SuspendController::class], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_suspend_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->patch(action([SuspendController::class], $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_an_unemployed_referee()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_future_employed_referee()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_an_injured_referee()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_released_referee()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_retired_referee()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $referee));
    }

    /**
     * @test
     * @dataProvider nonsuspendableRefereeTypes
     */
    public function invoke_throws_exception_for_suspending_a_non_suspendable_referee($factoryState)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $referee));
    }

    public function nonsuspendableRefereeTypes()
    {
        return [
            'unemployed referee' => ['unemployed'],
            'with future employed referee' => ['withFutureEmployment'],
            'injured referee' => ['injured'],
            'released referee' => ['released'],
            'retired referee' => ['retired'],
            'suspended referee' => ['suspended'],
        ];
    }
}
