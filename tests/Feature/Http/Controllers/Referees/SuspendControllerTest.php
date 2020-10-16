<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Referees\SuspendController;
use App\Http\Requests\Referees\SuspendRequest;
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
 * @group feature-rosters
 */
class SuspendControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_suspends_a_bookable_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = Referee::factory()->bookable()->create();

        $response = $this->suspendRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::SUSPENDED, $referee->status);
            $this->assertCount(1, $referee->suspensions);
            $this->assertEquals($now->toDateTimeString(), $referee->suspensions->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            SuspendController::class,
            '__invoke',
            SuspendRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->create();

        $this->suspendRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_suspend_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->suspendRequest($referee)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_an_unemployed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->unemployed()->create();

        $this->suspendRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_future_employed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->suspendRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_an_injured_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->injured()->create();

        $this->suspendRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_released_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->released()->create();

        $this->suspendRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_retired_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->retired()->create();

        $this->suspendRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_suspended_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->suspended()->create();

        $this->suspendRequest($referee);
    }
}