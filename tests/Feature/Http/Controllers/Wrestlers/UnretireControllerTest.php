<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Wrestlers\UnretireController;
use App\Http\Requests\Wrestlers\UnretireRequest;
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
        $wrestler = Wrestler::factory()->retired()->create();

        $response = $this->unretireRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->retirements->first()->ended_at->toDateTimeString('minute'));
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
        $wrestler = Wrestler::factory()->create();

        $this->unretireRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

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

        $wrestler = Wrestler::factory()->bookable()->create();

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

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

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

        $wrestler = Wrestler::factory()->injured()->create();

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

        $wrestler = Wrestler::factory()->released()->create();

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

        $wrestler = Wrestler::factory()->suspended()->create();

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

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->unretireRequest($wrestler);
    }
}
