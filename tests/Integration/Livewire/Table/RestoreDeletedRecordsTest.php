<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Livewire\Events\Tables\Main as EventsTable;
use App\Livewire\Managers\Tables\Main as ManagersTable;
use App\Livewire\Referees\Tables\Main as RefereesTable;
use App\Livewire\Stables\Tables\Main as StablesTable;
use App\Livewire\TagTeams\Tables\Main as TagTeamsTable;
use App\Livewire\Titles\Tables\Main as TitlesTable;
use App\Livewire\Venues\Tables\Main as VenuesTable;
use App\Livewire\Wrestlers\Tables\Main as WrestlersTable;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * @return array<string, mixed>
 */
function restorableTable(string $component, string $filterKey, string $namespace, Closure $make, Closure $refuse, string $refusalFragment): array
{
    return compact('component', 'filterKey', 'namespace', 'make', 'refuse', 'refusalFragment');
}

/**
 * Whether the record is still soft-deleted, read without the promotion scope of whoever is signed in.
 */
function isStillDeleted(Model $record): bool
{
    return $record->newQuery()->withoutGlobalScopes()->whereKey($record->getKey())->sole()->getAttribute('deleted_at') !== null;
}

/**
 * Promotion ownership attributes, left to the factory when the record belongs to no particular promotion.
 *
 * @return array<string, mixed>
 */
function ownedBy(?Promotion $promotion): array
{
    return $promotion === null ? [] : ['promotion_id' => $promotion->getKey()];
}

// Each table: the filter key that offers the Deleted option, how to create a record named for the test, and how to
// make a restore refuse (with the fragment of the message that explains why).
$restorableTables = [
    'wrestlers' => restorableTable(
        WrestlersTable::class,
        'status',
        'wrestlers',
        fn (string $name, ?Promotion $promotion = null): Wrestler => Wrestler::factory()->create(['name' => $name, ...ownedBy($promotion)]),
        fn (Wrestler $record): bool => $record->restore(),
        'This wrestler has not been deleted.',
    ),
    'managers' => restorableTable(
        ManagersTable::class,
        'status',
        'managers',
        fn (string $name, ?Promotion $promotion = null): Manager => Manager::factory()->create(['first_name' => 'Zed', 'last_name' => $name, ...ownedBy($promotion)]),
        fn (Manager $record): bool => $record->restore(),
        'This manager has not been deleted.',
    ),
    'referees' => restorableTable(
        RefereesTable::class,
        'status',
        'referees',
        fn (string $name, ?Promotion $promotion = null): Referee => Referee::factory()->create(['first_name' => 'Zed', 'last_name' => $name, ...ownedBy($promotion)]),
        fn (Referee $record): bool => $record->restore(),
        'This referee has not been deleted.',
    ),
    'tag teams' => restorableTable(
        TagTeamsTable::class,
        'status',
        'tag-teams',
        fn (string $name, ?Promotion $promotion = null): TagTeam => TagTeam::factory()->create(['name' => $name, ...ownedBy($promotion)]),
        fn (TagTeam $record): TagTeam => TagTeam::factory()->employed()->create(['name' => $record->name, 'promotion_id' => $record->promotion_id]),
        'Unable to restore this tag team.',
    ),
    'stables' => restorableTable(
        StablesTable::class,
        'status',
        'stables',
        fn (string $name, ?Promotion $promotion = null): Stable => Stable::factory()->inactive()->create(['name' => $name, ...ownedBy($promotion)]),
        fn (Stable $record): Stable => Stable::factory()->inactive()->create(['name' => $record->name, 'promotion_id' => $record->promotion_id]),
        'cannot be restored: name conflicts with existing stable',
    ),
    'titles' => restorableTable(
        TitlesTable::class,
        'status',
        'titles',
        fn (string $name, ?Promotion $promotion = null): Title => Title::factory()->create(['name' => $name, ...ownedBy($promotion)]),
        fn (Title $record): Title => Title::factory()->create(['name' => $record->name, 'promotion_id' => $record->promotion_id]),
        'the name conflicts with existing title',
    ),
    'events' => restorableTable(
        EventsTable::class,
        'status',
        'events',
        fn (string $name, ?Promotion $promotion = null): Event => Event::factory()->scheduled()->create(['name' => $name, 'venue_id' => Venue::factory()->create(['name' => 'Madison Square Garden']), ...ownedBy($promotion)]),
        fn (Event $record): Event => Event::factory()->create(['venue_id' => $record->venue_id, 'date' => $record->date]),
        'Venue [Madison Square Garden] is already booked',
    ),
    'venues' => restorableTable(
        VenuesTable::class,
        'deleted',
        'venues',
        fn (string $name, ?Promotion $promotion = null): Venue => Venue::factory()->create(['name' => $name]),
        fn (Venue $record): Venue => Venue::factory()->create(['name' => $record->name]),
        'the name conflicts with existing venue',
    ),
];

