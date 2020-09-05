<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Wrestlers\EmployController;
use App\Http\Requests\Wrestlers\EmployRequest;
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
class EmployControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_future_employed_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->withFutureEmployment()->create();

        $response = $this->employRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);
            $this->assertEquals($now->toDateTimeString(), $wrestler->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_an_unemployed_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->unemployed()->create();

        $response = $this->employRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertCount(1, $wrestler->employments);
            $this->assertEquals($now->toDateTimeString(), $wrestler->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_released_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->released()->create();

        $response = $this->employRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertCount(2, $wrestler->employments);
            $this->assertEquals($now->toDateTimeString(), $wrestler->employments->last()->started_at->toDateTimeString());
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
    public function a_basic_user_cannot_employ_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->withFutureEmployment()->create();

        $this->employRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->withFutureEmployment()->create();

        $this->employRequest($wrestler)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_bookable_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->bookable()->create();

        $this->employRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->markTestIncomplete();
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->retired()->create();

        $this->employRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_suspended_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->suspended()->create();

        $this->employRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_an_injured_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->injured()->create();

        $this->employRequest($wrestler);
    }
}
