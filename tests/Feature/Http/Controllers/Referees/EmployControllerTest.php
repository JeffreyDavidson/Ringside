<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Referees\EmployController;
use App\Http\Requests\Referees\EmployRequest;
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
class EmployControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_future_employed_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = Referee::factory()->withFutureEmployment()->create();

        $response = $this->employRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
            $this->assertCount(1, $referee->employments);
            $this->assertEquals($now->toDateTimeString(), $referee->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_an_unemployed_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = Referee::factory()->unemployed()->create();

        $response = $this->employRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
            $this->assertCount(1, $referee->employments);
            $this->assertEquals($now->toDateTimeString(), $referee->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_released_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = Referee::factory()->released()->create();

        $response = $this->employRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
            $this->assertCount(2, $referee->employments);
            $this->assertEquals($now->toDateTimeString(), $referee->employments->last()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            EmployController::class,
            '__invoke',
            EmployRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_employ_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->employRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_a_referee()
    {
        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->employRequest($referee)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_bookable_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->bookable()->create();

        $this->employRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_retired_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->retired()->create();

        $this->employRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_suspended_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->suspended()->create();

        $this->employRequest($referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_an_injured_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->injured()->create();

        $this->employRequest($referee);
    }
}
