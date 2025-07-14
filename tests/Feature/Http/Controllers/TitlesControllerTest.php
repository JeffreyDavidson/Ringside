<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\IndexController;
use App\Http\Controllers\Titles\ShowController;
use App\Livewire\Titles\Tables\PreviousTitleChampionshipsTable;
use App\Livewire\Titles\Tables\TitlesTable;
use App\Models\Titles\Title;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Titles Controllers.
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

describe('show', function () {
    beforeEach(function () {
        $this->title = Title::factory()->create();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, $this->title))
            ->assertOk()
            ->assertViewIs('titles.show')
            ->assertViewHas('title', $this->title)
            ->assertSeeLivewire(PreviousTitleChampionshipsTable::class);
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a basic user cannot view a title', function () {
        actingAs(basicUser())
            ->get(action(ShowController::class, $this->title))
            ->assertForbidden();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a guest cannot view a title', function () {
        get(action(ShowController::class, $this->title))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ShowController::__invoke()
     */
    test('returns 404 when title does not exist', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, 999999))
            ->assertNotFound();
    });
});
