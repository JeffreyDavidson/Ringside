<?php

declare(strict_types=1);

use App\Actions\Events\SaveEventPackAction;
use App\Data\Events\EventPackData;
use App\Exceptions\Events\EventPackConflictException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\ViewModels\Events\EventPackViewModel;

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
        ->and($statements[$matchesLock]['sql'])->toContain('order by "events_matches"."id" asc')
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

test('a pack save takes the card locks once in order, then writes the new version, fingerprint and content in the stored key order', function (): void {
    // Arrange
    $event = Event::factory()->create();
    EventMatch::factory()->for($event)->create();
    $data = resolve(EventPackViewModel::class)->data($event);

    // Act
    $statements = recordStatements(fn () => resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated([...$data['content'], 'notes' => 'Saved note']), false));

    // Assert
    $writes = array_values(array_filter(
        array_column($statements, 'sql'),
        fn (string $sql): bool => str_contains($sql, 'for update') || str_starts_with($sql, 'insert into "event_packs"'),
    ));
    $pack = $event->pack()->firstOrFail();
    expect($writes)->toBe([
        'select * from "events" where "events"."id" = ? and "events"."deleted_at" is null limit 1  /* for update */',
        'select * from "events_matches" where "event_id" = ? order by "events_matches"."id" asc  /* for update */',
        'select * from "event_packs" where "event_id" = ? limit 1  /* for update */',
        'select * from "event_live_runs" where "event_id" = ? limit 1  /* for update */',
        'insert into "event_packs" ("event_id", "version", "card_fingerprint", "content", "updated_at", "created_at") values (?, ?, ?, ?, ?, ?)',
    ])
        ->and($pack->version)->toBe(1)
        ->and($pack->card_fingerprint)->toBe($data['card']['fingerprint'])
        ->and(array_keys($pack->content))->toBe(['timezone', 'doors_at', 'revision', 'master', 'notes', 'follow_up', 'matches', 'segments'])
        ->and($pack->content['notes'])->toBe('Saved note');
});

test('a second pack save keeps the saved production plan and sponsor tasks and replaces only the submitted matches', function (): void {
    // Arrange
    $event = Event::factory()->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    $action = resolve(SaveEventPackAction::class);
    $action->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated([...$data['content'], 'sponsor_tasks' => [['id' => 'a75f5717-2c9f-46df-b405-7f9670eb4995', 'label' => 'Banner', 'instruction' => 'Hang it', 'owner' => null]]]), false);
    $stored = $event->pack()->firstOrFail();
    $stored->content = [...$stored->content, 'production' => ['notes' => 'Kept', 'assignments' => [], 'cues' => []]];
    $stored->save();

    // Act
    $action->handle($event, 1, $data['card']['fingerprint'], EventPackData::fromValidated([...$data['content'], 'notes' => 'Second']), false);

    // Assert
    $pack = $event->pack()->firstOrFail();
    expect($pack->version)->toBe(2)
        ->and($pack->content['notes'])->toBe('Second')
        ->and(data_get($pack->content, 'sponsor_tasks.0.label'))->toBe('Banner')
        ->and(data_get($pack->content, 'production'))->toEqual(['notes' => 'Kept', 'assignments' => [], 'cues' => []]);
});

test('a pack saved against a changed card is refused until the changes are reviewed', function (): void {
    // Arrange
    $event = Event::factory()->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    $action = resolve(SaveEventPackAction::class);
    $action->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $event->pack()->firstOrFail()->forceFill(['card_fingerprint' => 'stale-fingerprint'])->save();

    // Act
    $unreviewed = fn () => $action->handle($event, 1, $data['card']['fingerprint'], EventPackData::fromValidated([...$data['content'], 'notes' => 'Unreviewed']), false);

    // Assert
    expect($unreviewed)->toThrow(EventPackConflictException::class)
        ->and($event->pack()->firstOrFail()->version)->toBe(1)
        ->and($event->pack()->firstOrFail()->card_fingerprint)->toBe('stale-fingerprint');
});

test('a reviewed pack save over a changed card replaces the stored fingerprint', function (): void {
    // Arrange
    $event = Event::factory()->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    $action = resolve(SaveEventPackAction::class);
    $action->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $event->pack()->firstOrFail()->forceFill(['card_fingerprint' => 'stale-fingerprint'])->save();

    // Act
    $action->handle($event, 1, $data['card']['fingerprint'], EventPackData::fromValidated([...$data['content'], 'notes' => 'Reviewed']), true);

    // Assert
    $pack = $event->pack()->firstOrFail();
    expect($pack->version)->toBe(2)
        ->and($pack->content['notes'])->toBe('Reviewed')
        ->and($pack->card_fingerprint)->toBe($data['card']['fingerprint']);
});
