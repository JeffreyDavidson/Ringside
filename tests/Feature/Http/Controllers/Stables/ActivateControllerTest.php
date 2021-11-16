<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Stables\ActivateController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class ActivateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_activates_an_unactivated_stable_with_members_and_redirects()
    {
        $stable = Stable::factory()->unactivated()->create();

        $this->assertEquals(StableStatus::unactivated(), $stable->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([ActivateController::class], $stable))
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) {
            $this->assertCount(1, $stable->activations);
            $this->assertEquals(StableStatus::active(), $stable->status);

            foreach ($stable->currentWrestlers as $wrestler) {
                $this->assertCount(1, $wrestler->employments);
                $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
            }

            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->assertCount(1, $tagTeam->employments);
                $this->assertEquals(TagTeamStatus::bookable(), $tagTeam->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_activates_a_future_activated_stable_with_members_and_redirects()
    {
        $stable = Stable::factory()->withFutureActivation()->create();
        $startedAt = $stable->activations->last()->started_at;

        $this->assertTrue(now()->lt($startedAt));
        $this->assertEquals(StableStatus::future_activation(), $stable->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([ActivateController::class], $stable))
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) use ($startedAt) {
            $this->assertTrue($stable->currentActivation->started_at->lt($startedAt));
            $this->assertEquals(StableStatus::active(), $stable->status);

            foreach ($stable->currentWrestlers as $wrestler) {
                $this->assertCount(1, $wrestler->employments);
                $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
            }

            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->assertCount(1, $tagTeam->employments);
                $this->assertEquals(TagTeamStatus::bookable(), $tagTeam->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_activates_an_inactive_stable_with_members_and_redirects()
    {
        $stable = Stable::factory()->inactive()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ActivateController::class], $stable))
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) {
            $this->assertCount(2, $stable->activations);
            $this->assertEquals(StableStatus::active(), $stable->status);

            foreach ($stable->currentWrestlers as $wrestler) {
                $this->assertCount(2, $wrestler->employments);
                $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
            }

            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->assertCount(2, $tagTeam->employments);
                $this->assertEquals(TagTeamStatus::bookable(), $tagTeam->status);
            }
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_activate_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([ActivateController::class], $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_activate_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->patch(action([ActivateController::class], $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonactivatableStableTypes
     */
    public function invoke_throws_exception_for_activating_a_non_activatable_stable($factoryState)
    {
        $this->expectException(CannotBeActivatedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ActivateController::class], $stable));
    }

    public function nonactivatableStableTypes()
    {
        return [
            'retired stable' => ['retired'],
            'active stable' => ['active'],
        ];
    }
}
