<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/sponsor-commitment.php';

use App\Actions\Events\SaveSponsorCommitmentAction;
use App\Actions\Sponsors\SaveSponsorAction;
use App\Data\Events\SponsorCommitmentData;
use App\Data\Sponsors\SponsorData;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorCommitmentType;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('an event promise records its own version and private history', function (): void {
    // Arrange
    $event = Event::factory()->for(Promotion::factory())->create();
    $sponsor = Sponsor::factory()->create(['promotion_id' => $event->promotion_id, 'name' => 'Harbor Hardware']);
    $user = administrator();
    $user->update(['first_name' => 'Morgan', 'last_name' => 'Ellis']);
    actingAs($user);

    // Act
    $promise = app(SaveSponsorCommitmentAction::class)->handle($event, null, 0, (string) Str::uuid(),
        new SponsorCommitmentData($sponsor->id, SponsorCommitmentType::Banner, 'Hang the entrance banner'));

    // Assert
    expect($promise->description)->toBe('Hang the entrance banner')
        ->and($promise->sponsor_name)->toBe('Harbor Hardware')
        ->and($promise->version)->toBe(1)
        ->and($promise->revisions()->count())->toBe(1)
        ->and($promise->revisions()->firstOrFail()->actor_name)->toBe('Morgan Ellis')
        ->and($promise->revisions()->firstOrFail()->snapshot['description'])->toBe('Hang the entrance banner');
});

test('completion and reopening retain the original evidence without certifying another event', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $other = Event::factory()->create(['promotion_id' => $promise->promotion_id, 'name' => 'Harbor Encore']);
    $action = app(SaveSponsorCommitmentAction::class);
    $next = $action->handle($other, null, 0, (string) Str::uuid(), sponsorPromiseData($promise));

    // Act
    $action->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, [
        'status' => SponsorCommitmentStatus::Completed, 'evidence' => 'Photo checked at doors', 'evidenceUrl' => 'https://example.test/banner']));
    $completed = $promise->refresh();
    $action->handle($world['event'], $promise->id, 2, $promise->submission_key, sponsorPromiseData($completed, ['status' => SponsorCommitmentStatus::Pending]), 'Banner was removed early');

    // Assert
    expect($promise->refresh()->status)->toBe(SponsorCommitmentStatus::Pending)
        ->and($promise->completed_at)->toBeNull()->and($promise->evidence_note)->toBeNull()
        ->and($promise->revisions()->orderBy('version')->pluck('version')->all())->toBe([1, 2, 3])
        ->and($promise->revisions()->where('version', 2)->firstOrFail()->snapshot['evidence_note'])->toBe('Photo checked at doors')
        ->and($next->refresh()->status)->toBe(SponsorCommitmentStatus::Pending);
});

test('stale tabs repeated creates and invalid submission keys cannot add promise history', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $action = app(SaveSponsorCommitmentAction::class);
    $stale = clone $promise;
    $action->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['description' => 'Entrance and exit banner']));

    // Act and Assert
    expect(fn () => $action->handle($world['event'], $stale->id, 1, $stale->submission_key, sponsorPromiseData($stale)))
        ->toThrow(SponsorCommitmentException::class);
    expect(fn () => $action->handle($world['event'], null, 0, $promise->submission_key, sponsorPromiseData($promise)))
        ->toThrow(SponsorCommitmentException::class)
        ->and(fn () => $action->handle($world['event'], null, 0, 'invalid', sponsorPromiseData($promise)))->toThrow(SponsorCommitmentException::class)
        ->and($promise->refresh()->description)->toBe('Entrance and exit banner')
        ->and($promise->revisions()->count())->toBe(2)
        ->and(EventSponsorCommitment::query()->count())->toBe(1);
});

test('terminal promises require a reason and completed promises require real evidence', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $action = app(SaveSponsorCommitmentAction::class);

    // Act and Assert
    expect(fn () => $action->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Cancelled])))
        ->toThrow(SponsorCommitmentException::class);
    expect(fn () => $action->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Completed, 'evidence' => '  '])))
        ->toThrow(SponsorCommitmentException::class)
        ->and(fn () => $action->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Completed, 'evidence' => 'Checked', 'completedAt' => now()->addMinute()])))->toThrow(SponsorCommitmentException::class);
    $action->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Cancelled]), 'Sponsor withdrew');
    expect(fn () => $action->handle($world['event'], $promise->id, 2, $promise->submission_key, sponsorPromiseData($promise->refresh(), ['status' => SponsorCommitmentStatus::Pending])))
        ->toThrow(SponsorCommitmentException::class)
        ->and($promise->revisions()->count())->toBe(2);
});

