<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Referees\ClearInjuryController;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class ClearInjuryControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_marks_an_injured_referee_as_being_cleared_and_redirects()
    {
        $referee = Referee::factory()->injured()->create();

        $this->assertNull($referee->injuries->last()->ended_at);

        $this
            ->actAs(Role::administrator())
            ->patch(action([ClearInjuryController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->injuries->last()->ended_at);
            $this->assertEquals(RefereeStatus::bookable(), $referee->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_mark_an_injured_referee_as_cleared()
    {
        $referee = Referee::factory()->injured()->create();

        $this->actAs(Role::basic())
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
     *
     * @dataProvider nonclearableRefereeTypes
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_non_clearable_referee($factoryState)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ClearInjuryController::class], $referee));
    }

    public function nonclearableRefereeTypes()
    {
        return [
            'unemployed referee' => ['unemployed'],
            'bookable referee' => ['bookable'],
            'with future employed referee' => ['withFutureEmployment'],
            'suspended referee' => ['suspended'],
            'retired referee' => ['retired'],
            'released referee' => ['released'],
        ];
    }
}
