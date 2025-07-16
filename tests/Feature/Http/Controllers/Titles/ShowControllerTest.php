<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\ShowController;
use App\Livewire\Titles\Tables\PreviousTitleChampionshipsTable;
use App\Models\Titles\Title;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Titles Show Controller.
 *
 * @see ShowController
 */
describe('Titles Show Controller', function () {
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
