<?php

declare(strict_types=1);

use App\Http\Controllers\WrestlersController;
use App\Livewire\Wrestlers\Tables\PreviousManagersTable;
use App\Livewire\Wrestlers\Tables\PreviousMatchesTable;
use App\Livewire\Wrestlers\Tables\PreviousStablesTable;
use App\Livewire\Wrestlers\Tables\PreviousTagTeamsTable;
use App\Livewire\Wrestlers\Tables\PreviousTitleChampionshipsTable;
use App\Livewire\Wrestlers\Tables\WrestlersTable;
use App\Models\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for WrestlersController.
 *
 * @see WrestlersController
 */
describe('index', function () {
    /**
     * @see WrestlersController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([WrestlersController::class, 'index']))
            ->assertOk()
            ->assertViewIs('wrestlers.index')
            ->assertSeeLivewire(WrestlersTable::class);
    });

    /**
     * @see WrestlersController::index()
     */
    test('a basic user cannot view wrestlers index page', function () {
        actingAs(basicUser())
            ->get(action([WrestlersController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see WrestlersController::index()
     */
    test('a guest cannot view wrestlers index page', function () {
        get(action([WrestlersController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->create();
    });

    /**
     * @see WrestlersController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([WrestlersController::class, 'show'], $this->wrestler))
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $this->wrestler)
            ->assertSeeLivewire(PreviousTitleChampionshipsTable::class)
            ->assertSeeLivewire(PreviousMatchesTable::class)
            ->assertSeeLivewire(PreviousTagTeamsTable::class)
            ->assertSeeLivewire(PreviousManagersTable::class)
            ->assertSeeLivewire(PreviousStablesTable::class);
    });

    /**
     * @see WrestlersController::show()
     */
    test('a basic user cannot view wrestler profiles', function () {
        actingAs(basicUser())
            ->get(action([WrestlersController::class, 'show'], $this->wrestler))
            ->assertForbidden();
    });

    /**
     * @see WrestlersController::show()
     */
    test('a guest cannot view a wrestler profile', function () {
        get(action([WrestlersController::class, 'show'], $this->wrestler))
            ->assertRedirect(route('login'));
    });

    /**
     * @see WrestlersController::show()
     */
    test('returns 404 when wrestler does not exist', function () {
        actingAs(administrator())
            ->get(action([WrestlersController::class, 'show'], 999999))
            ->assertNotFound();
    });
});
