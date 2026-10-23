<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/sponsor-commitment.php';

use App\Actions\Events\CompleteLiveShowAction;
use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\ReviewLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\SaveEventProductionAction;
use App\Actions\Events\SaveSponsorCommitmentAction;
use App\Actions\Events\SaveSponsorInstructionAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Data\Events\EventPackData;
use App\Data\Events\EventProductionData;
use App\Data\Events\LiveShowReviewData;
use App\Data\Events\SponsorInstructionData;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorCommitmentType;
use App\Enums\Events\SponsorInstructionMode;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Lifecycle\Events\LiveShowSourceRequirements;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Matches\EventMatch;
use App\Services\Events\SponsorInstructionService;
use App\ValueObjects\PackKey;
use App\ViewModels\Events\EventPackViewModel;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('reviewed setup wording enters the pack without private promise fields', function (): void {
    // Arrange
    $promise = EventSponsorCommitment::factory()->create(['instruction_label' => 'Entrance banner', 'instruction' => 'Hang before doors', 'instruction_owner' => 'Ring crew', 'notes' => 'PRIVATE-TERMS-7Q', 'agreement_reference' => 'PRIVATE-REF-7Q']);
    $event = $promise->event;
    actingAs(administrator());
    $pack = app(EventPackViewModel::class)->data($event);
    $preview = app(SponsorInstructionService::class)->preview($event, $promise, SponsorInstructionMode::Setup, null, null);

    // Act
    app(SaveSponsorInstructionAction::class)->handle($event, $promise->id, 1, 0, $pack['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, $preview['hash']), signedInUser());

    // Assert
    $content = $event->pack()->firstOrFail()->content;
    expect(data_get($content, 'sponsor_tasks.0'))->toMatchArray(['label' => 'Entrance banner', 'instruction' => 'Hang before doors', 'owner' => 'Ring crew'])
        ->and(json_encode($content))->not->toContain('PRIVATE-TERMS-7Q', 'PRIVATE-REF-7Q')
        ->and($promise->refresh()->version)->toBe(2)
        ->and($promise->revisions()->count())->toBe(1);
});

test('repeated announcements each enter the existing order once and require explicit source adoption', function (): void {
    // Arrange
    $world = sponsorFixture();
    $event = $world['event'];
    $promise = $world['promise'];
    $save = app(SaveSponsorCommitmentAction::class);
    $promise = $save->handle($event, $promise->id, 1, $promise->submission_key, sponsorPromiseData(
        $promise,
        ['type' => SponsorCommitmentType::Announcement, 'instructionLabel' => 'Opening sponsor thanks', 'instruction' => 'Thank Harbor Hardware', 'instructionOwner' => 'Announcer'],
    ), signedInUser());
    $screen = app(EventPackViewModel::class);
    $query = app(SponsorInstructionService::class);
    $publisher = app(SaveSponsorInstructionAction::class);
    $preview = $query->preview($event, $promise, SponsorInstructionMode::Segment, null, null);
    $publisher->handle($event, $promise->id, 2, 0, $screen->data($event)['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Segment, null, null, $preview['hash']), signedInUser());
    $promise->refresh();
    $run = app(PrepareLiveShowAction::class)->handle($event, 0, 1, $screen->data($event)['card']['fingerprint'], administrator());
    $other = $save->handle($event, null, 0, (string) Str::uuid(), sponsorPromiseData($promise, ['description' => 'Second sponsor thanks', 'instructionLabel' => 'Closing sponsor thanks']), signedInUser());
    $preview = $query->preview($event, $other, SponsorInstructionMode::Segment, null, null);

    // Act
    $publisher->handle($event, $other->id, 1, 1, $screen->data($event)['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Segment, null, null, $preview['hash']), signedInUser());

    // Assert
    expect($event->pack()->firstOrFail()->content['segments'])->toHaveCount(2)
        ->and($run->refresh()->content['items'])->toHaveCount(1)
        ->and(app(LiveShowSourceRequirements::class)->hasChanged($event, $run))->toBeTrue();
    app(ReviewLiveShowAction::class)->handle($event, 1, 2, $screen->data($event)['card']['fingerprint'], LiveShowReviewData::fromValidated([]), signedInUser());
    expect($run->refresh()->content['items'])->toHaveCount(2);
});

test('started announcements retain their text and private fulfillment never writes live state', function (): void {
    // Arrange
    $world = sponsorFixture();
    $event = $world['event'];
    $promise = $world['promise'];
    $save = app(SaveSponsorCommitmentAction::class);
    $promise = $save->handle($event, $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['instructionLabel' => 'Sponsor thanks', 'instruction' => 'Thank Harbor Hardware']), signedInUser());
    $query = app(SponsorInstructionService::class);
    $screen = app(EventPackViewModel::class);
    $publisher = app(SaveSponsorInstructionAction::class);
    $publisher->handle($event, $promise->id, 2, 0, $screen->data($event)['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Segment, null, null, $query->preview($event, $promise, SponsorInstructionMode::Segment, null, null)['hash']), signedInUser());
    $promise->refresh();
    $run = app(PrepareLiveShowAction::class)->handle($event, 0, 1, $screen->data($event)['card']['fingerprint'], administrator());
    $transition = app(TransitionLiveItemAction::class);
    $transition->handle($event, 1, $run->content['items'][0]['key'], LiveItemOperation::Start, signedInUser());
    $before = $run->refresh()->content;
    $promise = $save->handle($event, $promise->id, 3, $promise->submission_key, sponsorPromiseData($promise, ['instruction' => 'Revised wording']), signedInUser());
    $preview = $query->preview($event, $promise, SponsorInstructionMode::Segment, $promise->pack_key, null);

    // Act and Assert
    expect(fn () => $publisher->handle($event, $promise->id, 4, 1, $screen->data($event)['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Segment, PackKey::fromString($promise->pack_key ?? ''), null, $preview['hash']), signedInUser()))
        ->toThrow(SponsorCommitmentException::class);
    $promise = $save->handle($event, $promise->id, 4, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Completed, 'evidence' => 'Announcer confirmed the wording']), signedInUser());
    expect($run->refresh()->content)->toBe($before);
    $transition->handle($event, 2, $run->content['items'][0]['key'], LiveItemOperation::Finish, signedInUser());
    app(CompleteLiveShowAction::class)->handle($event, 3, signedInUser());
    $completed = $run->refresh()->content;
    $save->handle($event, $promise->id, 5, $promise->submission_key, sponsorPromiseData($promise, ['evidence' => 'Reviewed the recording']), signedInUser(), 'Correct evidence');
    expect($run->refresh()->content)->toBe($completed);
});

test('pack edits invalidate previews without losing setup instructions on an ordinary save', function (): void {
    // Arrange
    $world = sponsorFixture();
    $event = $world['event'];
    $promise = app(SaveSponsorCommitmentAction::class)->handle(
        $event,
        $world['promise']->id,
        1,
        $world['promise']->submission_key,
        sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']),
        signedInUser(),
    );
    $query = app(SponsorInstructionService::class);
    $screen = app(EventPackViewModel::class);
    $publisher = app(SaveSponsorInstructionAction::class);
    $fingerprint = $screen->data($event)['card']['fingerprint'];
    $preview = $query->preview($event, $promise, SponsorInstructionMode::Setup, null, null);
    app(SaveEventPackAction::class)->handle($event, 0, $fingerprint, EventPackData::fromValidated($screen->data($event)['content']), false);

    // Act and Assert
    expect(fn () => $publisher->handle($event, $promise->id, 2, 0, $fingerprint, new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, $preview['hash']), signedInUser()))->toThrow(RecordChangedException::class);
    $publisher->handle($event, $promise->id, 2, 1, $fingerprint, new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, $query->preview($event, $promise, SponsorInstructionMode::Setup, null, null)['hash']), signedInUser());
    $data = $screen->data($event);
    $tasks = $data['content']['sponsor_tasks'] ?? [];
    unset($data['content']['sponsor_tasks']);
    app(SaveEventPackAction::class)->handle($event, 2, $fingerprint, EventPackData::fromValidated($data['content']), false);
    expect(($event->pack()->firstOrFail()->content['sponsor_tasks'] ?? []))->toBe($tasks);
});

test('an existing pack item cannot satisfy a second promise and retraction keeps private history', function (): void {
    // Arrange
    $world = sponsorFixture();
    $event = $world['event'];
    $save = app(SaveSponsorCommitmentAction::class);
    $promise = $save->handle($event, $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']), signedInUser());
    $query = app(SponsorInstructionService::class);
    $publisher = app(SaveSponsorInstructionAction::class);
    $screen = app(EventPackViewModel::class);
    $fingerprint = $screen->data($event)['card']['fingerprint'];
    $publisher->handle($event, $promise->id, 2, 0, $fingerprint, new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, $query->preview($event, $promise, SponsorInstructionMode::Setup, null, null)['hash']), signedInUser());
    $promise->refresh();
    $other = $save->handle($event, null, 0, (string) Str::uuid(), sponsorPromiseData($promise), signedInUser());

    // Act and Assert
    expect(fn () => $publisher->handle($event, $other->id, 1, 1, $fingerprint, new SponsorInstructionData(SponsorInstructionMode::Setup, PackKey::fromString($promise->pack_key ?? ''), null, $query->preview($event, $other, SponsorInstructionMode::Setup, $promise->pack_key, null)['hash']), signedInUser()))
        ->toThrow(SponsorCommitmentException::class);
    expect(fn () => $publisher->handle($event, $promise->id, 3, 1, $fingerprint, new SponsorInstructionData(SponsorInstructionMode::Remove, null, null, $query->preview($event, $promise, SponsorInstructionMode::Remove, null, null)['hash']), signedInUser()))
        ->toThrow(SponsorCommitmentException::class);
    $publisher->handle($event, $promise->id, 3, 1, $fingerprint, new SponsorInstructionData(SponsorInstructionMode::Remove, null, null, $query->preview($event, $promise, SponsorInstructionMode::Remove, null, null)['hash'], 'Banner withdrawn'), signedInUser());
    expect($promise->refresh()->pack_key)->toBeNull()->and($promise->revisions()->count())->toBe(4)
        ->and(($event->pack()->firstOrFail()->content['sponsor_tasks'] ?? []))->toBeEmpty();
});

test('missing wording foreign anchors and unknown destinations refuse pack publication', function (string $mode, ?int $after, bool $wording): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];

    if ($wording) {
        $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']), signedInUser());
    }

    $preview = app(SponsorInstructionService::class)->preview($world['event'], $promise, SponsorInstructionMode::from($mode), null, $after);
    $fingerprint = app(EventPackViewModel::class)->data($world['event'])['card']['fingerprint'];

    // Act and Assert
    expect(fn () => app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, $promise->version, 0, $fingerprint, new SponsorInstructionData(SponsorInstructionMode::from($mode), null, $after, $preview['hash']), signedInUser()))
        ->toThrow(SponsorCommitmentException::class)
        ->and($world['event']->pack()->exists())->toBeFalse();
})->with([['setup', null, false], ['segment', 999999, true]]);

test('a changed card needs ordinary pack review before sponsor instructions can be adopted', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']), signedInUser());
    $screen = app(EventPackViewModel::class);
    $data = $screen->data($world['event']);
    app(SaveEventPackAction::class)->handle($world['event'], 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $world['event']->update(['name' => 'Revised Harbor Showcase']);
    $fingerprint = $screen->data($world['event'])['card']['fingerprint'];
    $preview = app(SponsorInstructionService::class)->preview($world['event'], $promise, SponsorInstructionMode::Setup, null, null);

    // Act and Assert
    expect(fn () => app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 1, $fingerprint, new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, $preview['hash']), signedInUser()))
        ->toThrow(SponsorCommitmentException::class)
        ->and($world['event']->pack()->firstOrFail()->version)->toBe(1)
        ->and($promise->refresh()->version)->toBe(2);
});

test('an unstarted announcement can be deliberately retracted without removing its promise', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Sponsor thanks', 'instruction' => 'Thank Harbor Hardware']), signedInUser());
    $query = app(SponsorInstructionService::class);
    $fingerprint = app(EventPackViewModel::class)->data($world['event'])['card']['fingerprint'];
    $publisher = app(SaveSponsorInstructionAction::class);
    $publisher->handle($world['event'], $promise->id, 2, 0, $fingerprint, new SponsorInstructionData(SponsorInstructionMode::Segment, null, null, $query->preview($world['event'], $promise, SponsorInstructionMode::Segment, null, null)['hash']), signedInUser());
    $promise->refresh();

    // Act
    $publisher->handle($world['event'], $promise->id, 3, 1, $fingerprint, new SponsorInstructionData(SponsorInstructionMode::Remove, null, null, $query->preview($world['event'], $promise, SponsorInstructionMode::Remove, null, null)['hash'], 'Announcement withdrawn'), signedInUser());

    // Assert
    expect($world['event']->pack()->firstOrFail()->content['segments'])->toBeEmpty()
        ->and($promise->refresh()->status)->toBe(SponsorCommitmentStatus::Pending)->and($promise->revisions()->count())->toBe(4);
});

