<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Referees\InjureController;
use App\Http\Requests\Referees\InjureRequest;
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
class InjureControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_injures_a_bookable_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = Referee::factory()->bookable()->create();

        $response = $this->put(route('referees.injure', $referee));

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::INJURED, $referee->status);
            $this->assertCount(1, $referee->injuries);
            $this->assertEquals($now->toDateTimeString(), $referee->injuries->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(InjureController::class, '__invoke', InjureRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_injure_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->put(route('referees.injure', $referee))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_injure_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->put(route('referees.injure', $referee))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_an_unemployed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->unemployed()->create();

        $this->put(route('referees.injure', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_suspended_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->suspended()->create();

        $this->put(route('referees.injure', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_released_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->released()->create();

        $this->put(route('referees.injure', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_future_employed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->put(route('referees.injure', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_retired_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->retired()->create();

        $this->put(route('referees.injure', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_an_injured_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->injured()->create();

        $this->put(route('referees.injure', $referee));
    }
}
