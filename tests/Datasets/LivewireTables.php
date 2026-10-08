<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousStables as ManagerPreviousStables;
use App\Livewire\Managers\Tables\PreviousTagTeams as ManagerPreviousTagTeams;
use App\Livewire\Managers\Tables\PreviousWrestlers as ManagerPreviousWrestlers;
use App\Livewire\Referees\Tables\PreviousMatches as RefereePreviousMatches;
use App\Livewire\Stables\Tables\PreviousManagers as StablePreviousManagers;
use App\Livewire\Stables\Tables\PreviousTagTeams as StablePreviousTagTeams;
use App\Livewire\Stables\Tables\PreviousWrestlers as StablePreviousWrestlers;
use App\Livewire\TagTeams\Tables\PreviousManagers as TagTeamPreviousManagers;
use App\Livewire\TagTeams\Tables\PreviousMatches as TagTeamPreviousMatches;
use App\Livewire\TagTeams\Tables\PreviousStables as TagTeamPreviousStables;
use App\Livewire\TagTeams\Tables\PreviousTitleChampionships as TagTeamPreviousTitleChampionships;
use App\Livewire\TagTeams\Tables\PreviousWrestlers as TagTeamPreviousWrestlers;
use App\Livewire\Titles\Tables\TitleHistory;
use App\Livewire\Venues\Tables\PreviousEvents;
use App\Livewire\Wrestlers\Tables\PreviousManagers as WrestlerPreviousManagers;
use App\Livewire\Wrestlers\Tables\PreviousMatches as WrestlerPreviousMatches;
use App\Livewire\Wrestlers\Tables\PreviousStables as WrestlerPreviousStables;
use App\Livewire\Wrestlers\Tables\PreviousTagTeams as WrestlerPreviousTagTeams;
use App\Livewire\Wrestlers\Tables\PreviousTitleChampionships as WrestlerPreviousTitleChampionships;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use App\Models\Titles\Title;
use Carbon\CarbonInterface;
use Database\Factories\Matches\MatchFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds one case of the history table datasets.
 *
 * @param  class-string  $table  Livewire history table component
 * @param  string  $parameter  Locked id property that scopes the table to its parent record
 * @param  Closure(): Model  $parent  Creates the parent record the table lists history for
 * @param  string  $noun  Plural noun the table lists, used for the empty state and search placeholder
 * @param  int  $basicUserStatus  Status returned to a signed-in user without access to the parent
 * @return array{class-string, string, Closure(): Model, int, string, string, string}
 */
function livewireHistoryTable(
    string $table,
    string $parameter,
    Closure $parent,
    string $noun,
    ?string $heading = null,
    ?string $emptyMessage = null,
    int $basicUserStatus = 404,
): array {
    return [
        $table,
        $parameter,
        $parent,
        $basicUserStatus,
        $heading ?? "Previous {$noun}",
        $emptyMessage ?? "No previous {$noun} yet.",
        "Search {$noun}",
    ];
}

/*
 * Every detail-page history table. Each one lists records that belong to a
 * parent (manager, referee, stable, tag team, title, venue or wrestler), is
 * locked to that parent by an id property and shares the same empty state and
 * authorization behaviour.
 */
