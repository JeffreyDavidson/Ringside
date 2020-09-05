<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use Carbon\Carbon;
use App\Enums\Role;
use Tests\TestCase;
use App\Enums\WrestlerStatus;
use Tests\Factories\WrestlerFactory;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Requests\Wrestlers\UnretireRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Wrestlers\UnretireController;

/**
 * @group wrestlers
 * @group feature-wrestlers
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
    public function invoke_unretires_a_retired_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->retired()->create();
        // dd($wrestler);

        $response = $this->unretireRequest($wrestler);
        dd($response);
        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals($now->toDateTimeString(), $wrestler->retirements->first()->ended_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            UnretireController::class,
            '__invoke',
            UnretireRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->create();

        $this->unretireRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->create();

        $this->unretireRequest($wrestler)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_bookable_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->bookable()->create();

        $this->unretireRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->withFutureEmployment()->create();

        $this->unretireRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_injured_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->injured()->create();

        $this->unretireRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->released()->create();

        $this->unretireRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_suspended_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->suspended()->create();

        $this->unretireRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->unemployed()->create();

        $this->unretireRequest($wrestler);
    }
}
