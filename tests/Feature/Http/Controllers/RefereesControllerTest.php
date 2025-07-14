<?php

declare(strict_types=1);

use App\Http\Controllers\Referees\IndexController;
use App\Http\Controllers\Referees\ShowController;
use App\Livewire\Referees\Tables\PreviousMatchesTable;
use App\Livewire\Referees\Tables\RefereesTable;
use App\Models\Referees\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Referees Controllers.
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
            ->assertViewIs('referees.index')
            ->assertSeeLivewire(RefereesTable::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view referees index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view referees index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->referee = Referee::factory()->create();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, $this->referee))
            ->assertViewIs('referees.show')
            ->assertViewHas('referee', $this->referee)
            ->assertSeeLivewire(PreviousMatchesTable::class);
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a basic user cannot view a referee profile', function () {
        actingAs(basicUser())
            ->get(action(ShowController::class, $this->referee))
            ->assertForbidden();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a guest cannot view a referee profile', function () {
        get(action(ShowController::class, $this->referee))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ShowController::__invoke()
     */
    test('returns 404 when referee does not exist', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, 999999))
            ->assertNotFound();
    });
});
