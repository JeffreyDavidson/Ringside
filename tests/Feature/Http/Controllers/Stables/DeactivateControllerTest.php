<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Stables\DeactivateController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class DeactivateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_deactivates_an_active_stable_and_its_members_and_redirects()
    {
        $stable = Stable::factory()->active()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([DeactivateController::class], $stable))
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) {
            $this->assertNotNull($stable->activations->last()->ended_at);
            $this->assertEquals(StableStatus::inactive(), $stable->status);

            foreach ($stable->currentWrestlers as $wrestler) {
                $this->assertEquals(WrestlerStatus::released(), $wrestler->status);
            }

            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->assertEquals(TagTeamStatus::released(), $tagTeam->status);
            }
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_deactivates_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([DeactivateController::class], $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_deactivates_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->patch(action([DeactivateController::class], $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nondeactivatableStableTypes
     */
    public function invoke_throws_exception_for_deactivating_a_non_deactivatable_stable($factoryState)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([DeactivateController::class], $stable));
    }

    public function nondeactivatableStableTypes()
    {
        return [
            'inactive stable' => ['inactive'],
            'retired stable' => ['retired'],
            'unactivated stable' => ['unactivated'],
            'with future activated stable' => ['withFutureActivation'],
        ];
    }
}
