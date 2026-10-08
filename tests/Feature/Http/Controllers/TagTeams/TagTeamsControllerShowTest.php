<?php

declare(strict_types=1);

use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\TagTeams\Components\Actions;
use App\Livewire\TagTeams\Tables\PreviousManagers;
use App\Livewire\TagTeams\Tables\PreviousMatches;
use App\Livewire\TagTeams\Tables\PreviousStables;
use App\Livewire\TagTeams\Tables\PreviousTitleChampionships;
use App\Livewire\TagTeams\Tables\PreviousWrestlers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for TagTeams Controller.
 *
 * @see TagTeamsController
 */
describe('TagTeams Controller', function () {
    /**
     * @see TagTeamsController::show()
     */
    test('show returns a view', function () {
        $tagTeam = TagTeam::factory()->create();

        actingAs(administrator())
            ->get(route('tag-teams.show', $tagTeam))
            ->assertOk()
            ->assertViewIs('tag-teams.show')
            ->assertSee($tagTeam->name)
            ->assertSee('Status')
            ->assertViewHas('tagTeam', $tagTeam)
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousTitleChampionships::class)
            ->assertSeeLivewire(PreviousMatches::class)
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousManagers::class)
            ->assertSeeLivewire(PreviousStables::class);
    });

    /**
     * @see TagTeamsController::show()
     */
    test('show renders the lifecycle actions component', function () {
        $tagTeam = TagTeam::factory()->create();

        actingAs(administrator())
            ->get(route('tag-teams.show', $tagTeam))
            ->assertOk()
            ->assertSeeLivewire(Actions::class);
    });

    /**
     * @see TagTeamsController::show()
     */
    test('show renders the general info component', function () {
        $tagTeam = TagTeam::factory()->create();

        actingAs(administrator())
            ->get(route('tag-teams.show', $tagTeam))
            ->assertOk()
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($tagTeam->status->label());
    });

    /**
     * @see TagTeamsController::show()
     */
    test('show renders the related data displayed by the tag team summary', function () {
        TagTeam::factory()->create();

        $tagTeam = TagTeam::factory()->employed()->create();
        $tagTeam->currentWrestlers->firstOrFail()->update(['name' => "O'Neil & Sons"]);
        $tagTeam->currentWrestlers->skip(1)->firstOrFail()->update(['name' => "D'Angelo & Sons"]);
        $manager = Manager::factory()->create(['first_name' => 'Travis', 'last_name' => "O'Keefe"]);
        $tagTeam->managers()->attach($manager, ['hired_at' => now()->subDay()]);
        TitleChampionship::factory()
            ->for(Title::factory()->create(['name' => 'Tag Team Belt']), 'title')
            ->forTagTeam($tagTeam)
            ->current()
            ->create();

        $response = actingAs(administrator())
            ->get(route('tag-teams.show', $tagTeam))
            ->assertOk()
            ->assertSee($manager->full_name)
            ->assertSee('Tag Team Belt');

        $tagTeam->currentWrestlers->each(fn (Wrestler $wrestler) => $response->assertSee($wrestler->name));
        $response->assertDontSeeHtml('&amp;#039;');
    });

    /**
     * @see TagTeamsController::show()
     */
    test('a basic user cannot view tag team profiles', function () {
        $tagTeam = TagTeam::factory()->create();

        actingAs(basicUser())
            ->get(route('tag-teams.show', $tagTeam))
            ->assertForbidden();
    });

    /**
     * @see TagTeamsController::show()
     */
    test('a guest cannot view a tag team profile', function () {
        TagTeam::factory()->create();

        $tagTeam = TagTeam::factory()->create();

        get(route('tag-teams.show', $tagTeam))
            ->assertRedirect(route('login'));
    });

    /**
     * @see TagTeamsController::show()
     */
    test('returns 404 when tag team does not exist', function () {
        TagTeam::factory()->create();

        actingAs(administrator())
            ->get(route('tag-teams.show', 999999))
            ->assertNotFound();
    });
});