test('sponsor publishing cannot exceed the existing fifty segment pack limit', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Sponsor thanks', 'instruction' => 'Thank Harbor Hardware']), signedInUser());
    $data = app(EventPackViewModel::class)->data($world['event']);
    $content = $data['content'];
    $content['segments'] = array_values(array_map(fn (int $number): array => ['id' => (string) Str::uuid(), 'label' => "Segment {$number}", 'after_match_id' => null, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null], range(1, 50)));
    app(SaveEventPackAction::class)->handle($world['event'], 0, $data['card']['fingerprint'], EventPackData::fromValidated($content), false);
    $preview = app(SponsorInstructionService::class)->preview($world['event'], $promise, SponsorInstructionMode::Segment, null, null);

    // Act and Assert
    expect(fn () => app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 1, $data['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Segment, null, null, $preview['hash']), signedInUser()))
        ->toThrow(SponsorCommitmentException::class)
        ->and($world['event']->pack()->firstOrFail()->content['segments'])->toHaveCount(50);
});

test('instruction adoption locks the event card before the promise and both before writes', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang before doors']), signedInUser());
    $pack = app(EventPackViewModel::class)->data($world['event']);
    $preview = app(SponsorInstructionService::class)->preview($world['event'], $promise, SponsorInstructionMode::Setup, null, null);

    // Act
    $statements = recordStatements(fn () => app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 0, $pack['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, $preview['hash']), signedInUser()));

    // Assert
    $locks = array_map(fn (string $table): int => statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "'.$table.'"')), ['events', 'events_matches', 'event_packs', 'event_live_runs', 'event_sponsor_commitments']);
    expect($locks[0])->toBeLessThan($locks[1])->and($locks[1])->toBeLessThan($locks[2])
        ->and($locks[2])->toBeLessThan($locks[3])->and($locks[3])->toBeLessThan($locks[4])
        ->and($locks[4])->toBeLessThan(statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "event_packs"')))
        ->and($statements[$locks[1]]['sql'])->toContain('order by "events_matches"."id" asc');
});

test('instruction adoption takes each card lock once and reads the card once for its fingerprint', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang before doors']), signedInUser());
    $pack = app(EventPackViewModel::class)->data($world['event']);
    $preview = app(SponsorInstructionService::class)->preview($world['event'], $promise, SponsorInstructionMode::Setup, null, null);

    // Act
    $statements = recordStatements(fn () => app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 0, $pack['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, $preview['hash']), signedInUser()));

    // Assert
    $locks = array_values(array_filter(
        array_column($statements, 'sql'),
        fn (string $sql): bool => str_contains($sql, 'for update'),
    ));
    $cardReads = array_filter(array_column($statements, 'sql'), fn (string $sql): bool => str_starts_with($sql, 'select * from "roster_unavailabilities"'));
    expect($locks)->toBe([
        'select * from "events" where "events"."id" = ? and "events"."deleted_at" is null limit 1  /* for update */',
        'select * from "events_matches" where "event_id" = ? order by "events_matches"."id" asc  /* for update */',
        'select * from "event_packs" where "event_id" = ? limit 1  /* for update */',
        'select * from "event_live_runs" where "event_id" = ? limit 1  /* for update */',
        'select * from "event_sponsor_commitments" where "event_id" = ? and "event_sponsor_commitments"."id" = ? limit 1  /* for update */',
    ])
        ->and($cardReads)->toHaveCount(2);
});

test('instruction adoption over a saved pack writes the next version, the held fingerprint and the retained sections in stored order', function (): void {
    // Arrange
    $world = sponsorFixture();
    $event = $world['event'];
    EventMatch::factory()->for($event)->create();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($event, $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang before doors']), signedInUser());
    $screen = app(EventPackViewModel::class)->data($event);
    app(SaveEventPackAction::class)->handle($event, 0, $screen['card']['fingerprint'], EventPackData::fromValidated([...$screen['content'], 'notes' => 'Retained note', 'doors_at' => '18:30']), false);
    $stored = $event->pack()->firstOrFail();
    $stored->content = [...$stored->content, 'production' => ['notes' => 'Retained production', 'assignments' => [], 'cues' => []]];
    $stored->save();
    $preview = app(SponsorInstructionService::class)->preview($event, $promise, SponsorInstructionMode::Setup, null, null);

    // Act
    app(SaveSponsorInstructionAction::class)->handle($event, $promise->id, 2, 1, $screen['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, $preview['hash']), signedInUser());

    // Assert
    $pack = $event->pack()->firstOrFail();
    expect($pack->version)->toBe(2)
        ->and($pack->card_fingerprint)->toBe($screen['card']['fingerprint'])
        ->and(array_keys($pack->content))->toBe(['timezone', 'doors_at', 'revision', 'master', 'notes', 'follow_up', 'matches', 'segments', 'sponsor_tasks', 'production'])
        ->and($pack->content['notes'])->toBe('Retained note')
        ->and($pack->content['doors_at'])->toBe('18:30')
        ->and(data_get($pack->content, 'sponsor_tasks.0.label'))->toBe('Banner')
        ->and($pack->content['production'])->toEqual(['notes' => 'Retained production', 'assignments' => [], 'cues' => []]);
});

test('sponsor adoption and production edits preserve each others saved pack sections', function (string $mode): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $production = $event->pack()->firstOrFail()->content['production'] ?? null;
    $promise = EventSponsorCommitment::factory()->create(['event_id' => $event->id, 'promotion_id' => $event->promotion_id,
        'instruction_label' => 'Entrance banner', 'instruction' => 'Hang before doors', 'instruction_owner' => 'Ring crew']);
    $screen = app(EventPackViewModel::class)->data($event);
    $preview = app(SponsorInstructionService::class)->preview($event, $promise, SponsorInstructionMode::from($mode), null, null);

    // Act
    app(SaveSponsorInstructionAction::class)->handle($event, $promise->id, 1, 1, $screen['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::from($mode), null, null, $preview['hash']), signedInUser());

    // Assert
    $content = $event->pack()->firstOrFail()->content;
    expect($content['production'] ?? null)->toBe($production);
    $instructions = $mode === 'setup' ? ($content['sponsor_tasks'] ?? []) : $content['segments'];
    expect($instructions)->toHaveCount(1);

    // Act
    app(SaveEventProductionAction::class)->handle($event, 2, $screen['card']['fingerprint'], EventProductionData::fromValidated([...$fixture['production'], 'notes' => 'Updated private crew note']), false, administrator());

    // Assert
    $updated = $event->pack()->firstOrFail();
    expect($updated->version)->toBe(3)
        ->and($mode === 'setup' ? ($updated->content['sponsor_tasks'] ?? []) : $updated->content['segments'])->toBe($instructions)
        ->and(data_get($updated->content, 'production.notes'))->toBe('Updated private crew note');
})->with(['setup', 'segment']);

test('reviewed setup wording records the actor passed in on the promise revision', function (): void {
    // Arrange
    $promise = EventSponsorCommitment::factory()->create(['instruction_label' => 'Entrance banner', 'instruction' => 'Hang before doors', 'instruction_owner' => 'Ring crew']);
    $event = $promise->event;
    actingAs(administrator());
    $actor = administrator();
    $pack = app(EventPackViewModel::class)->data($event);
    $preview = app(SponsorInstructionService::class)->preview($event, $promise, SponsorInstructionMode::Setup, null, null);

    // Act
    app(SaveSponsorInstructionAction::class)->handle($event, $promise->id, 1, 0, $pack['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, $preview['hash']), $actor);

    // Assert
    expect($promise->revisions()->sole()->actor_id)->toBe($actor->id);
});
