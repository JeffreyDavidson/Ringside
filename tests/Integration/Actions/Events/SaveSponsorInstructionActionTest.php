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
use App\Enums\Events\LiveItemOperation;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorCommitmentType;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Models\Events\EventSponsorCommitment;
use App\Queries\Events\LiveShowSourceQuery;
use App\Queries\Events\SponsorInstructionQuery;
use App\ViewModels\EventPackViewModel;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('reviewed setup wording enters the pack without private promise fields', function (): void {
    // Arrange
    $promise = EventSponsorCommitment::factory()->create(['instruction_label' => 'Entrance banner', 'instruction' => 'Hang before doors', 'instruction_owner' => 'Ring crew', 'notes' => 'PRIVATE-TERMS-7Q', 'agreement_reference' => 'PRIVATE-REF-7Q']);
    $event = $promise->event;
    actingAs(administrator());
    $pack = app(EventPackViewModel::class)->data($event);
    $preview = app(SponsorInstructionQuery::class)->preview($event, $promise, 'setup', null, null);

    // Act
    app(SaveSponsorInstructionAction::class)->handle($event, $promise->id, 1, 0, $pack['card']['fingerprint'], 'setup', null, null, $preview['hash']);

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
    $promise = $save->handle($event, $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise,
        ['type' => SponsorCommitmentType::Announcement, 'instructionLabel' => 'Opening sponsor thanks', 'instruction' => 'Thank Harbor Hardware', 'instructionOwner' => 'Announcer']));
    $screen = app(EventPackViewModel::class);
    $query = app(SponsorInstructionQuery::class);
    $publisher = app(SaveSponsorInstructionAction::class);
    $preview = $query->preview($event, $promise, 'segment', null, null);
    $publisher->handle($event, $promise->id, 2, 0, $screen->data($event)['card']['fingerprint'], 'segment', null, null, $preview['hash']);
    $promise->refresh();
    $run = app(PrepareLiveShowAction::class)->handle($event, 0, 1, $screen->data($event)['card']['fingerprint']);
    $other = $save->handle($event, null, 0, (string) Str::uuid(), sponsorPromiseData($promise, ['description' => 'Second sponsor thanks', 'instructionLabel' => 'Closing sponsor thanks']));
    $preview = $query->preview($event, $other, 'segment', null, null);

    // Act
    $publisher->handle($event, $other->id, 1, 1, $screen->data($event)['card']['fingerprint'], 'segment', null, null, $preview['hash']);

    // Assert
    expect($event->pack()->firstOrFail()->content['segments'])->toHaveCount(2)
        ->and($run->refresh()->content['items'])->toHaveCount(1)
        ->and(app(LiveShowSourceQuery::class)->changed($event, $run))->toBeTrue();
    app(ReviewLiveShowAction::class)->handle($event, 1, 2, $screen->data($event)['card']['fingerprint'], []);
    expect($run->refresh()->content['items'])->toHaveCount(2);
});

test('started announcements retain their text and private fulfillment never writes live state', function (): void {
    // Arrange
    $world = sponsorFixture();
    $event = $world['event'];
    $promise = $world['promise'];
    $save = app(SaveSponsorCommitmentAction::class);
    $promise = $save->handle($event, $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['instructionLabel' => 'Sponsor thanks', 'instruction' => 'Thank Harbor Hardware']));
    $query = app(SponsorInstructionQuery::class);
    $screen = app(EventPackViewModel::class);
    $publisher = app(SaveSponsorInstructionAction::class);
    $publisher->handle($event, $promise->id, 2, 0, $screen->data($event)['card']['fingerprint'], 'segment', null, null, $query->preview($event, $promise, 'segment', null, null)['hash']);
    $promise->refresh();
    $run = app(PrepareLiveShowAction::class)->handle($event, 0, 1, $screen->data($event)['card']['fingerprint']);
    $transition = app(TransitionLiveItemAction::class);
    $transition->handle($event, 1, $run->content['items'][0]['key'], LiveItemOperation::Start);
    $before = $run->refresh()->content;
    $promise = $save->handle($event, $promise->id, 3, $promise->submission_key, sponsorPromiseData($promise, ['instruction' => 'Revised wording']));
    $preview = $query->preview($event, $promise, 'segment', $promise->pack_key, null);

    // Act and Assert
    expect(fn () => $publisher->handle($event, $promise->id, 4, 1, $screen->data($event)['card']['fingerprint'], 'segment', $promise->pack_key, null, $preview['hash']))
        ->toThrow(SponsorCommitmentException::class);
    $promise = $save->handle($event, $promise->id, 4, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Completed, 'evidence' => 'Announcer confirmed the wording']));
    expect($run->refresh()->content)->toBe($before);
    $transition->handle($event, 2, $run->content['items'][0]['key'], LiveItemOperation::Finish);
    app(CompleteLiveShowAction::class)->handle($event, 3);
    $completed = $run->refresh()->content;
    $save->handle($event, $promise->id, 5, $promise->submission_key, sponsorPromiseData($promise, ['evidence' => 'Reviewed the recording']), 'Correct evidence');
    expect($run->refresh()->content)->toBe($completed);
});

