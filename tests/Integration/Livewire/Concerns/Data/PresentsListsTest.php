<?php

declare(strict_types=1);

use App\Livewire\Events\Modals\FormModal as EventFormModal;
use App\Livewire\Matches\Modals\FormModal as MatchFormModal;
use App\Livewire\Stables\Modals\FormModal as StableFormModal;
use App\Livewire\TagTeams\Modals\FormModal as TagTeamFormModal;
use App\Models\Events\Venue;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

describe('Livewire list presenters', function () {
    test('presents managers keyed by id', function () {
        $manager = Manager::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);
        $manager->refresh();

        expect(app(TagTeamFormModal::class)->getManagers())
            ->toBe([$manager->id => $manager->full_name]);
    });

    test('presents referees keyed by id', function () {
        $referee = Referee::factory()->create([
            'first_name' => 'Earl',
            'last_name' => 'Hebner',
        ]);
        $referee->refresh();

        expect(app(MatchFormModal::class)->getReferees())
            ->toBe([$referee->id => $referee->full_name]);
    });

    test('presents tag teams keyed by id', function () {
        $tagTeam = TagTeam::factory()->create(['name' => 'The Example Team']);

        expect(app(StableFormModal::class)->getTagTeams())
            ->toBe([$tagTeam->id => $tagTeam->name]);
    });

    test('presents titles keyed by id', function () {
        $title = Title::factory()->create(['name' => 'World Title']);

        expect(app(MatchFormModal::class)->getTitles())
            ->toBe([$title->id => $title->name]);
    });

    test('presents venues in alphabetical order keyed by id', function () {
        $zeta = Venue::factory()->create(['name' => 'Zeta Arena']);
        $alpha = Venue::factory()->create(['name' => 'Alpha Arena']);

        expect(app(EventFormModal::class)->getVenues())
            ->toBe([$alpha->id => $alpha->name, $zeta->id => $zeta->name]);
    });

    test('presents wrestlers keyed by id', function () {
        $wrestler = Wrestler::factory()->create(['name' => 'Example Wrestler']);

        expect(app(TagTeamFormModal::class)->getWrestlers())
            ->toBe([$wrestler->id => $wrestler->name]);
    });
});
