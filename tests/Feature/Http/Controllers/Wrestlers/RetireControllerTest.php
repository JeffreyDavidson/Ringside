<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Wrestlers\RetireController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\RetireRequest;
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
class RetireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_retires_a_bookable_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function invoke_retires_an_injured_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function invoke_retires_a_suspended_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function retiring_a_bookable_wrestler_on_a_bookable_tag_team_makes_tag_team_unbookable()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->wrestlers()->first();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $wrestler));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::UNBOOKABLE, $tagTeam->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(RetireController::class, '__invoke', RetireRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_retire_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(route('wrestlers.retire', $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_retire_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->patch(action([RetireController::class], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_retiring_a_retired_wrestler()
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_retiring_a_future_employed_wrestler()
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_retiring_a_released_wrestler()
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_an_exception_for_retiring_an_unemployed_wrestler()
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $wrestler));
    }
}