test('pack edits invalidate previews without losing setup instructions on an ordinary save', function (): void {
    // Arrange
    $world = sponsorFixture();
    $event = $world['event'];
    $promise = app(SaveSponsorCommitmentAction::class)->handle($event, $world['promise']->id, 1, $world['promise']->submission_key,
        sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']));
    $query = app(SponsorInstructionQuery::class);
    $screen = app(EventPackViewModel::class);
    $publisher = app(SaveSponsorInstructionAction::class);
    $fingerprint = $screen->data($event)['card']['fingerprint'];
    $preview = $query->preview($event, $promise, 'setup', null, null);
    app(SaveEventPackAction::class)->handle($event, 0, $fingerprint, $screen->data($event)['content'], false);

    // Act and Assert
    expect(fn () => $publisher->handle($event, $promise->id, 2, 0, $fingerprint, 'setup', null, null, $preview['hash']))->toThrow(SponsorCommitmentException::class);
    $publisher->handle($event, $promise->id, 2, 1, $fingerprint, 'setup', null, null, $query->preview($event, $promise, 'setup', null, null)['hash']);
    $data = $screen->data($event);
    $tasks = $data['content']['sponsor_tasks'] ?? [];
    unset($data['content']['sponsor_tasks']);
    app(SaveEventPackAction::class)->handle($event, 2, $fingerprint, $data['content'], false);
    expect(($event->pack()->firstOrFail()->content['sponsor_tasks'] ?? []))->toBe($tasks);
});

test('an existing pack item cannot satisfy a second promise and retraction keeps private history', function (): void {
    // Arrange
    $world = sponsorFixture();
    $event = $world['event'];
    $save = app(SaveSponsorCommitmentAction::class);
    $promise = $save->handle($event, $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']));
    $query = app(SponsorInstructionQuery::class);
    $publisher = app(SaveSponsorInstructionAction::class);
    $screen = app(EventPackViewModel::class);
    $fingerprint = $screen->data($event)['card']['fingerprint'];
    $publisher->handle($event, $promise->id, 2, 0, $fingerprint, 'setup', null, null, $query->preview($event, $promise, 'setup', null, null)['hash']);
    $promise->refresh();
    $other = $save->handle($event, null, 0, (string) Str::uuid(), sponsorPromiseData($promise));

    // Act and Assert
    expect(fn () => $publisher->handle($event, $other->id, 1, 1, $fingerprint, 'setup', $promise->pack_key, null, $query->preview($event, $other, 'setup', $promise->pack_key, null)['hash']))
        ->toThrow(SponsorCommitmentException::class);
    expect(fn () => $publisher->handle($event, $promise->id, 3, 1, $fingerprint, 'remove', null, null, $query->preview($event, $promise, 'remove', null, null)['hash']))
        ->toThrow(SponsorCommitmentException::class);
    $publisher->handle($event, $promise->id, 3, 1, $fingerprint, 'remove', null, null, $query->preview($event, $promise, 'remove', null, null)['hash'], 'Banner withdrawn');
    expect($promise->refresh()->pack_key)->toBeNull()->and($promise->revisions()->count())->toBe(4)
        ->and(($event->pack()->firstOrFail()->content['sponsor_tasks'] ?? []))->toBeEmpty();
});

test('missing wording foreign anchors and unknown destinations refuse pack publication', function (string $mode, ?int $after, bool $wording): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    if ($wording) {
        $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']));
    }
    $preview = app(SponsorInstructionQuery::class)->preview($world['event'], $promise, $mode, null, $after);
    $fingerprint = app(EventPackViewModel::class)->data($world['event'])['card']['fingerprint'];

    // Act and Assert
    expect(fn () => app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, $promise->version, 0, $fingerprint, $mode, null, $after, $preview['hash']))
        ->toThrow(SponsorCommitmentException::class)
        ->and($world['event']->pack()->exists())->toBeFalse();
})->with([['setup', null, false], ['unknown', null, true], ['segment', 999999, true]]);