dataset('tables with a deleted option', fn (): array => array_map(
    fn (array $table): array => [$table['component'], $table['filterKey'], $table['make']],
    $restorableTables,
));

dataset('tables with a linked record name', fn (): array => array_map(
    fn (array $table): array => [$table['component'], $table['filterKey'], $table['make']],
    Arr::except($restorableTables, 'venues'),
));

dataset('tables with a restore action', fn (): array => array_map(
    fn (array $table): array => [$table['component'], $table['filterKey'], $table['make'], $table['namespace']],
    $restorableTables,
));

dataset('tables with a refusable restore', fn (): array => array_map(
    fn (array $table): array => [$table['component'], $table['filterKey'], $table['make'], $table['refuse'], $table['refusalFragment']],
    $restorableTables,
));

describe('listing deleted records', function (): void {
    beforeEach(fn () => actingAs(administrator()));

    test('the index table hides deleted records by default and lists only them under the Deleted option', function (string $component, string $filterKey, Closure $make): void {
        // Arrange
        $make('Active Record', null);
        $make('Removed Record', null)->delete();
        $table = livewire($component);

        // Act
        $table->set("filterValues.{$filterKey}", 'deleted');

        // Assert
        $table
            ->assertSet("filterValues.{$filterKey}", 'deleted')
            ->assertSee('Removed Record')
            ->assertDontSee('Active Record');

        // Act
        $table->set("filterValues.{$filterKey}", '');

        // Assert
        $table
            ->assertSee('Active Record')
            ->assertDontSee('Removed Record');
    })->with('tables with a deleted option');

    test('the index table counts deleted records in the status metadata', function (string $component, string $filterKey, Closure $make): void {
        // Arrange
        $make('Active Record', null);
        $make('Removed Record', null)->delete();

        // Act
        $table = livewire($component);

        // Assert
        $table->assertSet('metadataSnapshot.total', 1);
    })->with('tables with a deleted option');

    test('a deleted row offers only Restore with a confirmation naming the record', function (string $component, string $filterKey, Closure $make): void {
        // Arrange
        $record = $make('Removed Record', null);
        $record->delete();
        $table = livewire($component);

        // Act
        $table->set("filterValues.{$filterKey}", 'deleted');

        // Assert
        $table
            ->assertSeeHtml("wire:click=\"restore({$record->getKey()})\"")
            ->assertSeeHtml('wire:confirm="Restore')
            ->assertSee('Restore')
            ->assertDontSeeHtml('wire:click="delete(')
            ->assertDontSeeHtml("modelId: {$record->getKey()}")
            ->assertDontSeeHtml('<span>'.__('core.row_actions.view').'</span>');
    })->with('tables with a deleted option');

    test('a deleted row shows its name as plain text because its show page does not exist', function (string $component, string $filterKey, Closure $make): void {
        // Arrange
        $make('Removed Record', null)->delete();
        $make('Active Record', null);
        $table = livewire($component);

        // Act
        $table->set("filterValues.{$filterKey}", 'deleted');

        // Assert
        $table
            ->assertSeeHtml('data-test="deleted-record-name"')
            ->assertDontSeeHtml('/show');

        // Act
        $table->set("filterValues.{$filterKey}", '');

        // Assert
        $table->assertDontSeeHtml('data-test="deleted-record-name"');
    })->with('tables with a linked record name');
});

describe('who sees the deleted list', function (): void {
    test('a member who may only view gets no Deleted option, count or rows', function (string $component, string $filterKey, Closure $make): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $make('Active Record', $promotion);
        $make('Removed Record', $promotion)->delete();
        actingAsPromotionMember($promotion, MembershipRole::Member);

        // Act
        $table = livewire($component)->set("filterValues.{$filterKey}", 'deleted');

        // Assert
        $table
            ->assertSet('metadataSnapshot.statuses', fn (array $statuses): bool => ! in_array('deleted', array_column($statuses, 'value'), true))
            ->assertSet('metadataSnapshot.total', 1)
            ->assertDontSee('Removed Record');
    })->with('tables with a linked record name');

    test('a member who may restore still gets the Deleted option, count and rows', function (string $component, string $filterKey, Closure $make): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $make('Active Record', $promotion);
        $make('Removed Record', $promotion)->delete();
        actingAsPromotionMember($promotion, MembershipRole::Manager);

        // Act
        $table = livewire($component)->set("filterValues.{$filterKey}", 'deleted');

        // Assert
        $table
            ->assertSet('metadataSnapshot.statuses', fn (array $statuses): bool => in_array(['value' => 'deleted', 'label' => 'Deleted', 'count' => 1], $statuses, true))
            ->assertSee('Removed Record')
            ->assertDontSee('Active Record');
    })->with('tables with a linked record name');

    test('only administrators open the venues table, so only they see its deleted venues', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        actingAsPromotionMember($promotion, MembershipRole::Owner);

        // Act
        $table = livewire(VenuesTable::class);

        // Assert
        $table->assertForbidden();
    });
});

