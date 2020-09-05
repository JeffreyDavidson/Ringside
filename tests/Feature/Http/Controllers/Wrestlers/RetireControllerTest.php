<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use Carbon\Carbon;
use App\Enums\Role;
use Tests\TestCase;
use App\Enums\WrestlerStatus;
use Tests\Factories\WrestlerFactory;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Requests\Wrestlers\RetireRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Wrestlers\RetireController;

/**
 * @group wrestlers
 * @group feature-wrestlers
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
    public function invoke_retires_a_bookable_wrestler_and_redirects($administrators)
    {
        $this->markTestIncomplete();
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->bookable()->create();

        $response = $this->retireRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals($now->toDateTimeString(), $wrestler->retirements->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_an_injured_wrestler_and_redirects($administrators)
    {
        $this->markTestIncomplete();
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->retireRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals($now->toDateTimeString(), $wrestler->retirements->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_suspended_wrestler_and_redirects($administrators)
    {
        $this->markTestIncomplete();
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $response = $this->retireRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals($now->toDateTimeString(), $wrestler->retirements->first()->started_at->toDateTimeString());
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
    public function a_basic_user_cannot_retire_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->create();

        $this->retireRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->create();

        $this->retireRequest($wrestler)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->retired()->create();

        $this->retireRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->withFutureEmployment()->create();

        $this->retireRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->released()->create();

        $this->retireRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->retired()->create();

        $this->retireRequest($wrestler);
    }
}
