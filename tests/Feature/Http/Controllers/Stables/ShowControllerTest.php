<?php

declare(strict_types=1);

use App\Http\Controllers\Stables\ShowController;
use App\Livewire\Stables\Tables\PreviousTagTeams;
use App\Livewire\Stables\Tables\PreviousWrestlers;
use App\Models\Stables\Stable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Stables Show Controller.
 *
 * @see ShowController
 */
describe('Stables Show Controller', function () {
    beforeEach(function () {
        $this->stable = Stable::factory()->create();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('show returns a view', function () {
        $response = actingAs(administrator())
            ->get(action(ShowController::class, $this->stable));

        $response->assertOk();
        $response->assertViewIs('stables.show')
            ->assertViewHas('stable', $this->stable)
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousTagTeams::class);
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a basic user cannot view stable profiles', function () {
        actingAs(basicUser())
            ->get(action(ShowController::class, $this->stable))
            ->assertForbidden();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a guest cannot view a stable profile', function () {
        get(action(ShowController::class, $this->stable))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ShowController::__invoke()
     */
    test('returns 404 when stable does not exist', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, 999999))
            ->assertNotFound();
    });
});
