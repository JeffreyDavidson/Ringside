<?php

declare(strict_types=1);

use App\Http\Controllers\Stables\StablesController;
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
    beforeEach(function () {
        $this->stable = Stable::factory()->create();
    });

    /**
     * @see StablesController::show()
     */
    test('show returns a view', function () {
        $response = actingAs(administrator())
            ->get(action([StablesController::class, 'show'], $this->stable));

        $response->assertOk();
        $response->assertViewIs('stables.show')
            ->assertViewHas('stable', $this->stable)
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousTagTeams::class)
            ->assertSeeLivewire(PreviousManagers::class);
    });

    /**
     * @see StablesController::show()
     */
    test('show renders the stable summary from only its required relationships', function () {
        $startedAt = today()->subDay();
        ActivityPeriod::factory()
            ->for($this->stable, 'activeable')
            ->started($startedAt)
            ->create();

        actingAs(administrator())
            ->get(action([StablesController::class, 'show'], $this->stable))
            ->assertOk()
            ->assertSee($startedAt->toDateString())
            ->assertViewHas('stable', fn (Stable $stable): bool => count($stable->getRelations()) === 3
                && $stable->relationLoaded('currentTagTeams')
                && $stable->relationLoaded('currentWrestlers')
                && $stable->relationLoaded('firstActivityPeriod'));
    });

    /**
     * @see StablesController::show()
     */
    test('a basic user cannot view stable profiles', function () {
        actingAs(basicUser())
            ->get(action([StablesController::class, 'show'], $this->stable))
            ->assertForbidden();
    });

    /**
     * @see StablesController::show()
     */
    test('a guest cannot view a stable profile', function () {
        get(action([StablesController::class, 'show'], $this->stable))
            ->assertRedirect(route('login'));
    });

    /**
     * @see StablesController::show()
     */
    test('returns 404 when stable does not exist', function () {
        actingAs(administrator())
            ->get(action([StablesController::class, 'show'], 999999))
            ->assertNotFound();
    });

    /**
     * @see StablesController::show()
     */
    test('administrators can view stable profiles in every lifecycle state', function () {
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
            $this->get(action([StablesController::class, 'show'], $stable))
                ->assertOk();
        }
    });
});
