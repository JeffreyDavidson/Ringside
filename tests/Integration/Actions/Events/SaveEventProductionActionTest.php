<?php

declare(strict_types=1);

use App\Actions\Crew\ArchiveCrewContactAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\SaveEventProductionAction;
use App\Data\Events\EventPackData;
use App\Data\Events\EventProductionData;
use App\Exceptions\Events\EventPackConflictException;
use App\Exceptions\RecordChangedException;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\ViewModels\Events\EventPackViewModel;
use App\ViewModels\Events\EventProductionViewModel;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

test('saving production keeps existing pack notes and snapshots one contact with multiple roles', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->for(Promotion::factory())->create();
    $match = EventMatch::factory()->for($event)->create();
    $contact = CrewContact::factory()->create(['promotion_id' => $event->promotion_id, 'name' => 'Alex Operator']);
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated([...$data['content'], 'notes' => 'Existing show note']), false);
    $assignment = ['id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051001', 'contact_id' => $contact->id, 'role' => 'sound', 'timezone' => 'UTC'];
    $production = ['notes' => 'Private production note', 'assignments' => [$assignment, [...$assignment, 'id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051002', 'role' => 'announcer']],
        'cues' => [['id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051003', 'item_key' => 'match:'.$match->id, 'subject' => 'Harbor Hero', 'trigger' => 'On caller signal', 'instruction' => 'Play entrance', 'owner_id' => $assignment['id'], 'track' => 'harbor-theme.mp3']]];

    // Act
    resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated($production), false, signedInUser());

    // Assert
    $pack = $event->pack()->firstOrFail();
    expect($pack->version)->toBe(2)->and($pack->content['notes'])->toBe('Existing show note')
        ->and(data_get($pack->content, 'production.assignments'))->toHaveCount(2)
        ->and(data_get($pack->content, 'production.assignments.0.name'))->toBe('Alex Operator')
        ->and(data_get($pack->content, 'production.cues.0.owner_name'))->toBe('Alex Operator');
});

test('restoring an inactive assignment cannot newly assign an archived contact', function (): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    $original = $data['content']['production'] ?? [];
    resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated(['notes' => null, 'assignments' => [], 'cues' => []]), false, administrator());
    resolve(ArchiveCrewContactAction::class)->handle($fixture['contact'], 1, administrator());

    // Act
    $attempt = fn () => resolve(SaveEventProductionAction::class)->handle($event, 2, $data['card']['fingerprint'], EventProductionData::fromValidated($original), false, administrator());

    // Assert
    expect($attempt)->toThrow(ValidationException::class)
        ->and($event->pack()->firstOrFail()->version)->toBe(2);
});

test('invalid production input leaves the saved pack unchanged', function (string $case): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    $input = $fixture['production'];
    $before = $event->pack()->firstOrFail()->content;

    if ($case === 'foreign contact') {
        $input['assignments'][0]['contact_id'] = CrewContact::factory()->create()->id;
    }

    if ($case === 'unknown owner') {
        $input['cues'][0]['owner_id'] = '4e833c54-5f60-48b8-91c5-dbf85ca51111';
    }

    if ($case === 'foreign item') {
        $input['cues'][0]['item_key'] = 'match:'.EventMatch::factory()->create()->id;
    }

    if ($case === 'duplicate assignment') {
        $input['assignments'][] = $input['assignments'][0];
    }

    if ($case === 'duplicate cue') {
        $input['cues'][] = $input['cues'][0];
    }

    if ($case === 'unsafe url') {
        $input['cues'][0]['url'] = 'javascript:alert(1)';
    }

    if ($case === 'missing role') {
        unset($input['assignments'][0]['role']);
    }

    if ($case === 'credential url') {
        $input['cues'][0]['url'] = 'https://secret:password@example.com/track';
    }

    if ($case === 'other description') {
        $input['assignments'][0]['role'] = 'other';
    }

    if ($case === 'gap' || $case === 'repeated') {
        $input['assignments'][0]['timezone'] = 'America/New_York';
        $input['assignments'][0]['call_at'] = $case === 'gap' ? '2026-03-08T02:30' : '2026-11-01T01:30';
    }

    if ($case === 'cue count') {
        $input['cues'] = array_map(fn (int $i): array => [...$input['cues'][0], 'id' => sprintf('eed36a82-90cb-4a79-b2cc-%012d', $i)], range(1, 21));
    }

    // Act
    $attempt = fn () => resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated($input), false, administrator());

    // Assert
    expect($attempt)->toThrow(ValidationException::class)
        ->and($event->pack()->firstOrFail()->content)->toBe($before)
        ->and($event->pack()->firstOrFail()->version)->toBe(1);
})->with(['foreign contact', 'unknown owner', 'foreign item', 'duplicate assignment', 'duplicate cue', 'unsafe url', 'missing role', 'credential url', 'other description', 'gap', 'repeated', 'cue count']);

test('saving production locks the referenced crew contacts after the event, its matches and its pack', function (): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);

    // Act
    $statements = recordStatements(fn () => resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated($fixture['production']), false, administrator()));

    // Assert
    $eventLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "events"'));
    $matchLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "events_matches"'));
    $packLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "event_packs"'));
    $contactLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "crew_contacts"'));
    $packWrite = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'update "event_packs"'));
    expect($eventLock)->toBeLessThan($matchLock)
        ->and($matchLock)->toBeLessThan($packLock)
        ->and($packLock)->toBeLessThan($contactLock)
        ->and($contactLock)->toBeLessThan($packWrite);
});

