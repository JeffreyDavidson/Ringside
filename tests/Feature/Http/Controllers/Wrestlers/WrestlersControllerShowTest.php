<?php

declare(strict_types=1);

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Wrestlers\Components\Actions;
use App\Livewire\Wrestlers\Tables\PreviousManagers;
use App\Livewire\Wrestlers\Tables\PreviousMatches;
use App\Livewire\Wrestlers\Tables\PreviousStables;
use App\Livewire\Wrestlers\Tables\PreviousTagTeams;
use App\Livewire\Wrestlers\Tables\PreviousTitleChampionships;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Wrestlers Controller.
 *
 * @see WrestlersController
 */
describe('Wrestlers Controller', function () {
    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->create();
    });

    /**
     * @see WrestlersController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(route('wrestlers.show', $this->wrestler))
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertSee($this->wrestler->name)
            ->assertSee('Status')
            ->assertViewHas('wrestler', $this->wrestler)
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousTitleChampionships::class)
            ->assertSeeLivewire(PreviousMatches::class)
            ->assertSeeLivewire(PreviousTagTeams::class)
            ->assertSeeLivewire(PreviousManagers::class)
            ->assertSeeLivewire(PreviousStables::class);
    });

    /**
     * @see WrestlersController::show()
     */
    test('show labels injured and suspended wrestlers', function (bool $injured, bool $suspended) {
        $wrestler = Wrestler::factory()->employed()->create();

        if ($injured) {
            Injury::factory()->for($wrestler, 'injurable')->create();
        }

        if ($suspended) {
            Suspension::factory()->for($wrestler, 'suspendable')->create();
        }

        $response = actingAs(administrator())
            ->get(route('wrestlers.show', $wrestler))
            ->assertOk()
            ->assertSee('Employed');

        expect($response->getContent())
            ->when($injured, fn ($html) => $html->toContain('data-test="availability-injured"'))
            ->unless($injured, fn ($html) => $html->not->toContain('data-test="availability-injured"'))
            ->when($suspended, fn ($html) => $html->toContain('data-test="availability-suspended"'))
            ->unless($suspended, fn ($html) => $html->not->toContain('data-test="availability-suspended"'));
    })->with([
        'injured' => [true, false],
        'suspended' => [false, true],
        'injured and suspended' => [true, true],
        'available' => [false, false],
    ]);

    /**
     * @see WrestlersController::show()
     */
    test('show renders the lifecycle actions component', function () {
        actingAs(administrator())
            ->get(route('wrestlers.show', $this->wrestler))
            ->assertOk()
            ->assertSeeLivewire(Actions::class);
    });

    /**
     * @see WrestlersController::show()
     */
    test('show renders the general info component', function () {
        actingAs(administrator())
            ->get(route('wrestlers.show', $this->wrestler))
            ->assertOk()
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($this->wrestler->status->label());
    });

    /**
     * @see WrestlersController::show()
     */
    test('show renders the related data displayed by the wrestler summary', function () {
        $wrestler = Wrestler::factory()->employed()->onCurrentTagTeam(TagTeam::factory()->create(['name' => 'Tag Team Alpha']))->create();
        $manager = Manager::factory()->create(['first_name' => 'Travis', 'last_name' => "O'Keefe"]);
        $wrestler->managers()->attach($manager, ['hired_at' => now()->subDay()]);
        TitleChampionship::factory()
            ->for(Title::factory()->create(['name' => 'Heavyweight Belt']), 'title')
            ->forWrestler($wrestler)
            ->current()
            ->create();

        actingAs(administrator())
            ->get(route('wrestlers.show', $wrestler))
            ->assertOk()
            ->assertSee('Tag Team Alpha')
            ->assertSee($manager->full_name)
            ->assertSee('Heavyweight Belt')
            ->assertDontSeeHtml('&amp;#039;')
            ->assertSee($wrestler->employments()->firstOrFail()->started_at->toDateString());
    });

    /**
     * @see WrestlersController::show()
     */
    test('show loads the summary relationships only once', function () {
        $wrestler = Wrestler::factory()->employed()->create();
        $wrestler->managers()->attach(Manager::factory()->create(), ['hired_at' => now()->subDay()]);
        actingAs(administrator());

        DB::flushQueryLog();
        DB::enableQueryLog();

        get(route('wrestlers.show', $wrestler))->assertOk();

        $managerQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'wrestlers_managers'));
        DB::disableQueryLog();

        expect($managerQueries)->toHaveCount(1);
    });

    /**
     * @see WrestlersController::show()
     */
    test('a basic user cannot view wrestler profiles', function () {
        actingAs(basicUser())
            ->get(route('wrestlers.show', $this->wrestler))
            ->assertForbidden();
    });

    /**
     * @see WrestlersController::show()
     */
    test('a guest cannot view a wrestler profile', function () {
        get(route('wrestlers.show', $this->wrestler))
            ->assertRedirect(route('login'));
    });

    /**
     * @see WrestlersController::show()
     */
    test('returns 404 when wrestler does not exist', function () {
        actingAs(administrator())
            ->get(route('wrestlers.show', 999999))
            ->assertNotFound();
    });
});
