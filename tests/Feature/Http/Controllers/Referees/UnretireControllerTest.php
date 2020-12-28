<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Referees\UnretireController;
use App\Http\Requests\Referees\UnretireRequest;
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
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_unretires_a_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = Referee::factory()->retired()->create();

        $response = $this->patch(route('referees.unretire', $referee));

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
            $this->assertCount(1, $referee->retirements);
            $this->assertEquals($now->toDateTimeString(), $referee->retirements->first()->ended_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(UnretireController::class, '__invoke', UnretireRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->create();

        $this->patch(route('referees.unretire', $referee))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->patch(route('referees.unretire', $referee))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_bookable_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->bookable()->create();

        $this->patch(route('referees.unretire', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_future_employed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->patch(route('referees.unretire', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_injured_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->injured()->create();

        $this->patch(route('referees.unretire', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_released_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->released()->create();

        $this->patch(route('referees.unretire', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_suspended_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->suspended()->create();

        $this->patch(route('referees.unretire', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_unemployed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $referee = Referee::factory()->unemployed()->create();

        $this->patch(route('referees.unretire', $referee));
    }
}
