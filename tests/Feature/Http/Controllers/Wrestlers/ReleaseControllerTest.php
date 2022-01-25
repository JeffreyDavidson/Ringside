<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Wrestlers\ReleaseController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-rosters
 */
class ReleaseControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_releases_a_bookable_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertNotNull($wrestler->employments->last()->ended_at);
            $this->assertEquals(WrestlerStatus::released(), $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function invoke_releases_an_injured_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertNotNull($wrestler->injuries->last()->ended_at);
            $this->assertNotNull($wrestler->employments->last()->ended_at);
            $this->assertEquals(WrestlerStatus::released(), $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function invoke_releases_a_suspended_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->suspended()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertNotNull($wrestler->suspensions->last()->ended_at);
            $this->assertNotNull($wrestler->employments->last()->ended_at);
            $this->assertEquals(WrestlerStatus::released(), $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function releasing_a_bookable_wrestler_on_a_bookable_tag_team_makes_tag_team_unbookable()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $wrestler));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::UNbookable(), $tagTeam->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_suspend_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([ReleaseController::class], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_release_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->patch(action([ReleaseController::class], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     *
     * @dataProvider nonreleasableWrestlerTypes
     */
    public function invoke_throws_an_exception_for_releasing_a_non_releasable_wrestler($factoryState)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $wrestler));
    }

    public function nonreleasableWrestlerTypes()
    {
        return [
            'unemployed wrestler' => ['unemployed'],
            'with future employed wrestler' => ['withFutureEmployment'],
            'released wrestler' => ['released'],
            'retired wrestler' => ['retired'],
        ];
    }
}
