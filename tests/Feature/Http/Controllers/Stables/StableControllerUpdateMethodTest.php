<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Tests\Factories\StableRequestDataFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class StableControllerUpdateMethodTest extends TestCase
{
    /**
     * @test
     */
    public function edit_returns_a_view()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([StablesController::class, 'edit'], $stable))
            ->assertViewIs('stables.edit')
            ->assertViewHas('stable', $stable);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_editing_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::basic())
            ->get(action([StablesController::class, 'edit'], $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_editing_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->get(action([StablesController::class, 'edit'], $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function updates_a_stable_and_redirects()
    {
        $stable = Stable::factory()->withNoMembers()->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([StablesController::class, 'edit'], $stable))
            ->put(
                action([StablesController::class, 'update'], $stable),
                StableRequestDataFactory::new()->withStable($stable)->create(['name' => 'Example Stable Name'])
            )
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) {
            $this->assertEquals('Example Stable Name', $stable->name);
        });
    }

    /**
     * @test
     */
    public function wrestlers_of_stable_are_synced_when_stable_is_updated()
    {
        $stable = Stable::factory()->withFutureActivation()->withNoMembers()->create();
        $wrestlers = Wrestler::factory()->bookable()->count(3)->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([StablesController::class, 'edit'], $stable))
            ->put(
                action([StablesController::class, 'update'], $stable),
                StableRequestDataFactory::new()
                    ->withStable($stable)
                    ->withWrestlers($wrestlers->modelKeys())
                    ->create()
            )
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) use ($wrestlers) {
            $this->assertCount(3, $stable->currentWrestlers);
            $this->assertEquals($stable->currentWrestlers->modelKeys(), $wrestlers->modelKeys());
        });
    }

    /**
     * @test
     */
    public function tag_teams_of_stable_are_synced_when_stable_is_updated()
    {
        $stable = Stable::factory()->withFutureActivation()->withNoMembers()->create();
        $tagTeams = TagTeam::factory()->bookable()->count(2)->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([StablesController::class, 'edit'], $stable))
            ->put(
                action([StablesController::class, 'update'], $stable),
                StableRequestDataFactory::new()
                    ->withStable($stable)
                    ->withTagTeams($tagTeams->modelKeys())
                    ->create()
            )
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) use ($tagTeams) {
            $this->assertCount(2, $stable->currentTagTeams);
            $this->assertEquals($stable->currentTagTeams->modelKeys(), $tagTeams->modelKeys());
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_update_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::basic())
            ->from(action([StablesController::class, 'edit'], $stable))
            ->put(
                action([StablesController::class, 'update'], $stable),
                StableRequestDataFactory::new()->withStable($stable)->create()
            )
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_update_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->from(action([StablesController::class, 'edit'], $stable))
            ->put(
                action([StablesController::class, 'update'], $stable),
                StableRequestDataFactory::new()->withStable($stable)->create()
            )
            ->assertRedirect(route('login'));
    }
}
