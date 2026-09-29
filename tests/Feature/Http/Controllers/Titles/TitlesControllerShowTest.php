<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\TitlesController;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Titles\Components\Actions;
use App\Livewire\Titles\Tables\PreviousTitleChampionships;
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
    beforeEach(function () {
        $this->title = Title::factory()->create();
    });

    /**
     * @see TitlesController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(route('titles.show', $this->title))
            ->assertOk()
            ->assertViewIs('titles.show')
            ->assertSee($this->title->name)
            ->assertSee('Current Champion')
            ->assertSee('Vacant')
            ->assertViewHas('title', $this->title)
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousTitleChampionships::class);
    });

    /**
     * @see TitlesController::show()
     */
    test('show renders the lifecycle actions component', function () {
        actingAs(administrator())
            ->get(route('titles.show', $this->title))
            ->assertOk()
            ->assertSeeLivewire(Actions::class);
    });

    /**
     * @see TitlesController::show()
     */
    test('show renders the general info component', function () {
        actingAs(administrator())
            ->get(route('titles.show', $this->title))
            ->assertOk()
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($this->title->status->label());
    });

    /**
     * @see TitlesController::show()
     */
    test('show renders the related data displayed by the title summary', function () {
        $startedAt = today()->subDay();
        ActivityPeriod::factory()
            ->for($this->title, 'activeable')
            ->started($startedAt)
            ->create();
        $wrestler = Wrestler::factory()->create();
        TitleChampionship::factory()->for($this->title, 'title')->forWrestler($wrestler)->current()->create();

        actingAs(administrator())
            ->get(route('titles.show', $this->title))
            ->assertOk()
            ->assertSee($startedAt->toDateString())
            ->assertSee($wrestler->name);
    });

    /**
     * @see TitlesController::show()
     */
    test('a basic user cannot view a title', function () {
        actingAs(basicUser())
            ->get(route('titles.show', $this->title))
            ->assertForbidden();
    });

    /**
     * @see TitlesController::show()
     */
    test('a guest cannot view a title', function () {
        get(route('titles.show', $this->title))
            ->assertRedirect(route('login'));
    });

    /**
     * @see TitlesController::show()
     */
    test('returns 404 when title does not exist', function () {
        actingAs(administrator())
            ->get(route('titles.show', 999999))
            ->assertNotFound();
    });
});