dataset('livewire history tables', [
    'manager stables' => livewireHistoryTable(ManagerPreviousStables::class, 'managerId', static fn (): Model => Manager::factory()->create(), 'stables'),
    'manager tag teams' => livewireHistoryTable(ManagerPreviousTagTeams::class, 'managerId', static fn (): Model => Manager::factory()->create(), 'tag teams'),
    'manager wrestlers' => livewireHistoryTable(ManagerPreviousWrestlers::class, 'managerId', static fn (): Model => Manager::factory()->create(), 'wrestlers'),
    'referee matches' => livewireHistoryTable(RefereePreviousMatches::class, 'refereeId', static fn (): Model => Referee::factory()->create(), 'matches'),
    'stable managers' => livewireHistoryTable(StablePreviousManagers::class, 'stableId', static fn (): Model => Stable::factory()->create(), 'managers'),
    'stable tag teams' => livewireHistoryTable(StablePreviousTagTeams::class, 'stableId', static fn (): Model => Stable::factory()->create(), 'tag teams'),
    'stable wrestlers' => livewireHistoryTable(StablePreviousWrestlers::class, 'stableId', static fn (): Model => Stable::factory()->create(), 'wrestlers'),
    'tag team managers' => livewireHistoryTable(TagTeamPreviousManagers::class, 'tagTeamId', static fn (): Model => TagTeam::factory()->create(), 'managers'),
    'tag team matches' => livewireHistoryTable(TagTeamPreviousMatches::class, 'tagTeamId', static fn (): Model => TagTeam::factory()->create(), 'matches'),
    'tag team stables' => livewireHistoryTable(TagTeamPreviousStables::class, 'tagTeamId', static fn (): Model => TagTeam::factory()->create(), 'stables'),
    'tag team title championships' => livewireHistoryTable(TagTeamPreviousTitleChampionships::class, 'tagTeamId', static fn (): Model => TagTeam::factory()->create(), 'title championships'),
    'tag team wrestlers' => livewireHistoryTable(TagTeamPreviousWrestlers::class, 'tagTeamId', static fn (): Model => TagTeam::factory()->create(), 'wrestlers'),
    'title reigns' => livewireHistoryTable(TitleHistory::class, 'titleId', static fn (): Model => Title::factory()->create(), 'title reigns', 'Title reigns', 'No title reigns yet.'),
    'venue events' => livewireHistoryTable(PreviousEvents::class, 'venueId', static fn (): Model => Venue::factory()->create(), 'events', basicUserStatus: 403),
    'wrestler managers' => livewireHistoryTable(WrestlerPreviousManagers::class, 'wrestlerId', static fn (): Model => Wrestler::factory()->create(), 'managers'),
    'wrestler matches' => livewireHistoryTable(WrestlerPreviousMatches::class, 'wrestlerId', static fn (): Model => Wrestler::factory()->create(), 'matches'),
    'wrestler stables' => livewireHistoryTable(WrestlerPreviousStables::class, 'wrestlerId', static fn (): Model => Wrestler::factory()->create(), 'stables'),
    'wrestler tag teams' => livewireHistoryTable(WrestlerPreviousTagTeams::class, 'wrestlerId', static fn (): Model => Wrestler::factory()->create(), 'tag teams'),
    'wrestler title championships' => livewireHistoryTable(WrestlerPreviousTitleChampionships::class, 'wrestlerId', static fn (): Model => Wrestler::factory()->create(), 'title championships'),
]);

/*
 * The match history tables of the three roster members that appear on a match
 * card. `bookMatch` adds the owner to a match (as a competitor, or as the
 * referee) and returns the persisted match; `competitorRelation` is the
 * relation the table must eager load.
 */
dataset('livewire previous matches tables', [
    'wrestler' => [
        'component' => WrestlerPreviousMatches::class,
        'ownerParameter' => 'wrestlerId',
        'ownerRouteName' => 'wrestlers.show',
        'createOwner' => static fn (): Model => Wrestler::factory()->create(),
        'bookMatch' => static fn (MatchFactory $match, Model $owner): EventMatch => $match
            ->withCompetitors([$owner, Wrestler::factory()->create()])
            ->createOne(),
        'participantRelation' => 'competitors',
    ],
    'tag team' => [
        'component' => TagTeamPreviousMatches::class,
        'ownerParameter' => 'tagTeamId',
        'ownerRouteName' => 'tag-teams.show',
        'createOwner' => static fn (): Model => TagTeam::factory()->create(),
        'bookMatch' => static fn (MatchFactory $match, Model $owner): EventMatch => $match
            ->withCompetitors([$owner, TagTeam::factory()->create()])
            ->createOne(),
        'participantRelation' => 'competitors',
    ],
    'referee' => [
        'component' => RefereePreviousMatches::class,
        'ownerParameter' => 'refereeId',
        'ownerRouteName' => 'referees.show',
        'createOwner' => static fn (): Model => Referee::factory()->create(),
        'bookMatch' => static function (MatchFactory $match, Model $owner): EventMatch {
            $booked = $match->createOne();
            $booked->referees()->attach($owner);

            return $booked;
        },
        'participantRelation' => 'referees',
    ],
]);

