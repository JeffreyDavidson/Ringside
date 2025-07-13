<?php

declare(strict_types=1);

use App\Http\Controllers\RefereesController;
use App\Livewire\Referees\Tables\PreviousMatchesTable;
use App\Livewire\Referees\Tables\RefereesTable;
use App\Models\Referees\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for RefereesController.
 *
 * @see RefereesController
 */
describe('index', function () {
    /**
     * @see RefereesController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([RefereesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('referees.index')
            ->assertSeeLivewire(RefereesTable::class);
    });

    /**
     * @see RefereesController::index()
     */
    test('a basic user cannot view referees index page', function () {
        actingAs(basicUser())
            ->get(action([RefereesController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see RefereesController::index()
     */
    test('a guest cannot view referees index page', function () {
        get(action([RefereesController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->referee = Referee::factory()->create();
    });

    /**
     * @see RefereesController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([RefereesController::class, 'show'], $this->referee))
            ->assertViewIs('referees.show')
            ->assertViewHas('referee', $this->referee)
            ->assertSeeLivewire(PreviousMatchesTable::class);
    });

    /**
     * @see RefereesController::show()
     */
    test('a basic user cannot view a referee profile', function () {
        actingAs(basicUser())
            ->get(action([RefereesController::class, 'show'], $this->referee))
            ->assertForbidden();
    });

    /**
     * @see RefereesController::show()
     */
    test('a guest cannot view a referee profile', function () {
        get(action([RefereesController::class, 'show'], $this->referee))
            ->assertRedirect(route('login'));
    });

    /**
     * @see RefereesController::show()
     */
    test('returns 404 when referee does not exist', function () {
        actingAs(administrator())
            ->get(action([RefereesController::class, 'show'], 999999))
            ->assertNotFound();
    });
});