test('event rescheduling and archiving retain evidence and require deliberate context review', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $action = app(SaveSponsorCommitmentAction::class);
    $action->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['deadline' => now()->subHour(), 'evidence' => 'Posted before change', 'status' => SponsorCommitmentStatus::Completed]));
    $original = $promise->refresh()->event_context;
    $world['event']->update(['date' => now()->addDay()]);
    $world['event']->delete();

    // Act
    $action->handle($world['event'], $promise->id, 2, $promise->submission_key, sponsorPromiseData($promise), 'Review archived event', true);

    // Assert
    expect($promise->refresh()->event_context)->not->toBe($original)
        ->and($promise->event_context['archived'])->toBeTrue()
        ->and($promise->status)->toBe(SponsorCommitmentStatus::Completed)
        ->and($promise->deadline_at?->toIso8601String())->toBe(now()->subHour()->toIso8601String())
        ->and($promise->evidence_note)->toBe('Posted before change');
    expect(fn () => $action->handle($world['event'], null, 0, (string) Str::uuid(), sponsorPromiseData($promise), 'New archived promise'))
        ->toThrow(SponsorCommitmentException::class);
});

test('archived sponsors preserve existing promises but cannot be newly selected', function (): void {
    // Arrange
    $world = sponsorFixture();
    app(SaveSponsorAction::class)->handle(Promotion::query()->findOrFail($world['sponsor']->promotion_id), $world['sponsor']->id, 1, $world['sponsor']->submission_key, new SponsorData('Renamed Harbor', archived: true));
    $promise = $world['promise'];
    $action = app(SaveSponsorCommitmentAction::class);

    // Act
    $action->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['notes' => 'Historical correction']));

    // Assert
    expect($promise->refresh()->sponsor_name)->toBe('Harbor Hardware');
    expect(fn () => $action->handle($world['event'], null, 0, (string) Str::uuid(), sponsorPromiseData($promise)))
        ->toThrow(SponsorCommitmentException::class);
    $replacement = Sponsor::factory()->create(['promotion_id' => $promise->promotion_id, 'name' => 'New sponsor']);
    $action->handle($world['event'], $promise->id, 2, $promise->submission_key, sponsorPromiseData($promise, ['sponsorId' => $replacement->id]));
    expect($promise->refresh()->sponsor_name)->toBe('New sponsor');
});

test('promise histories reject edits deletions and permanent loss of their parent', function (): void {
    // Arrange
    $world = sponsorFixture();
    $revision = $world['promise']->revisions()->firstOrFail();

    // Act and Assert
    $revision->reason = 'Rewrite';
    expect(fn () => $revision->save())->toThrow(LogicException::class)
        ->and(fn () => $revision->delete())->toThrow(LogicException::class)
        ->and(fn () => DB::transaction(fn () => $world['event']->forceDelete()))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => $world['sponsor']->delete()))->toThrow(QueryException::class)
        ->and($revision->commitment()->firstOrFail()->id)->toBe($world['promise']->id);
});

test('unowned events cannot receive private sponsor promises', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create();
    $sponsor = Sponsor::factory()->create();

    // Act and Assert
    expect(fn () => app(SaveSponsorCommitmentAction::class)->handle($event, null, 0, (string) Str::uuid(), new SponsorCommitmentData($sponsor->id, SponsorCommitmentType::Banner, 'Unowned promise')))
        ->toThrow(SponsorCommitmentException::class)
        ->and(EventSponsorCommitment::query()->count())->toBe(0);
});

test('promise corrections lock event sponsor and promise before their atomic writes', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];

    // Act
    $statements = recordStatements(fn () => app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['notes' => 'Corrected note'])));

    // Assert
    $event = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "events"'));
    $sponsor = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "sponsors"'));
    $commitment = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "event_sponsor_commitments"'));
    $write = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'update "event_sponsor_commitments"'));
    expect($event)->toBeLessThan($sponsor)->and($sponsor)->toBeLessThan($commitment)->and($commitment)->toBeLessThan($write)
        ->and($statements[$event]['bindings'])->toBe([$world['event']->id])
        ->and($promise->refresh()->revisions()->count())->toBe(2);
});
