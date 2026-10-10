<?php

declare(strict_types=1);

use App\Actions\Events\CreateAction;
use App\Data\Events\EventData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Events\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;

test('it creates a scheduled event with its venue and preview', function (): void {
    $venue = Venue::factory()->create();
    $date = now()->addWeek()->setTime(19, 0);
    $data = new EventData('Summer Slam', $date, $venue, 'A major event.');

    $event = resolve(CreateAction::class)->handle($data);

    expect($event)
        ->toBeInstanceOf(Event::class)
        ->name->toBe('Summer Slam')
        ->preview->toBe('A major event.')
        ->venue_id->toBe($venue->id)
        ->and($event->date?->toDateTimeString())->toBe($date->toDateTimeString());
});

test('it assigns the active promotion when promotion context is enforced', function (): void {
    $promotion = Promotion::factory()->create();
    $context = app(PromotionContextService::class);

    $context->set($promotion);
    $context->enforce();

    $event = resolve(CreateAction::class)->handle(
        new EventData('Promotion Event', null, null, null),
    );

    expect($event->promotion_id)->toBe($promotion->id)
        ->and(Event::query()->findOrFail($event->id)->is($event))->toBeTrue();
});

test('it locks the venue row before checking the venue is free and creating the event', function (): void {
    // Arrange
    $venue = Venue::factory()->create();
    $data = new EventData('Locked Venue Event', now()->addWeek()->setTime(19, 0), $venue, null);

    // Act
    $statements = recordStatements(fn () => resolve(CreateAction::class)->handle($data));

    // Assert
    $venueLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "venues"'));
    $availabilityCheck = statementPosition($statements, fn (array $statement): bool => str_contains($statement['sql'], 'from "events"') && str_contains($statement['sql'], '"venue_id"'));
    $insert = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "events"'));

    expect(lockedRowIds($statements, 'venues'))->toBe([$venue->id])
        ->and($venueLock)->toBeLessThan($availabilityCheck)
        ->and($availabilityCheck)->toBeLessThan($insert);
});

describe('event name guard', function (): void {
    test('it locks the name of the promotion before the venue row and the insert', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        enforcePromotionContext($promotion);
        $venue = Venue::factory()->create();
        $data = new EventData('  Summer Slam ', now()->addWeek()->setTime(19, 0), $venue, null);
        $lock = resolve(RecordNameLock::class);

        // Act
        $statements = recordStatements(fn () => resolve(CreateAction::class)->handle($data));

        // Assert
        $nameLock = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "record_name_locks"'));
        $venueLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "venues"'));
        $insert = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "events"'));

        expect($statements[$nameLock]['bindings'])->toBe([$lock->key(GuardedName::EventName, $promotion->id, 'Summer Slam')])
            ->and($nameLock)->toBeLessThan($venueLock)
            ->and($venueLock)->toBeLessThan($insert);
    });

    test('it stores the trimmed name', function (): void {
        // Arrange
        $data = new EventData('  Summer Slam ', null, null, null);

        // Act
        $event = resolve(CreateAction::class)->handle($data);

        // Assert
        expect($event->name)->toBe('Summer Slam');
    });

    test('it rejects a name another event of the promotion already uses, even with surrounding space or deleted', function (string $name, bool $deleted): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        enforcePromotionContext($promotion);
        $existing = Event::factory()->for($promotion, 'promotion')->create(['name' => 'Summer Slam']);

        if ($deleted) {
            $existing->delete();
        }

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(new EventData($name, null, null, null));

        // Assert
        expect($create)->toThrow(NameTakenException::class, "An event named 'Summer Slam' already exists in this promotion.")
            ->and(Event::query()->withoutGlobalScopes()->withTrashed()->where('name', 'Summer Slam')->count())->toBe(1);
    })->with([
        'exact' => ['Summer Slam', false],
        'leading space' => [' Summer Slam', false],
        'deleted' => ['Summer Slam', true],
    ]);

    test('it rejects a name an unowned event already uses when creating without a promotion', function (): void {
        // Arrange
        Event::factory()->create(['name' => 'Summer Slam']);

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(new EventData('Summer Slam', null, null, null));

        // Assert
        expect($create)->toThrow(NameTakenException::class)
            ->and(Event::query()->withoutGlobalScopes()->where('name', 'Summer Slam')->count())->toBe(1);
    });

    test('it allows a name only another promotion uses', function (): void {
        // Arrange
        Event::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'Summer Slam']);
        $promotion = Promotion::factory()->create();
        enforcePromotionContext($promotion);

        // Act
        $event = resolve(CreateAction::class)->handle(new EventData('Summer Slam', null, null, null));

        // Assert
        expect($event->promotion_id)->toBe($promotion->id);
    });
});