test('saving production takes every lock once in order, then updates the pack row and logs the save', function (): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    $before = $event->pack()->firstOrFail();

    // Act
    $statements = recordStatements(fn () => resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated([...$fixture['production'], 'notes' => 'Changed note']), false, administrator()));

    // Assert
    $writes = array_values(array_filter(
        array_column($statements, 'sql'),
        fn (string $sql): bool => str_contains($sql, 'for update') || str_starts_with($sql, 'update "event_packs"') || str_starts_with($sql, 'insert into "activity_log"'),
    ));
    $pack = $event->pack()->firstOrFail();
    expect($writes)->toBe([
        'select * from "events" where "events"."id" = ? and "events"."deleted_at" is null limit 1  /* for update */',
        'select * from "events_matches" where "event_id" = ? order by "events_matches"."id" asc  /* for update */',
        'select * from "event_packs" where "event_id" = ? limit 1  /* for update */',
        'select * from "event_live_runs" where "event_id" = ? limit 1  /* for update */',
        'select * from "crew_contacts" where "promotion_id" = ? and "crew_contacts"."id" in (1) order by "id" asc  /* for update */',
        'update "event_packs" set "version" = ?, "content" = ?, "updated_at" = ? where "id" = ?',
        'insert into "activity_log" ("log_name", "attribute_changes", "properties", "causer_id", "causer_type", "subject_id", "subject_type", "description", "updated_at", "created_at") values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    ])
        ->and($pack->version)->toBe(2)
        ->and($pack->card_fingerprint)->toBe($data['card']['fingerprint'])
        ->and(array_keys($pack->content))->toBe(array_keys($before->content))
        ->and(data_get($pack->content, 'production'))->toEqual([...$fixture['production'], 'notes' => 'Changed note']);
});

test('explicit name refresh and offset call time change only the latest saved plan', function (): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    $contact = $fixture['contact'];
    $contact->name = 'New directory name';
    $contact->save();
    $input = $fixture['production'];
    $input['assignments'][0]['timezone'] = 'America/New_York';
    $input['assignments'][0]['call_at'] = '2026-11-01T01:30';
    $input['assignments'][0]['offset'] = '-05:00';

    // Act
    resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated($input), false, administrator());

    // Assert
    expect(data_get($event->pack()->firstOrFail()->content, 'production.assignments.0.name'))->toBe('PRIVATE-CREW-7Q');
    $input['assignments'][0]['refresh_name'] = true;
    resolve(SaveEventProductionAction::class)->handle($event, 2, $data['card']['fingerprint'], EventProductionData::fromValidated($input), false, administrator());
    expect(data_get($event->pack()->firstOrFail()->content, 'production.assignments.0.name'))->toBe('New directory name')
        ->and(data_get($event->pack()->firstOrFail()->content, 'production.assignments.0.call_at'))->toBe('2026-11-01T01:30');
});

test('ordinary pack saves retain production and removed matches retain their cues', function (): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $fixture['match']->delete();
    $data = resolve(EventPackViewModel::class)->data($event);
    $content = $data['content'];
    unset($content['production']);

    // Act
    resolve(SaveEventPackAction::class)->handle($event, 1, $data['card']['fingerprint'], EventPackData::fromValidated($content), true);
    resolve(SaveEventProductionAction::class)->handle($event, 2, $data['card']['fingerprint'], EventProductionData::fromValidated(['notes' => null, 'assignments' => [], 'cues' => []]), false, administrator());

    // Assert
    expect(data_get($event->pack()->firstOrFail()->content, 'production.cues.0.instruction'))->toBe('PRIVATE-CUE-7Q')
        ->and(data_get($event->pack()->firstOrFail()->content, 'production.assignments.0.active'))->toBeFalse();
    $fixture['match']->restore();
    $sheet = resolve(EventProductionViewModel::class)->data($event, 'plan');
    expect(data_get($sheet, 'rows.0.cues.0.instruction'))->toBe('PRIVATE-CUE-7Q');
});

test('stale production previews cannot overwrite a changed source', function (string $case): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    $version = $case === 'version' ? 0 : 1;
    $fingerprint = $case === 'fingerprint' ? str_repeat('0', 64) : $data['card']['fingerprint'];

    if ($case === 'review') {
        $fixture['match']->match_number = 25;
        $fixture['match']->save();
        $fingerprint = resolve(EventPackViewModel::class)->data($event)['card']['fingerprint'];
    }

    $before = $event->pack()->firstOrFail()->content;

    // Act
    $attempt = fn () => resolve(SaveEventProductionAction::class)->handle($event, $version, $fingerprint, EventProductionData::fromValidated($fixture['production']), false, administrator());

    // Assert
    expect($attempt)->toThrow($case === 'review' ? EventPackConflictException::class : RecordChangedException::class)
        ->and($event->pack()->firstOrFail()->content)->toBe($before);
})->with(['version', 'fingerprint', 'review']);

test('saving production names the actor passed in as the audit causer', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $event = Event::factory()->for(Promotion::factory())->create();
    EventMatch::factory()->for($event)->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $since = lastActivityId();

    // Act
    resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated(['notes' => 'Private production note', 'assignments' => [], 'cues' => []]), false, $actor);

    // Assert
    expect(activityEntriesAfter($since, 'production'))->toBe(['pack-save|'.causerTag($actor)]);
});
