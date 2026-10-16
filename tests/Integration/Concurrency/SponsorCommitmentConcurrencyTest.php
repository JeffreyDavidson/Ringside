<?php

declare(strict_types=1);

require_once __DIR__.'/../../Fixtures/sponsor-commitment.php';

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveSponsorCommitmentAction;
use App\Actions\Events\SaveSponsorInstructionAction;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Sponsors\Sponsor;
use App\Models\Users\User;
use App\Services\Events\SponsorInstructionService;
use App\ViewModels\EventPackViewModel;
use Illuminate\Support\Str;

/**
 * @param  array{event: Event, sponsor: Sponsor, promise: EventSponsorCommitment}  $world
 * @return array{sponsor_event_id: int, sponsor_id: int, promise_id: int, user_id: int, version: int, submission_key: string, sponsor_operation: string, fingerprint: string, pack_version: int, preview_hash: string, target_key: string, mode: string, clock: string}
 */
function sponsorWorkerSpec(array $world, string $operation): array
{
    $pack = app(EventPackViewModel::class)->data($world['event']);
    $promise = $world['promise']->refresh();
    $target = $promise->pack_key;

    return ['sponsor_event_id' => $world['event']->id, 'sponsor_id' => $world['sponsor']->id, 'promise_id' => $promise->id,
        'user_id' => User::query()->firstOrFail()->id, 'version' => $promise->version, 'submission_key' => $promise->submission_key,
        'sponsor_operation' => $operation, 'fingerprint' => $pack['card']['fingerprint'], 'pack_version' => $pack['pack']->version ?? 0,
        'preview_hash' => app(SponsorInstructionService::class)->preview($world['event'], $promise, 'segment', $target, null)['hash'],
        'target_key' => $target ?? '', 'mode' => 'segment', 'clock' => now()->toIso8601String()];
}

/** @param array{event: Event, sponsor: Sponsor, promise: EventSponsorCommitment} $world */
function publishSponsorFixture(array $world): void
{
    $promise = $world['promise'];
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 1, $promise->submission_key,
        sponsorPromiseData($promise, ['instructionLabel' => 'Sponsor thanks', 'instruction' => 'Thank Harbor Hardware']));
    $pack = app(EventPackViewModel::class)->data($world['event']);
    app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 0, $pack['card']['fingerprint'], 'segment', null, null,
        app(SponsorInstructionService::class)->preview($world['event'], $promise, 'segment', null, null)['hash']);
}