test('a changed card needs ordinary pack review before sponsor instructions can be adopted', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']));
    $screen = app(EventPackViewModel::class);
    $data = $screen->data($world['event']);
    app(SaveEventPackAction::class)->handle($world['event'], 0, $data['card']['fingerprint'], $data['content'], false);
    $world['event']->update(['name' => 'Revised Harbor Showcase']);
    $fingerprint = $screen->data($world['event'])['card']['fingerprint'];
    $preview = app(SponsorInstructionQuery::class)->preview($world['event'], $promise, 'setup', null, null);

    // Act and Assert
    expect(fn () => app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 1, $fingerprint, 'setup', null, null, $preview['hash']))
        ->toThrow(SponsorCommitmentException::class)
        ->and($world['event']->pack()->firstOrFail()->version)->toBe(1)
        ->and($promise->refresh()->version)->toBe(2);
});

test('an unstarted announcement can be deliberately retracted without removing its promise', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Sponsor thanks', 'instruction' => 'Thank Harbor Hardware']));
    $query = app(SponsorInstructionQuery::class);
    $fingerprint = app(EventPackViewModel::class)->data($world['event'])['card']['fingerprint'];
    $publisher = app(SaveSponsorInstructionAction::class);
    $publisher->handle($world['event'], $promise->id, 2, 0, $fingerprint, 'segment', null, null, $query->preview($world['event'], $promise, 'segment', null, null)['hash']);
    $promise->refresh();

    // Act
    $publisher->handle($world['event'], $promise->id, 3, 1, $fingerprint, 'remove', null, null, $query->preview($world['event'], $promise, 'remove', null, null)['hash'], 'Announcement withdrawn');

    // Assert
    expect($world['event']->pack()->firstOrFail()->content['segments'])->toBeEmpty()
        ->and($promise->refresh()->status)->toBe(SponsorCommitmentStatus::Pending)->and($promise->revisions()->count())->toBe(4);
});

test('sponsor publishing cannot exceed the existing fifty segment pack limit', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Sponsor thanks', 'instruction' => 'Thank Harbor Hardware']));
    $data = app(EventPackViewModel::class)->data($world['event']);
    $content = $data['content'];
    $content['segments'] = array_values(array_map(fn (int $number): array => ['id' => (string) Str::uuid(), 'label' => "Segment {$number}", 'after_match_id' => null, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null], range(1, 50)));
    app(SaveEventPackAction::class)->handle($world['event'], 0, $data['card']['fingerprint'], $content, false);
    $preview = app(SponsorInstructionQuery::class)->preview($world['event'], $promise, 'segment', null, null);

    // Act and Assert
    expect(fn () => app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 1, $data['card']['fingerprint'], 'segment', null, null, $preview['hash']))
        ->toThrow(SponsorCommitmentException::class)
        ->and($world['event']->pack()->firstOrFail()->content['segments'])->toHaveCount(50);
});

test('instruction adoption keeps the event match promise pack and run lock order before writes', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang before doors']));
    $pack = app(EventPackViewModel::class)->data($world['event']);
    $preview = app(SponsorInstructionQuery::class)->preview($world['event'], $promise, 'setup', null, null);

    // Act
    $statements = recordStatements(fn () => app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 0, $pack['card']['fingerprint'], 'setup', null, null, $preview['hash']));

    // Assert
    $locks = array_map(fn (string $table): int => statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "'.$table.'"')), ['events', 'events_matches', 'event_sponsor_commitments', 'event_packs', 'event_live_runs']);
    expect($locks[0])->toBeLessThan($locks[1])->and($locks[1])->toBeLessThan($locks[2])
        ->and($locks[2])->toBeLessThan($locks[3])->and($locks[3])->toBeLessThan($locks[4])
        ->and($locks[4])->toBeLessThan(statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "event_packs"')))
        ->and($statements[$locks[1]]['sql'])->toContain('order by "id" asc');
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
    $preview = app(SponsorInstructionQuery::class)->preview($event, $promise, $mode, null, null);

    // Act
    app(SaveSponsorInstructionAction::class)->handle($event, $promise->id, 1, 1, $screen['card']['fingerprint'], $mode, null, null, $preview['hash']);

    // Assert
    $content = $event->pack()->firstOrFail()->content;
    expect($content['production'] ?? null)->toBe($production);
    $instructions = $mode === 'setup' ? ($content['sponsor_tasks'] ?? []) : $content['segments'];
    expect($instructions)->toHaveCount(1);

    // Act
    app(SaveEventProductionAction::class)->handle($event, 2, $screen['card']['fingerprint'], [...$fixture['production'], 'notes' => 'Updated private crew note'], false);

    // Assert
    $updated = $event->pack()->firstOrFail();
    expect($updated->version)->toBe(3)
        ->and($mode === 'setup' ? ($updated->content['sponsor_tasks'] ?? []) : $updated->content['segments'])->toBe($instructions)
        ->and(data_get($updated->content, 'production.notes'))->toBe('Updated private crew note');
})->with(['setup', 'segment']);
