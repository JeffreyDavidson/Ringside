<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Referees\ReleaseController;
use App\Http\Requests\Referees\ReleaseRequest;
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
class ReleaseControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_releases_a_bookable_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $referee = Referee::factory()->bookable()->create();

        $this->actAs($administrators)
            ->patch(route('referees.release', $referee))
            ->assertRedirect(route('referees.index'));

        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::RELEASED, $referee->status);
            $this->assertEquals($now->toDateTimeString(), $referee->employments->first()->ended_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_releases_an_injured_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $referee = Referee::factory()->injured()->create();

        $this->actAs($administrators)
            ->patch(route('referees.release', $referee))
            ->assertRedirect(route('referees.index'));

        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::RELEASED, $referee->status);
            $this->assertEquals($now->toDateTimeString(), $referee->employments->first()->ended_at->toDateTimeString());
            $this->assertEquals($now->toDateTimeString(), $referee->injuries->first()->ended_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_releases_a_suspended_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $referee = Referee::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('referees.release', $referee))
            ->assertRedirect(route('referees.index'));

        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::RELEASED, $referee->status);
            $this->assertEquals($now->toDateTimeString(), $referee->employments->first()->ended_at->toDateTimeString());
            $this->assertEquals($now->toDateTimeString(), $referee->suspensions->first()->ended_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ReleaseController::class, '__invoke', ReleaseRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('referees.release', $referee))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_release_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->patch(route('referees.release', $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_an_unemployed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('referees.release', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_a_future_employed_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->patch(route('referees.release', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_a_released_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('referees.release', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_a_retired_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('referees.release', $referee));
    }
}
