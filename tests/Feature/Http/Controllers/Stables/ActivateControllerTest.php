<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Stables\ActivateController;
use App\Http\Requests\Stables\ActivateRequest;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class ActivateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_an_unactivated_stable_with_members_and_redirects($administrators)
    {
        $stable = Stable::factory()->unactivated()->create();

        $this->assertEquals(StableStatus::UNACTIVATED, $stable->status);

        $this->actAs($administrators)
            ->patch(route('stables.activate', $stable))
            ->assertRedirect(route('stables.index'));

        tap($stable->fresh(), function ($stable) {
            $this->assertCount(1, $stable->activations);
            $this->assertEquals(StableStatus::ACTIVE, $stable->status);

            foreach ($stable->currentWrestlers as $wrestler) {
                $this->assertCount(1, $wrestler->employments);
                $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            }

            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->assertCount(1, $tagTeam->employments);
                $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
            }
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_a_future_activated_stable_with_members_and_redirects($administrators)
    {
        $stable = Stable::factory()->withFutureActivation()->create();
        $startedAt = $stable->activations->last()->started_at;

        $this->assertTrue(now()->lt($startedAt));
        $this->assertEquals(StableStatus::FUTURE_ACTIVATION, $stable->status);

        $this->actAs($administrators)
            ->patch(route('stables.activate', $stable))
            ->assertRedirect(route('stables.index'));

        tap($stable->fresh(), function ($stable) use ($startedAt) {
            $this->assertTrue($stable->currentActivation->started_at->lt($startedAt));
            $this->assertEquals(StableStatus::ACTIVE, $stable->status);

            foreach ($stable->currentWrestlers as $wrestler) {
                $this->assertCount(1, $wrestler->employments);
                $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            }

            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->assertCount(1, $tagTeam->employments);
                $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
            }
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_an_inactive_stable_with_members_and_redirects($administrators)
    {
        $stable = Stable::factory()->inactive()->create();

        $this->actAs($administrators)
            ->patch(route('stables.activate', $stable))
            ->assertRedirect(route('stables.index'));

        tap($stable->fresh(), function ($stable) {
            $this->assertCount(2, $stable->activations);
            $this->assertEquals(StableStatus::ACTIVE, $stable->status);

            foreach ($stable->currentWrestlers as $wrestler) {
                $this->assertCount(2, $wrestler->employments);
                $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            }

            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->assertCount(2, $tagTeam->employments);
                $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ActivateController::class, '__invoke', ActivateRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_activate_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('stables.activate', $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_activate_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->patch(route('stables.activate', $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_activating_an_retired_stable($administrators)
    {
        $this->expectException(CannotBeActivatedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('stables.activate', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_activating_an_active_stable($administrators)
    {
        $this->expectException(CannotBeActivatedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->active()->create();

        $this->actAs($administrators)
            ->patch(route('stables.activate', $stable));
    }
}
