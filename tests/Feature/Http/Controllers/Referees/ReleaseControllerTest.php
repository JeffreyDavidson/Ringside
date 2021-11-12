<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\ReleaseController;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-rosters
 */
class ReleaseControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_releases_a_bookable_referee_and_redirects()
    {
        $referee = Referee::factory()->bookable()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->employments->last()->ended_at);
            $this->assertEquals(RefereeStatus::released(), $referee->status);
        });
    }

    /**
     * @test
     */
    public function invoke_releases_an_injured_referee_and_redirects()
    {
        $referee = Referee::factory()->injured()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->injuries->last()->ended_at);
            $this->assertNotNull($referee->employments->last()->ended_at);
            $this->assertEquals(RefereeStatus::released(), $referee->status);
        });
    }

    /**
     * @test
     */
    public function invoke_releases_a_suspended_referee_and_redirects()
    {
        $referee = Referee::factory()->suspended()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->suspensions->last()->ended_at);
            $this->assertNotNull($referee->employments->last()->ended_at);
            $this->assertEquals(RefereeStatus::released(), $referee->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_suspend_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([ReleaseController::class], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_release_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->patch(action([ReleaseController::class], $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_releasing_an_unemployed_referee()
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->unemployed()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_releasing_a_future_employed_referee()
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $referee));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_releasing_a_released_referee()
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->released()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $referee));
    }

    /**
     * @test
     * @dataProvider nonreleasableRefereeTypes
     */
    public function invoke_throws_exception_for_releasing_a_non_releasable_referee($factoryState)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $referee));
    }

    public function nonreleasableRefereeTypes()
    {
        return [
            'unemployed referee' => ['unemployed'],
            'with future employed referee' => ['withFutureEmployment'],
            'released referee' => ['released'],
            'retired referee' => ['retired'],
        ];
    }
}
