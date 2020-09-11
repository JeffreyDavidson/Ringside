<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Referees\ReinstateController;
use App\Http\Requests\Referees\ReinstateRequest;
use App\Models\Referee;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group srm
 * @group feature-srm
 * @group roster
 * @group feature-roster
 */
class ReinstateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_reinstates_a_suspended_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = Referee::factory()->suspended()->create();

        $response = $this->reinstateRequest($referee);

        $this->assertEquals($now->toDateTimeString(), $referee->fresh()->suspensions()->latest()->first()->ended_at);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
            $this->assertCount(1, $referee->suspensions);
            $this->assertEquals($now->toDateTimeString(), $referee->suspensions->first()->ended_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            ReinstateController::class,
            '__invoke',
            ReinstateRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->create();

        $this->reinstateRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->reinstateRequest($referee)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_bookable_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->bookable()->create();

        $this->reinstateRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_an_unemployed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->unemployed()->create();

        $this->reinstateRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_an_injured_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->injured()->create();

        $this->reinstateRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_released_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->released()->create();

        $this->reinstateRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_future_employed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->reinstateRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_retired_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->retired()->create();

        $this->reinstateRequest($referee);
    }
}
