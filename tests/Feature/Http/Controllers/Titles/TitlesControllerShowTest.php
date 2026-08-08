<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\TitlesController;
use App\Livewire\Titles\Tables\PreviousTitleChampionships;
use App\Models\Titles\Title;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Titles Controller.
 *
 * @see TitlesController
 */
describe('Titles Controller', function () {
    beforeEach(function () {
        $this->title = Title::factory()->create();
    });

    /**
     * @see TitlesController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([TitlesController::class, 'show'], $this->title))
            ->assertOk()
            ->assertViewIs('titles.show')
            ->assertViewHas('title', $this->title)
            ->assertSeeLivewire(PreviousTitleChampionships::class);
    });

    /**
     * @see TitlesController::show()
     */
    test('a basic user cannot view a title', function () {
        actingAs(basicUser())
            ->get(action([TitlesController::class, 'show'], $this->title))
            ->assertForbidden();
    });

    /**
     * @see TitlesController::show()
     */
    test('a guest cannot view a title', function () {
        get(action([TitlesController::class, 'show'], $this->title))
            ->assertRedirect(route('login'));
    });

    /**
     * @see TitlesController::show()
     */
    test('returns 404 when title does not exist', function () {
        actingAs(administrator())
            ->get(action([TitlesController::class, 'show'], 999999))
            ->assertNotFound();
    });
});
