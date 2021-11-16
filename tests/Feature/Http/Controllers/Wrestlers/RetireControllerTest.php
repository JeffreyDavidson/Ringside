<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Wrestlers\RetireController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class RetireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_retires_a_bookable_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([RetireController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals(WrestlerStatus::retired(), $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function invoke_retires_an_injured_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([RetireController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals(WrestlerStatus::retired(), $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function invoke_retires_a_suspended_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->suspended()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([RetireController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->retirements);
            $this->assertEquals(WrestlerStatus::retired(), $wrestler->status);
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
            ->actAs(Role::administrator())
            ->patch(action([RetireController::class], $wrestler));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::UNbookable(), $tagTeam->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_retire_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::basic())
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
     * @dataProvider nonretirableWrestlerTypes
     */
    public function invoke_throws_an_exception_for_retiring_a_non_retirable_wrestler($factoryState)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([RetireController::class], $wrestler));
    }

    public function nonretirableWrestlerTypes()
    {
        return [
            'retired wrestler' => ['retired'],
            'with future employed wrestler' => ['withFutureEmployment'],
            'released wrestler' => ['released'],
            'unemployed wrestler' => ['unemployed'],
        ];
    }
}
