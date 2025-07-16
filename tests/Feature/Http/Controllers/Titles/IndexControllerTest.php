<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\IndexController;
use App\Livewire\Titles\Tables\TitlesTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Titles Index Controller.
 *
 * @see IndexController
 */
describe('Titles Index Controller', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('titles.index')
            ->assertSeeLivewire(TitlesTable::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view titles index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view titles index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});
