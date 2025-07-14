<?php

declare(strict_types=1);

use App\Http\Controllers\Stables\IndexController;
use App\Http\Controllers\Stables\ShowController;
use App\Livewire\Stables\Tables\PreviousTagTeamsTable;
use App\Livewire\Stables\Tables\PreviousWrestlersTable;
use App\Livewire\Stables\Tables\StablesTable;
use App\Models\Stables\Stable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Stables Controllers.
 *
 * @see IndexController
 * @see ShowController
 */
describe('index', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('stables.index')
            ->assertSeeLivewire(StablesTable::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view stables index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view stables index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
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
            ->assertSeeLivewire(PreviousWrestlersTable::class)
            ->assertSeeLivewire(PreviousTagTeamsTable::class);
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
