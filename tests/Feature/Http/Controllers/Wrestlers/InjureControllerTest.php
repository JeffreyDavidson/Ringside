<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Wrestlers\InjureController;
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
class InjureControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_injures_a_bookable_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        $this->assertCount(0, $wrestler->injuries);

        $this
            ->actAs(Role::administrator())
            ->patch(action([InjureController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->injuries);
            $this->assertEquals(WrestlerStatus::injured(), $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function injuring_a_bookable_wrestler_on_a_bookable_tag_team_makes_tag_team_unbookable()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();

        $this->assertEquals(TagTeamStatus::bookable(), $tagTeam->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([InjureController::class], $wrestler));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::unbookable(), $tagTeam->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_injure_a_wrestler()
    {
        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([InjureController::class], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_injure_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->patch(action([InjureController::class], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     *
     * @dataProvider noninjurableWrestlerTypes
     */
    public function invoke_throws_exception_for_injuring_a_non_injurable_wrestler($factoryState)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([InjureController::class], $wrestler));
    }

    public function noninjurableWrestlerTypes()
    {
        return [
            'unemployed wrestler' => ['unemployed'],
            'suspended wrestler' => ['suspended'],
            'released wrestler' => ['released'],
            'with future employed wrestler' => ['withFutureEmployment'],
            'retired wrestler' => ['retired'],
            'injured wrestler' => ['injured'],
        ];
    }
}
