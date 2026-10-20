<?php

declare(strict_types=1);

use App\Lifecycle\Events\EventCardLock;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The table of every row-locking statement, in the order the statements ran.
 *
 * @param  array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>  $statements
 * @return list<string>
 */
function lockedTables(array $statements): array
{
    return array_values(array_map(
        fn (array $statement): string => preg_match('/from "([a-z_]+)"/', $statement['sql'], $match) === 1 ? $match[1] : $statement['sql'],
        array_filter($statements, fn (array $statement): bool => $statement['locked']),
    ));
}

describe('lock', function (): void {
    test('it locks the event, its matches, its pack and its live run in that order', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        EventMatch::factory()
            ->forEvent($event)
            ->count(2)
            ->create();

        // Act
        $statements = recordStatements(fn () => resolve(EventCardLock::class)->lock($event->id));

        // Assert
        expect(lockedTables($statements))->toBe(['events', 'events_matches', 'event_packs', 'event_live_runs'])
            ->and(collect($statements)->first(fn (array $statement): bool => str_contains($statement['sql'], 'from "events_matches"'))['sql'] ?? '')
            ->toContain('order by "events_matches"."id" asc');
    });

    test('it returns the fresh event with its pack and live run', function (): void {
        // Arrange
        $event = Event::factory()
            ->create(['name' => 'Before']);
        $pack = EventPack::factory()
            ->for($event)
            ->create();
        $run = EventLiveRun::factory()
            ->for($event)
            ->create();
        Event::query()->whereKey($event->id)->update(['name' => 'After']);

        // Act
        $card = resolve(EventCardLock::class)->lock($event->id);

        // Assert
        expect($card['event']->name)->toBe('After')
            ->and($card['pack']?->is($pack))->toBeTrue()
            ->and($card['run']?->is($run))->toBeTrue();
    });

    test('it returns no pack or live run while the event has none', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();

        // Act
        $card = resolve(EventCardLock::class)->lock($event->id);

        // Assert
        expect($card['pack'])->toBeNull()
            ->and($card['run'])->toBeNull();
    });

    test('it returns the deleted matches of the card with the others', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        $kept = EventMatch::factory()
            ->forEvent($event)
            ->create();
        $deleted = EventMatch::factory()
            ->forEvent($event)
            ->create();
        $deleted->delete();
        EventMatch::factory()
            ->create();

        // Act
        $card = resolve(EventCardLock::class)->lock($event->id);

        // Assert
        expect($card['matches']->modelKeys())->toBe([$kept->id, $deleted->id]);
    });

    test('it refuses a deleted event', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        $event->delete();

        // Act
        $lock = fn () => resolve(EventCardLock::class)->lock($event->id);

        // Assert
        expect($lock)->toThrow(ModelNotFoundException::class);
    });
});

describe('lock including deleted', function (): void {
    test('it locks a deleted event of the promotion and its card in the same order', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion)
            ->create();
        $event->delete();

        // Act
        $statements = recordStatements(fn () => resolve(EventCardLock::class)->lockIncludingDeleted($event->id, $promotion->id));

        // Assert
        expect(lockedTables($statements))->toBe(['events', 'events_matches', 'event_packs', 'event_live_runs']);
    });

    test('it refuses an event of another promotion', function (): void {
        // Arrange
        $event = Event::factory()
            ->for(Promotion::factory())
            ->create();
        $other = Promotion::factory()
            ->create();

        // Act
        $lock = fn () => resolve(EventCardLock::class)->lockIncludingDeleted($event->id, $other->id);

        // Assert
        expect($lock)->toThrow(ModelNotFoundException::class);
    });
});
