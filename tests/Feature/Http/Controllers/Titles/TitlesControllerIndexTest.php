<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\TitlesController;
use App\Livewire\Titles\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Titles Controller.
 *
 * @see TitlesController
 */
describe('Titles Controller', function () {
    /**
     * @see TitlesController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(route('titles.index'))
            ->assertOk()
            ->assertViewIs('titles.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see TitlesController::index()
     */
    test('a basic user cannot view titles index page', function () {
        actingAs(basicUser())
            ->get(route('titles.index'))
            ->assertForbidden();
    });

    /**
     * @see TitlesController::index()
     */
    test('a guest cannot view titles index page', function () {
        get(route('titles.index'))
            ->assertRedirect(route('login'));
    });
});
