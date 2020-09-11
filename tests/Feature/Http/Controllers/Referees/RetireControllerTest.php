<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Referees\RetireController;
use App\Http\Requests\Referees\RetireRequest;
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
class RetireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_bookable_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = Referee::factory()->bookable()->create();

        $response = $this->retireRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::RETIRED, $referee->status);
            $this->assertCount(1, $referee->retirements);
            $this->assertEquals($now->toDateTimeString(), $referee->retirements->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_an_injured_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = Referee::factory()->injured()->create();

        $response = $this->retireRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::RETIRED, $referee->status);
            $this->assertCount(1, $referee->retirements);
            $this->assertEquals($now->toDateTimeString(), $referee->retirements->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_suspended_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = Referee::factory()->suspended()->create();

        $response = $this->retireRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::RETIRED, $referee->status);
            $this->assertCount(1, $referee->retirements);
            $this->assertEquals($now->toDateTimeString(), $referee->retirements->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            RetireController::class,
            '__invoke',
            RetireRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->create();

        $this->retireRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->retireRequest($referee)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_retired_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->retired()->create();

        $this->retireRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_future_employed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->retireRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_released_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->released()->create();

        $this->retireRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_unemployed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->retired()->create();

        $this->retireRequest($referee);
    }
}
