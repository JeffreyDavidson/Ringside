<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Requests\Wrestlers\ReinstateRequest;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
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
    public function invoke_reinstates_a_suspended_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = Wrestler::factory()->suspended()->create();

        $response = $this->reinstateRequest($wrestler);

        $this->assertEquals($now->toDateTimeString(), $wrestler->fresh()->suspensions()->latest()->first()->ended_at);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertCount(1, $wrestler->suspensions);
            $this->assertEquals($now->toDateTimeString(), $wrestler->suspensions->first()->ended_at->toDateTimeString());
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
    public function a_basic_user_cannot_reinstate_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = Wrestler::factory()->create();

        $this->reinstateRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->reinstateRequest($wrestler)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_bookable_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->bookable()->create();

        $this->reinstateRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->reinstateRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_an_injured_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->injured()->create();

        $this->reinstateRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->released()->create();

        $this->reinstateRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this->reinstateRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->retired()->create();

        $this->reinstateRequest($wrestler);
    }
}
