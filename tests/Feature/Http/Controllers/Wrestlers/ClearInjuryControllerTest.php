<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Wrestlers\ClearInjuryController;
use App\Http\Requests\Wrestlers\ClearInjuryRequest;
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
class ClearInjuryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_marks_an_injured_wrestler_as_being_recovered_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertCount(1, $wrestler->injuries);
            $this->assertEquals($now->toDateTimeString(), $wrestler->injuries->first()->ended_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            ClearInjuryController::class,
            '__invoke',
            ClearInjuryRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_mark_an_injured_wrestler_as_recovered()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $this->clearInjuryRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_mark_an_injured_wrestler_as_recovered()
    {
        $wrestler = WrestlerFactory::new()->injured()->create();

        $this->clearInjuryRequest($wrestler)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->unemployed()->create();

        $this->clearInjuryRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_a_bookable_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->bookable()->create();

        $this->clearInjuryRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->withFutureEmployment()->create();

        $this->clearInjuryRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_a_suspended_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->suspended()->create();

        $this->clearInjuryRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->retired()->create();

        $this->clearInjuryRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = WrestlerFactory::new()->released()->create();

        $this->clearInjuryRequest($wrestler);
    }
}
