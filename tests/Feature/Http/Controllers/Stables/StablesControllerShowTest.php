<?php

declare(strict_types=1);

use App\Http\Controllers\Stables\StablesController;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Stables\Components\Actions;
use App\Livewire\Stables\Tables\PreviousManagers;
use App\Livewire\Stables\Tables\PreviousTagTeams;
use App\Livewire\Stables\Tables\PreviousWrestlers;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Stables Controller.
 *
 * @see StablesController
 */
describe('Stables Controller', function () {
    /**
     * @see StablesController::show()
     */
    test('show returns a view', function () {
        $stable = Stable::factory()->create();

        $response = actingAs(administrator())
            ->get(route('stables.show', $stable));

        $response->assertOk();
        $response->assertViewIs('stables.show')
            ->assertSee($stable->name)
            ->assertSee('Status')
            ->assertViewHas('stable', $stable)
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousTagTeams::class)
            ->assertSeeLivewire(PreviousManagers::class);
    });

    /**
     * @see StablesController::show()
     */
    test('show leaves the summary relationships to the general info component', function () {
        $stable = Stable::factory()->create();

        $startedAt = today()->subDay();
        ActivityPeriod::factory()
            ->for($stable, 'activeable')
            ->started($startedAt)
            ->create();

        actingAs(administrator())
            ->get(route('stables.show', $stable))
            ->assertOk()
            ->assertSee($startedAt->toDateString())
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertViewHas('stable', fn (Stable $stable): bool => $stable->getRelations() === []);
    });

    /**
     * @see StablesController::show()
     */
    test('show renders the lifecycle actions component', function () {
        $stable = Stable::factory()->create();

        actingAs(administrator())
            ->get(route('stables.show', $stable))
            ->assertOk()
            ->assertSeeLivewire(Actions::class);
    });

    /**
     * @see StablesController::show()
     */
    test('a basic user cannot view stable profiles', function () {
        $stable = Stable::factory()->create();

        actingAs(basicUser())
            ->get(route('stables.show', $stable))
            ->assertForbidden();
    });

    /**
     * @see StablesController::show()
     */
    test('a guest cannot view a stable profile', function () {
        $stable = Stable::factory()->create();

        get(route('stables.show', $stable))
            ->assertRedirect(route('login'));
    });

    /**
     * @see StablesController::show()
     */
    test('returns 404 when stable does not exist', function () {
        Stable::factory()->create();

        actingAs(administrator())
            ->get(route('stables.show', 999999))
            ->assertNotFound();
    });

    /**
     * @see StablesController::show()
     */
    test('administrators can view stable profiles in every lifecycle state', function () {
        Stable::factory()->create();

        // Arrange
        $stables = [
            Stable::factory()->active()->create(),
            Stable::factory()->inactive()->create(),
            Stable::factory()->retired()->create(),
        ];

        // Act
        actingAs(administrator());

        // Assert
        foreach ($stables as $stable) {
            get(route('stables.show', $stable))
                ->assertOk();
        }
    });
});
