<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Wrestlers\ReinstateController;
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
class ReinstateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_reinstates_a_suspended_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->suspended()->create();

        $this->assertNull($wrestler->currentSuspension->ended_at);

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReinstateController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertNotNull($wrestler->suspensions->last()->ended_at);
            $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function reinstating_a_suspended_wrestler_on_an_unbookable_tag_team_makes_tag_team_bookable()
    {
        $tagTeam = TagTeam::factory()->withSuspendedWrestler()->create();
        $wrestler = $tagTeam->currentWrestlers()->suspended()->first();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReinstateController::class], $wrestler));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::bookable(), $tagTeam->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_reinstate_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([ReinstateController::class], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_reinstate_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->patch(action([ReinstateController::class], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     *
     * @dataProvider nonreinstatableWrestlerTypes
     */
    public function invoke_throws_exception_for_reinstating_a_non_reinstatable_wrestler($factoryState)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReinstateController::class], $wrestler));
    }

    public function nonreinstatableWrestlerTypes()
    {
        return [
            'bookable wrestler' => ['bookable'],
            'unemployed wrestler' => ['unemployed'],
            'injured wrestler' => ['injured'],
            'released wrestler' => ['released'],
            'with future employed wrestler' => ['withFutureEmployment'],
            'retired wrestler' => ['retired'],
        ];
    }
}
