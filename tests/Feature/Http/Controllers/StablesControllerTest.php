<?php

declare(strict_types=1);

use App\Http\Controllers\StablesController;
use App\Livewire\Stables\Tables\PreviousTagTeamsTable;
use App\Livewire\Stables\Tables\PreviousWrestlersTable;
use App\Livewire\Stables\Tables\StablesTable;
use App\Models\Stables\Stable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for StablesController.
 *
 * @see StablesController
 */
describe('index', function () {
    /**
     * @see StablesController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([StablesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('stables.index')
            ->assertSeeLivewire(StablesTable::class);
    });

    /**
     * @see StablesController::index()
     */
    test('a basic user cannot view stables index page', function () {
        actingAs(basicUser())
            ->get(action([StablesController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see StablesController::index()
     */
    test('a guest cannot view stables index page', function () {
        get(action([StablesController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
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
            ->assertSeeLivewire(PreviousWrestlersTable::class)
            ->assertSeeLivewire(PreviousTagTeamsTable::class);
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
});
