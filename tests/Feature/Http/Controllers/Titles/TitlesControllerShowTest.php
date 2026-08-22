<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\TitlesController;
use App\Livewire\Titles\Tables\PreviousTitleChampionships;
use App\Models\Lifecycle\ActivityPeriod;
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
    test('show renders the title summary from only its required relationship', function () {
        $startedAt = today()->subDay();
        ActivityPeriod::factory()
            ->for($this->title, 'activeable')
            ->started($startedAt)
            ->create();

        actingAs(administrator())
            ->get(action([TitlesController::class, 'show'], $this->title))
            ->assertOk()
            ->assertSee($startedAt->toDateString())
            ->assertViewHas('title', fn (Title $title): bool => count($title->getRelations()) === 1
                && $title->relationLoaded('firstActivityPeriod'));
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
