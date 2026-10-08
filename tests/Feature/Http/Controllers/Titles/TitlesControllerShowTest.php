<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\TitlesController;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Titles\Components\Actions;
use App\Livewire\Titles\Tables\TitleHistory;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Titles Controller.
 *
 * @see TitlesController
 */
describe('Titles Controller', function () {
    /**
     * @see TitlesController::show()
     */
    test('show returns a view', function () {
        $title = Title::factory()->create();

        actingAs(administrator())
            ->get(route('titles.show', $title))
            ->assertOk()
            ->assertViewIs('titles.show')
            ->assertSee($title->name)
            ->assertSee('Current Champion')
            ->assertSee('Vacant')
            ->assertViewHas('title', $title)
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(TitleHistory::class);
    });

    /**
     * @see TitlesController::show()
     */
    test('show renders the lifecycle actions component', function () {
        $title = Title::factory()->create();

        actingAs(administrator())
            ->get(route('titles.show', $title))
            ->assertOk()
            ->assertSeeLivewire(Actions::class);
    });

    /**
     * @see TitlesController::show()
     */
    test('show renders the general info component', function () {
        $title = Title::factory()->create();

        actingAs(administrator())
            ->get(route('titles.show', $title))
            ->assertOk()
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($title->status->label());
    });

    /**
     * @see TitlesController::show()
     */
    test('show renders the related data displayed by the title summary', function () {
        $title = Title::factory()->create();

        $startedAt = today()->subDay();
        ActivityPeriod::factory()
            ->for($title, 'activeable')
            ->started($startedAt)
            ->create();
        $wrestler = Wrestler::factory()->create();
        TitleChampionship::factory()->for($title, 'title')->forWrestler($wrestler)->current()->create();

        actingAs(administrator())
            ->get(route('titles.show', $title))
            ->assertOk()
            ->assertSee($startedAt->toDateString())
            ->assertSee($wrestler->name);
    });

    /**
     * @see TitlesController::show()
     */
    test('a basic user cannot view a title', function () {
        $title = Title::factory()->create();

        actingAs(basicUser())
            ->get(route('titles.show', $title))
            ->assertForbidden();
    });

    /**
     * @see TitlesController::show()
     */
    test('a guest cannot view a title', function () {
        $title = Title::factory()->create();

        get(route('titles.show', $title))
            ->assertRedirect(route('login'));
    });

    /**
     * @see TitlesController::show()
     */
    test('returns 404 when title does not exist', function () {
        Title::factory()->create();

        actingAs(administrator())
            ->get(route('titles.show', 999999))
            ->assertNotFound();
    });
});
