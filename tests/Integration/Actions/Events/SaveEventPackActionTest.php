<?php

declare(strict_types=1);

use App\Actions\Events\SaveEventPackAction;
use App\Data\Events\EventPackData;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\ViewModels\EventPackViewModel;

test('saving locks the owning event then its matches then the pack before inserting', function (): void {
    // Arrange
    $event = Event::factory()->create();
    EventMatch::factory()->for($event)->create();
    $data = resolve(EventPackViewModel::class)->data($event);

    // Act
    $statements = recordStatements(fn () => resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false));

    // Assert
    $eventLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "events"'));
    $matchesLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "events_matches"'));
    $packLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "event_packs"'));
    $insert = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "event_packs"'));
    expect($eventLock)->toBeLessThan($matchesLock)
        ->and($matchesLock)->toBeLessThan($packLock)
        ->and($packLock)->toBeLessThan($insert)
        ->and($statements[$eventLock]['bindings'])->toBe([$event->id])
        ->and($statements[$matchesLock]['bindings'])->toBe([$event->id])
        ->and($statements[$matchesLock]['sql'])->toContain('order by "id" asc')
        ->and($statements[$packLock]['bindings'])->toBe([$event->id])
        ->and($event->pack()->firstOrFail()->version)->toBe(1)
        ->and($event->pack()->with('event')->firstOrFail()->event->is($event))->toBeTrue();
});

test('a repeated first save fails without replacing the existing document', function (): void {
    // Arrange
    $event = Event::factory()->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    $action = resolve(SaveEventPackAction::class);
    $action->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated([...$data['content'], 'notes' => 'Saved first']), false);

    // Act
    $repeat = fn () => $action->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated([...$data['content'], 'notes' => 'Repeated']), false);

    // Assert
    expect($repeat)->toThrow(RecordChangedException::class)
        ->and($event->pack()->firstOrFail()->content['notes'])->toBe('Saved first')
        ->and($event->pack()->count())->toBe(1);
});
