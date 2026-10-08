<?php

declare(strict_types=1);

use App\Livewire\Events\Tables\Main as EventsMain;
use App\Livewire\Managers\Tables\Main as ManagersMain;
use App\Livewire\Referees\Tables\Main as RefereesMain;
use App\Livewire\Stables\Tables\Main as StablesMain;
use App\Livewire\TagTeams\Tables\Main as TagTeamsMain;
use App\Livewire\Titles\Tables\Main as TitlesMain;
use App\Livewire\Users\Tables\Main as UsersMain;
use App\Livewire\Venues\Tables\Main as VenuesMain;
use App\Livewire\Wrestlers\Tables\Main as WrestlersMain;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;

/*
 * The index ("Main") tables of the roster, events and users pages share one
 * contract: they are administrator-only, search by display name, clear the
 * search and re-render changed records on refresh. This catalogue holds what
 * differs per table; the datasets below slice it for each test.
 *
 * - attributes: turns a display name into the attributes that store it (people store a first and last name)
 * - state: factory state the record needs to appear in the table
 * - search: [search term, name the term matches, name it must hide]
 * - noun: label used for the record in the refresh test
 */

/**
 * @return array<string, array{
 *     component: class-string,
 *     model: class-string,
 *     attributes: Closure,
 *     state: ?string,
 *     search: array{string, string, string},
 *     noun: string,
 * }>
 */
function livewireMainTableCatalog(): array
{
    $name = static fn (string $name): array => ['name' => $name];
    $personName = static fn (string $name): array => [
        'first_name' => strtok($name, ' '),
        'last_name' => trim((string) strstr($name, ' ')),
    ];

    return [
        'events' => ['component' => EventsMain::class, 'model' => Event::class, 'attributes' => $name, 'state' => 'unscheduled', 'search' => ['Summer', 'Summer Spectacular', 'Winter Warfare'], 'noun' => 'Event'],
        'managers' => ['component' => ManagersMain::class, 'model' => Manager::class, 'attributes' => $personName, 'state' => null, 'search' => ['Paul', 'Paul Bearer', 'Jimmy Hart'], 'noun' => 'Manager'],
        'referees' => ['component' => RefereesMain::class, 'model' => Referee::class, 'attributes' => $personName, 'state' => null, 'search' => ['Earl', 'Earl Hebner', 'Mike Chioda'], 'noun' => 'Referee'],
        'stables' => ['component' => StablesMain::class, 'model' => Stable::class, 'attributes' => $name, 'state' => 'active', 'search' => ['Horsemen', 'The Four Horsemen', 'New World Order'], 'noun' => 'Stable'],
        'tag teams' => ['component' => TagTeamsMain::class, 'model' => TagTeam::class, 'attributes' => $name, 'state' => null, 'search' => ['Hardy', 'The Hardy Boyz', 'The Dudley Boyz'], 'noun' => 'Tag Team'],
        'titles' => ['component' => TitlesMain::class, 'model' => Title::class, 'attributes' => $name, 'state' => null, 'search' => ['World', 'World Heavyweight Title', 'Intercontinental Title'], 'noun' => 'Title'],
        'users' => ['component' => UsersMain::class, 'model' => User::class, 'attributes' => $personName, 'state' => null, 'search' => ['Xylo', 'Xylo Quartzenberg', 'Zephyra Vandermolen'], 'noun' => 'Name'],
        'venues' => ['component' => VenuesMain::class, 'model' => Venue::class, 'attributes' => $name, 'state' => null, 'search' => ['Alpha', 'Alpha Arena', 'Bravo Arena'], 'noun' => 'Venue'],
        'wrestlers' => ['component' => WrestlersMain::class, 'model' => Wrestler::class, 'attributes' => $name, 'state' => null, 'search' => ['John', 'John Cena', 'The Rock'], 'noun' => 'Wrestler'],
    ];
}

dataset('livewire main tables', function (): Generator {
    foreach (livewireMainTableCatalog() as $table => $spec) {
        yield $table => [$spec['component']];
    }
});

dataset('livewire main table searches', function (): Generator {
    foreach (livewireMainTableCatalog() as $table => $spec) {
        yield $table => [
            'component' => $spec['component'],
            'model' => $spec['model'],
            'attributes' => $spec['attributes'],
            'state' => $spec['state'],
            'term' => $spec['search'][0],
            'visible' => $spec['search'][1],
            'hidden' => $spec['search'][2],
        ];
    }
});

dataset('livewire main table refreshes', function (): Generator {
    foreach (livewireMainTableCatalog() as $table => $spec) {
        yield $table => [
            'component' => $spec['component'],
            'model' => $spec['model'],
            'attributes' => $spec['attributes'],
            'state' => $spec['state'],
            'noun' => $spec['noun'],
        ];
    }
});

/*
 * Tables whose records have an employment status: wrestlers, managers and
 * referees (individuals) and tag teams.
 */
dataset('livewire employment main tables', function (): Generator {
    foreach (['managers', 'referees', 'tag teams', 'wrestlers'] as $table) {
        $spec = livewireMainTableCatalog()[$table];

        yield $table => [
            'component' => $spec['component'],
            'model' => $spec['model'],
            'attributes' => $spec['attributes'],
            'noun' => $spec['noun'],
        ];
    }
});

/*
 * Tables of individuals (wrestlers, managers, referees): they add injury and
 * suspension availability and an employment date range filter.
 */
dataset('livewire individual main tables', function (): Generator {
    foreach (['managers', 'referees', 'wrestlers'] as $table) {
        $spec = livewireMainTableCatalog()[$table];

        yield $table => [
            'component' => $spec['component'],
            'model' => $spec['model'],
            'attributes' => $spec['attributes'],
        ];
    }
});