/*
 * The manager assignment history tables: every row of the pivot table links a
 * parent to a child with a hired and a fired date, and the table lists the
 * ended assignments of one parent. `createChild` takes a display name, which
 * people store as a first and a last name.
 */
dataset('livewire assignment history tables', [
    'manager wrestlers' => [
        'component' => ManagerPreviousWrestlers::class,
        'parameter' => 'managerId',
        'createParent' => static fn (): Model => Manager::factory()->create(),
        'createChild' => static fn (string $name): Model => Wrestler::factory()->create(['name' => $name]),
        'pivot' => WrestlerManager::class,
        'parentColumn' => 'manager_id',
        'childColumn' => 'wrestler_id',
        'childRelation' => 'wrestler',
        'placeholder' => 'Search wrestlers',
    ],
    'manager tag teams' => [
        'component' => ManagerPreviousTagTeams::class,
        'parameter' => 'managerId',
        'createParent' => static fn (): Model => Manager::factory()->create(),
        'createChild' => static fn (string $name): Model => TagTeam::factory()->create(['name' => $name]),
        'pivot' => TagTeamManager::class,
        'parentColumn' => 'manager_id',
        'childColumn' => 'tag_team_id',
        'childRelation' => 'tagTeam',
        'placeholder' => 'Search tag teams',
    ],
    'wrestler managers' => [
        'component' => WrestlerPreviousManagers::class,
        'parameter' => 'wrestlerId',
        'createParent' => static fn (): Model => Wrestler::factory()->create(),
        'createChild' => static fn (string $name): Model => Manager::factory()->create([
            'first_name' => strtok($name, ' '),
            'last_name' => trim((string) strstr($name, ' ')),
        ]),
        'pivot' => WrestlerManager::class,
        'parentColumn' => 'wrestler_id',
        'childColumn' => 'manager_id',
        'childRelation' => 'manager',
        'placeholder' => 'Search managers',
    ],
    'tag team managers' => [
        'component' => TagTeamPreviousManagers::class,
        'parameter' => 'tagTeamId',
        'createParent' => static fn (): Model => TagTeam::factory()->create(),
        'createChild' => static fn (string $name): Model => Manager::factory()->create([
            'first_name' => strtok($name, ' '),
            'last_name' => trim((string) strstr($name, ' ')),
        ]),
        'pivot' => TagTeamManager::class,
        'parentColumn' => 'tag_team_id',
        'childColumn' => 'manager_id',
        'childRelation' => 'manager',
        'placeholder' => 'Search managers',
    ],
]);

/*
 * The membership history tables: a child (wrestler, tag team or stable) joined
 * and left a parent for a period, and the table lists the ended memberships of
 * one parent. `link` stores one membership; `linkRoute` is the route the
 * rendered child links to, when the table links its children; `unknownWhenDeleted`
 * marks the tables that show "Unknown" for a deleted child.
 */
/**
 * @return array<string, array<string, mixed>>
 */
