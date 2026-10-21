<?php

declare(strict_types=1);

use App\Enums\Events\EventStatus;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Shared\EmploymentStatus;
use App\Enums\Stables\StableStatus;
use App\Enums\Titles\TitleStatus;
use App\Livewire\Events\Tables\Main as EventsMain;
use App\Livewire\Managers\Tables\Main as ManagersMain;
use App\Livewire\Referees\Tables\Main as RefereesMain;
use App\Livewire\Stables\Tables\Main as StablesMain;
use App\Livewire\Table\DataTableComponent;
use App\Livewire\Table\Filters\SelectFilter;
use App\Livewire\TagTeams\Tables\Main as TagTeamsMain;
use App\Livewire\Titles\Tables\Main as TitlesMain;
use App\Livewire\Wrestlers\Tables\Main as WrestlersMain;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;
use function Pest\Livewire\livewire;

/**
 * The status filter of the seven index tables that offer a Deleted option: its options, and the query each value builds.
 *
 * - options: the filter options a person who may not restore sees (administrators also get Deleted last)
 * - filtered: the query the table must build for a status value, written with the builder method directly
 *
 * @return array<string, array{component: class-string, model: class-string<Model>, options: array<string, string>, filtered: Closure}>
 */
function statusFilterCatalog(): array
{
    $employment = [
        '' => 'All',
        'employed' => 'Employed',
        'future_employment' => 'Awaiting Employment',
        'released' => 'Released',
        'retired' => 'Retired',
        'unemployed' => 'Unemployed',
    ];
    $byEmployment = static fn (Builder $query, string $value): Builder => $query->whereEmploymentStatus(EmploymentStatus::from($value));

    return [
        'wrestlers' => ['component' => WrestlersMain::class, 'model' => Wrestler::class, 'options' => $employment, 'filtered' => $byEmployment],
        'managers' => ['component' => ManagersMain::class, 'model' => Manager::class, 'options' => $employment, 'filtered' => $byEmployment],
        'referees' => ['component' => RefereesMain::class, 'model' => Referee::class, 'options' => $employment, 'filtered' => $byEmployment],
        'tag teams' => ['component' => TagTeamsMain::class, 'model' => TagTeam::class, 'options' => $employment, 'filtered' => $byEmployment],
        'stables' => [
            'component' => StablesMain::class,
            'model' => Stable::class,
            'options' => [
                '' => 'All',
                'unformed' => 'Not Yet Formed',
                'pending_establishment' => 'Not Yet Established',
                'active' => 'Active',
                'inactive' => 'Inactive',
                'retired' => 'Retired',
            ],
            'filtered' => static fn (Builder $query, string $value): Builder => $query->whereStatus(StableStatus::from($value)),
        ],
        'titles' => [
            'component' => TitlesMain::class,
            'model' => Title::class,
            'options' => [
                '' => 'All',
                'undebuted' => 'Not Yet Debuted',
                'pending_debut' => 'Scheduled to Debut',
                'active' => 'Active',
                'inactive' => 'Inactive',
                'retired' => 'Retired',
            ],
            'filtered' => static fn (Builder $query, string $value): Builder => $query->whereStatus(TitleStatus::from($value)),
        ],
        'events' => [
            'component' => EventsMain::class,
            'model' => Event::class,
            'options' => [
                '' => 'All',
                'past' => 'Past',
                'scheduled' => 'Scheduled',
                'unscheduled' => 'Unscheduled',
            ],
            'filtered' => static fn (Builder $query, string $value): Builder => $query->whereStatus(EventStatus::from($value)),
        ],
    ];
}

dataset('status filter tables', function (): Generator {
    foreach (statusFilterCatalog() as $table => $spec) {
        yield $table => [$spec['component'], $spec['model']];
    }
});

dataset('status filter options', function (): Generator {
    foreach (statusFilterCatalog() as $table => $spec) {
        yield $table => [$spec['component'], $spec['options']];
    }
});

dataset('status filter selections', function (): Generator {
    foreach (statusFilterCatalog() as $table => $spec) {
        foreach (array_slice(array_keys($spec['options']), 1) as $value) {
            yield "{$table} {$value}" => [$spec['component'], $spec['model'], (string) $value, $spec['filtered']];
        }
    }
});

/**
 * The table's status filter as it is built for the signed-in person.
 */
function statusFilterOf(string $component): SelectFilter
{
    $table = livewire($component)->instance();
    $filters = $table instanceof DataTableComponent ? $table->filters() : [];

    foreach ($filters as $filter) {
        if ($filter instanceof SelectFilter && $filter->getKey() === 'status') {
            return $filter;
        }
    }

    throw new RuntimeException('The table has no status filter.');
}

/**
 * The SQL and bindings a query would run, in one spelling for every engine.
 *
 * @param  Builder<*>  $query
 * @return array{string, array<int, mixed>}
 */
function statusFilterStatement(Builder $query): array
{
    return [
        normalizedSql($query->toSql()),
        array_map(fn (mixed $binding): mixed => $binding instanceof DateTimeInterface ? $binding->format('Y-m-d H:i:s.u') : $binding, $query->getBindings()),
    ];
}

describe('status filter', function (): void {
    beforeEach(fn () => travelTo(Date::parse('2026-03-02 12:00:00')));

    it('offers every status and appends Deleted for people who may restore', function (string $component, array $options): void {
        // Arrange
        actingAs(administrator());

        // Act
        $filter = statusFilterOf($component);

        // Assert
        expect($filter->getOptions())->toBe([...$options, 'deleted' => 'Deleted']);
    })->with('status filter options');

    it('leaves Deleted out for people who may not restore', function (string $component, array $options): void {
        // Arrange
        actingAsPromotionMember(Promotion::factory()->create(), MembershipRole::Member);

        // Act
        $filter = statusFilterOf($component);

        // Assert
        expect($filter->getOptions())->toBe($options);
    })->with('status filter options');

    it('filters by the chosen status', function (string $component, string $model, string $value, Closure $filtered): void {
        // Arrange
        actingAs(administrator());
        $filter = statusFilterOf($component);
        $query = $model::query();

        // Act
        $filter->apply($query, $value);

        // Assert
        expect(statusFilterStatement($query))->toBe(statusFilterStatement($filtered($model::query(), $value)));
    })->with('status filter selections');

    it('lists only soft-deleted rows when an administrator picks Deleted', function (string $component, string $model): void {
        // Arrange
        actingAs(administrator());
        $filter = statusFilterOf($component);
        $query = $model::query();

        // Act
        $filter->apply($query, 'deleted');

        // Assert
        expect(statusFilterStatement($query))->toBe(statusFilterStatement($model::query()->onlyTrashed()));
    })->with('status filter tables');

    it('ignores Deleted forced into the filter by people who may not restore', function (string $component, string $model): void {
        // Arrange
        actingAsPromotionMember(Promotion::factory()->create(), MembershipRole::Member);
        $filter = statusFilterOf($component);
        $query = $model::query();

        // Act
        $filter->apply($query, 'deleted');

        // Assert
        expect(statusFilterStatement($query))->toBe(statusFilterStatement($model::query()));
    })->with('status filter tables');

    it('does not filter by a value that is not a status', function (string $component, string $model): void {
        // Arrange
        actingAs(administrator());
        $filter = statusFilterOf($component);
        $query = $model::query();

        // Act
        $filter->apply($query, 'not-a-status');

        // Assert
        expect(statusFilterStatement($query))->toBe(statusFilterStatement($model::query()));
    })->with('status filter tables');
});
