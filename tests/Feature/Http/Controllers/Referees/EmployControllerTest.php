<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Referees\EmployController;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Requests\Referees\EmployRequest;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class EmployControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_an_unemployed_referee_and_redirects($administrators)
    {
        $referee = Referee::factory()->unemployed()->create();

        $this->assertCount(0, $referee->employments);
        $this->assertEquals(RefereeStatus::UNEMPLOYED, $referee->status);

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertCount(1, $referee->employments);
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_future_employed_referee_and_redirects($administrators)
    {
        $referee = Referee::factory()->withFutureEmployment()->create();
        $startedAt = $referee->employments->last()->started_at;

        $this->assertTrue(now()->lt($startedAt));
        $this->assertEquals(RefereeStatus::FUTURE_EMPLOYMENT, $referee->status);

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) use ($startedAt) {
            $this->assertTrue($referee->currentEmployment->started_at->lt($startedAt));
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_released_referee_and_redirects($administrators)
    {
        $referee = Referee::factory()->released()->create();

        $this->assertEquals(RefereeStatus::RELEASED, $referee->status);

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertCount(2, $referee->employments);
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(EmployController::class, '__invoke', EmployRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_employ_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([EmployController::class], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_employ_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->patch(action([EmployController::class], $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_employing_an_employed_referee($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->employed()->create();

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $referee));
    }
}
