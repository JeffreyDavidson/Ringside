<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Stables\DeactivateController;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Requests\Stables\DeactivateRequest;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class DeactivateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_deactivates_an_active_stable_and_its_members_and_redirects($administrators)
    {
        $stable = Stable::factory()->active()->create();

        $this
            ->actAs($administrators)
            ->patch(action([DeactivateController::class], $stable))
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) {
            $this->assertNotNull($stable->activations->last()->ended_at);
            $this->assertEquals(StableStatus::INACTIVE, $stable->status);

            foreach ($stable->currentWrestlers as $wrestler) {
                $this->assertEquals(WrestlerStatus::RELEASED, $wrestler->status);
            }

            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->assertEquals(TagTeamStatus::RELEASED, $tagTeam->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(DeactivateController::class, '__invoke', DeactivateRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_deactivates_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::BASIC)
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
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_deactivating_an_inactive_stable($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->inactive()->create();

        $this
            ->actAs($administrators)
            ->patch(action([DeactivateController::class], $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_deactivating_an_retired_stable($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->retired()->create();

        $this
            ->actAs($administrators)
            ->patch(action([DeactivateController::class], $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_deactivating_an_unactivated_stable($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->unactivated()->create();

        $this
            ->actAs($administrators)
            ->patch(action([DeactivateController::class], $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_deactivating_a_future_activated_stable($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->withFutureActivation()->create();

        $this
            ->actAs($administrators)
            ->patch(action([DeactivateController::class], $stable));
    }
}
