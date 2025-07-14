<?php

declare(strict_types=1);

use App\Http\Controllers\Stables\IndexController;
use App\Livewire\Stables\Tables\StablesTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Stables Index Controller.
 *
 * @see IndexController
 */
describe('Stables Index Controller', function () {
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