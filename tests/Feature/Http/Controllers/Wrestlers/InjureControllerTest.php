<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Wrestlers\InjureController;
use App\Http\Requests\Wrestlers\InjureRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
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
    public function invoke_injures_a_bookable_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->bookable()->create();

        $response = $this->injureRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::INJURED, $wrestler->status);
            $this->assertCount(1, $wrestler->injuries);
            $this->assertEquals($now->toDateTimeString(), $wrestler->injuries->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            InjureController::class,
            '__invoke',
            InjureRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_injure_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->withFutureEmployment()->create();

        $this->employRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_injure_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->create();

        $this->injureRequest($wrestler)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->unemployed()->create();

        $this->injureRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_suspended_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->suspended()->create();

        $this->injureRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->released()->create();

        $this->injureRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->withFutureEmployment()->create();

        $this->injureRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->retired()->create();

        $this->injureRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_an_injured_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->injured()->create();

        $this->injureRequest($wrestler);
    }
}