test('sponsor concurrency admits one repeated create and one atomic revision', function (): void {
    withCommittedData(function (): void {
        foreach (range(1, 3) as $run) {
            // Arrange
            $world = sponsorFixture();
            $key = (string) Str::uuid();
            $spec = [...sponsorWorkerSpec($world, 'create'), 'version' => 0, 'submission_key' => $key];
            $deadlocks = resolvedDeadlocks();

            // Act
            $results = collect(bookConcurrently([$spec, $spec]));

            // Assert
            expect($results->where('ok', true))->toHaveCount(1)
                ->and($results->where('ok', false)->pluck('exception')->all())->toBe([RecordChangedException::class])
                ->and(EventSponsorCommitment::query()->where('submission_key', $key)->firstOrFail()->revisions()->count())->toBe(1)
                ->and(deadlocksResolvedSince($deadlocks))->toBe(0);
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

test('sponsor concurrency refuses a stale opposite terminal transition', function (): void {
    withCommittedData(function (): void {
        // Arrange
        $world = sponsorFixture();
        $deadlocks = resolvedDeadlocks();

        // Act
        $results = collect(bookConcurrently([sponsorWorkerSpec($world, 'complete'), sponsorWorkerSpec($world, 'cancel')]));

        // Assert
        expect($results->where('ok', true))->toHaveCount(1)
            ->and($world['promise']->refresh()->version)->toBe(2)
            ->and($world['promise']->revisions()->count())->toBe(2)
            ->and(deadlocksResolvedSince($deadlocks))->toBe(0);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

test('sponsor concurrency archives without deleting a promise that won the selection race', function (): void {
    withCommittedData(function (): void {
        // Arrange
        $world = sponsorFixture();
        $key = (string) Str::uuid();
        $deadlocks = resolvedDeadlocks();

        // Act
        $results = bookConcurrently([[...sponsorWorkerSpec($world, 'create'), 'version' => 0, 'submission_key' => $key],
            [...sponsorWorkerSpec($world, 'archive'), 'version' => 1]]);

        // Assert
        expect($results[1]['ok'])->toBeTrue()
            ->and($world['sponsor']->refresh()->archived_at)->not->toBeNull()
            ->and(EventSponsorCommitment::query()->where('submission_key', $key)->count())->toBe($results[0]['ok'] ? 1 : 0)
            ->and($world['promise']->refresh()->revisions()->count())->toBe(1)
            ->and(deadlocksResolvedSince($deadlocks))->toBe(0);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

test('sponsor concurrency never mixes a published instruction into an unreviewed prepared snapshot', function (): void {
    withCommittedData(function (): void {
        // Arrange
        $world = sponsorFixture();
        publishSponsorFixture($world);
        $deadlocks = resolvedDeadlocks();

        // Act
        $results = bookConcurrently([sponsorWorkerSpec($world, 'publish'), sponsorWorkerSpec($world, 'prepare')]);

        // Assert
        expect($results[0]['ok'])->toBeTrue()
            ->and($world['event']->pack()->firstOrFail()->version)->toBe(2)
            ->and(deadlocksResolvedSince($deadlocks))->toBe(0);
        $live = $world['event']->liveRun()->first();
        if ($live !== null) {
            expect($live->pack_version)->toBe(1)->and($live->content['items'])->toHaveCount(1);
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

test('sponsor concurrency refuses source adoption if another instruction revision wins first', function (): void {
    withCommittedData(function (): void {
        // Arrange
        $world = sponsorFixture();
        publishSponsorFixture($world);
        $first = sponsorWorkerSpec($world, 'publish');
        $run = app(PrepareLiveShowAction::class)->handle($world['event'], 0, 1, $first['fingerprint']);
        $promise = $world['promise']->refresh();
        app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 3, 1, $first['fingerprint'], 'segment', $promise->pack_key, null, $first['preview_hash']);
        $deadlocks = resolvedDeadlocks();

        // Act
        $results = bookConcurrently([sponsorWorkerSpec($world, 'publish'), [...sponsorWorkerSpec($world, 'review'), 'version' => $run->version]]);

        // Assert
        expect($results[0]['ok'])->toBeTrue()
            ->and($world['event']->pack()->firstOrFail()->version)->toBe(3)
            ->and($run->refresh()->pack_version)->toBe($results[1]['ok'] ? 2 : 1)
            ->and($run->content['items'])->toHaveCount(1)
            ->and(deadlocksResolvedSince($deadlocks))->toBe(0);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

test('sponsor concurrency admits one correction when completion and reopening share a version', function (): void {
    withCommittedData(function (): void {
        // Arrange
        $world = sponsorFixture();
        $promise = $world['promise'];
        app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Completed, 'evidence' => 'Checked before doors']));
        $deadlocks = resolvedDeadlocks();

        // Act
        $results = collect(bookConcurrently([sponsorWorkerSpec($world, 'complete'), sponsorWorkerSpec($world, 'reopen')]));

        // Assert
        expect($results->where('ok', true))->toHaveCount(1)->and($promise->refresh()->version)->toBe(3)
            ->and($promise->revisions()->count())->toBe(3)->and($promise->revisions()->where('version', 2)->firstOrFail()->snapshot['evidence_note'])->toBe('Checked before doors')
            ->and(deadlocksResolvedSince($deadlocks))->toBe(0);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

test('sponsor concurrency admits one pack revision when ordinary edits race reviewed instructions', function (): void {
    withCommittedData(function (): void {
        // Arrange
        $world = sponsorFixture();
        publishSponsorFixture($world);
        $deadlocks = resolvedDeadlocks();

        // Act
        $results = collect(bookConcurrently([sponsorWorkerSpec($world, 'publish'), sponsorWorkerSpec($world, 'pack')]));

        // Assert
        expect($results->where('ok', true))->toHaveCount(1)->and($world['event']->pack()->firstOrFail()->version)->toBe(2)
            ->and($world['promise']->refresh()->revisions()->count())->toBe($results->where('ok', true)->keys()->contains(0) ? 4 : 3)
            ->and($world['event']->pack()->firstOrFail()->content['segments'])->toHaveCount(1)
            ->and(deadlocksResolvedSince($deadlocks))->toBe(0);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');
