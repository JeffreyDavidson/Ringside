<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Wrestlers\ClearInjuryController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\ClearInjuryRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
     */
    public function invoke_marks_an_injured_wrestler_as_being_cleared_from_injury_and_redirects()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        $this->assertNull($wrestler->injuries->last()->ended_at);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertNotNull($wrestler->injuries->last()->ended_at);
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function clearing_an_injured_wrestler_on_an_unbookable_tag_team_makes_tag_team_bookable()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(route('wrestlers.clear-from-injury', $wrestler));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertTrue($wrestler->isBookable());
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ClearInjuryController::class, '__invoke', ClearInjuryRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_mark_an_injured_wrestler_as_cleared()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([ClearInjuryController::class], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_mark_an_injured_wrestler_as_cleared()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        $this
            ->patch(action([ClearInjuryController::class], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_clearing_an_injury_from_an_unemployed_wrestler()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_clearing_an_injury_from_a_future_employed_wrestler()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_clearing_an_injury_from_a_bookable_wrestler()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $wrestler = Wrestler::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_clearing_an_injury_from_a_retired_wrestler()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $wrestler = Wrestler::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_clearing_an_injury_from_a_suspended_wrestler()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $wrestler = Wrestler::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ClearInjuryController::class], $wrestler));
    }
}