describe('restoring a deleted record', function (): void {
    test('the index table restores the record and drops it from the Deleted list', function (string $component, string $filterKey, Closure $make, string $namespace): void {
        // Arrange
        actingAs(administrator());
        $record = $make('Removed Record', null);
        $record->delete();
        $table = livewire($component)->set("filterValues.{$filterKey}", 'deleted');

        // Act
        $table->call('restore', $record->getKey());

        // Assert
        $table
            ->assertDispatched('flash-message', type: 'status', message: __("{$namespace}.actions.restored"))
            ->assertDontSee('Removed Record')
            ->assertSet('metadataSnapshot.total', 1);

        expect(isStillDeleted($record))->toBeFalse();
    })->with('tables with a restore action');

    test('a member who may only view cannot restore a deleted record', function (string $component, string $filterKey, Closure $make): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $record = $make('Removed Record', $promotion);
        $record->delete();
        actingAs(administrator());
        $table = livewire($component);
        actingAsPromotionMember($promotion, MembershipRole::Member);

        // Act
        $table->call('restore', $record->getKey());

        // Assert
        $table->assertForbidden();

        expect(isStillDeleted($record))->toBeTrue();
    })->with('tables with a deleted option');

    test('an owner of another promotion cannot reach a deleted record through the index table', function (string $component, string $filterKey, Closure $make): void {
        // Arrange
        $record = $make('Removed Record', Promotion::factory()->create());
        $record->delete();
        actingAs(administrator());
        $table = livewire($component);
        actingAsPromotionMember(Promotion::factory()->create(), MembershipRole::Owner);

        // Act
        $table->call('restore', $record->getKey());

        // Assert
        // Venues are shared by every promotion, so the venue policy refuses before any promotion scope can hide them.
        match ($filterKey) {
            'deleted' => $table->assertForbidden(),
            default => $table->assertNotFound(),
        };

        expect(isStillDeleted($record))->toBeTrue();
    })->with('tables with a deleted option');

    test('a restore the business rules refuse shows the reason for each record type', function (string $component, string $filterKey, Closure $make, Closure $refuseRestore, string $messageFragment): void {
        // Arrange
        actingAs(administrator());
        $record = $make('Removed Record', null);
        $record->delete();
        $table = livewire($component)->set("filterValues.{$filterKey}", 'deleted');
        $refuseRestore($record);

        // Act
        $table->call('restore', $record->getKey());

        // Assert
        $table->assertDispatched(
            'flash-message',
            fn (string $event, array $params): bool => ($params['type'] ?? null) === 'error'
                && str_contains((string) ($params['message'] ?? ''), $messageFragment),
        );
        $table->assertNotDispatched('flash-message', type: 'status');
    })->with('tables with a refusable restore');
});

describe('restoring around a deleted venue', function (): void {
    beforeEach(fn () => actingAs(administrator()));

    test('the events table tells the user to restore the venue first', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['name' => 'Madison Square Garden']);
        $event = Event::factory()->for($venue)->scheduled()->create();
        $event->delete();
        $venue->delete();
        $table = livewire(EventsTable::class)->set('filterValues.status', 'deleted');

        // Act
        $table->call('restore', $event->getKey());

        // Assert
        $table->assertDispatched(
            'flash-message',
            fn (string $name, array $params): bool => ($params['type'] ?? null) === 'error'
                && str_contains((string) ($params['message'] ?? ''), "its venue 'Madison Square Garden' is deleted. Restore the venue first."),
        );

        expect(isStillDeleted($event))->toBeTrue();
    });

    test('the venues table names the date a venue hosts more than one event', function (): void {
        // Arrange
        $date = now()->addWeek();
        $venue = Venue::factory()->create();
        Event::factory()->for($venue)->create(['date' => $date]);
        Event::factory()->for($venue)->create(['date' => $date->copy()->setTime(20, 0)]);
        $venue->delete();
        $table = livewire(VenuesTable::class)->set('filterValues.deleted', 'deleted');

        // Act
        $table->call('restore', $venue->getKey());

        // Assert
        $table->assertDispatched(
            'flash-message',
            fn (string $name, array $params): bool => ($params['type'] ?? null) === 'error'
                && str_contains((string) ($params['message'] ?? ''), "hosts more than one event on {$date->format('M j, Y')}"),
        );

        expect(isStillDeleted($venue))->toBeTrue();
    });
});
