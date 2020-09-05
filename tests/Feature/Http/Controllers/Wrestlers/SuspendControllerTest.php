<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use Carbon\Carbon;
use App\Enums\Role;
use Tests\TestCase;
use App\Enums\WrestlerStatus;
use Tests\Factories\WrestlerFactory;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Requests\Wrestlers\SuspendRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Wrestlers\SuspendController;

/**
 * @group wrestlers
 * @group feature-wrestlers
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
    public function invoke_suspends_a_bookable_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->bookable()->create();

        $response = $this->suspendRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::SUSPENDED, $wrestler->status);
            $this->assertCount(1, $wrestler->suspensions);
            $this->assertEquals($now->toDateTimeString(), $wrestler->suspensions->first()->started_at->toDateTimeString());
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
    public function a_basic_user_cannot_suspend_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->create();

        $this->suspendRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_suspend_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->create();

        $this->suspendRequest($wrestler)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->unemployed()->create();

        $this->suspendRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->withFutureEmployment()->create();

        $this->suspendRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_an_injured_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->injured()->create();

        $this->suspendRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->released()->create();

        $this->suspendRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->retired()->create();

        $this->suspendRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_suspended_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->suspended()->create();

        $this->suspendRequest($wrestler);
    }
}