function livewireMembershipHistoryTables(): array
{
    return [
        'stable wrestlers' => [
            'component' => StablePreviousWrestlers::class,
            'parameter' => 'stableId',
            'createParent' => static fn (): Model => Stable::factory()->create(),
            'createChild' => static fn (string $name): Model => Wrestler::factory()->create(['name' => $name]),
            'link' => static fn (Model $stable, Model $wrestler, CarbonInterface $from, ?CarbonInterface $to) => StableWrestler::query()->create([
                'stable_id' => $stable->getKey(),
                'wrestler_id' => $wrestler->getKey(),
                'joined_at' => $from,
                'left_at' => $to,
            ]),
            'placeholder' => 'Search wrestlers',
            'linkRoute' => 'wrestlers.show',
            'unknownWhenDeleted' => true,
        ],
        'stable tag teams' => [
            'component' => StablePreviousTagTeams::class,
            'parameter' => 'stableId',
            'createParent' => static fn (): Model => Stable::factory()->create(),
            'createChild' => static fn (string $name): Model => TagTeam::factory()->create(['name' => $name]),
            'link' => static fn (Model $stable, Model $tagTeam, CarbonInterface $from, ?CarbonInterface $to) => StableTagTeam::query()->create([
                'stable_id' => $stable->getKey(),
                'tag_team_id' => $tagTeam->getKey(),
                'joined_at' => $from,
                'left_at' => $to,
            ]),
            'placeholder' => 'Search tag teams',
            'linkRoute' => 'tag-teams.show',
            'unknownWhenDeleted' => true,
        ],
        'tag team wrestlers' => [
            'component' => TagTeamPreviousWrestlers::class,
            'parameter' => 'tagTeamId',
            'createParent' => static fn (): Model => TagTeam::factory()->create(),
            'createChild' => static fn (string $name): Model => Wrestler::factory()->create(['name' => $name]),
            'link' => static fn (Model $tagTeam, Model $wrestler, CarbonInterface $from, ?CarbonInterface $to) => TagTeamWrestler::query()->create([
                'tag_team_id' => $tagTeam->getKey(),
                'wrestler_id' => $wrestler->getKey(),
                'joined_at' => $from,
                'left_at' => $to,
            ]),
            'placeholder' => 'Search wrestlers',
            'linkRoute' => 'wrestlers.show',
            'unknownWhenDeleted' => true,
        ],
        'tag team stables' => [
            'component' => TagTeamPreviousStables::class,
            'parameter' => 'tagTeamId',
            'createParent' => static fn (): Model => TagTeam::factory()->create(),
            'createChild' => static fn (string $name): Model => Stable::factory()->create(['name' => $name]),
            'link' => static fn (TagTeam $tagTeam, Stable $stable, CarbonInterface $from, ?CarbonInterface $to) => $stable->tagTeams()->attach($tagTeam, [
                'joined_at' => $from,
                'left_at' => $to,
            ]),
            'placeholder' => 'Search stables',
            'linkRoute' => null,
            'unknownWhenDeleted' => false,
        ],
        'wrestler stables' => [
            'component' => WrestlerPreviousStables::class,
            'parameter' => 'wrestlerId',
            'createParent' => static fn (): Model => Wrestler::factory()->create(),
            'createChild' => static fn (string $name): Model => Stable::factory()->create(['name' => $name]),
            'link' => static fn (Wrestler $wrestler, Stable $stable, CarbonInterface $from, ?CarbonInterface $to) => $stable->wrestlers()->attach($wrestler, [
                'joined_at' => $from,
                'left_at' => $to,
            ]),
            'placeholder' => 'Search stables',
            'linkRoute' => null,
            'unknownWhenDeleted' => false,
        ],
        'wrestler tag teams' => [
            'component' => WrestlerPreviousTagTeams::class,
            'parameter' => 'wrestlerId',
            'createParent' => static fn (): Model => Wrestler::factory()->create(),
            'createChild' => static fn (string $name): Model => TagTeam::factory()->create(['name' => $name]),
            'link' => static fn (Model $wrestler, Model $tagTeam, CarbonInterface $from, ?CarbonInterface $to) => TagTeamWrestler::factory()->create([
                'tag_team_id' => $tagTeam->getKey(),
                'wrestler_id' => $wrestler->getKey(),
                'joined_at' => $from,
                'left_at' => $to,
            ]),
            'placeholder' => 'Search tag teams',
            'linkRoute' => 'tag-teams.show',
            'unknownWhenDeleted' => false,
        ],
    ];
}

dataset('livewire membership history tables', function (): Generator {
    foreach (livewireMembershipHistoryTables() as $table => $row) {
        yield $table => array_diff_key($row, ['unknownWhenDeleted' => true]);
    }
});

dataset('livewire membership history tables that name deleted records unknown', function (): Generator {
    foreach (livewireMembershipHistoryTables() as $table => $row) {
        if (! $row['unknownWhenDeleted']) {
            continue;
        }

        yield $table => [
            'component' => $row['component'],
            'parameter' => $row['parameter'],
            'createParent' => $row['createParent'],
            'createChild' => $row['createChild'],
            'link' => $row['link'],
        ];
    }
});
