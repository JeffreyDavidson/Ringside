<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Exceptions\Events\EventPublicationException;
use App\Exceptions\RecordChangedException;
use App\Models\Matches\EventMatch;
use App\Services\Events\PublicEventSourceService;

// Reuse the existing engine-aware start barrier; these are genuine process races, not SQLite lock assertions.
test('racing publishers and unpublishing never release two copies of the same reviewed version', function (string $other): void {
    withCommittedData(function () use ($other): void {
        $world = publicEventFixture();
        $event = $world['event'];
        savePublicDraft($event);
        $publication = releasePublicPage($event);
        $source = app(PublicEventSourceService::class)->get($event);
        $spec = ['public_event_id' => $event->id, 'public_actor_id' => $world['actor']->id, 'public_version' => $publication->version,
            'public_fingerprint' => $source['fingerprint'], 'public_operation' => 'card', 'public_match_id' => $world['match']->id, 'public_clock' => now()->toIso8601String()];
        $deadlocks = resolvedDeadlocks();

        $results = collect(bookConcurrently([$spec, [...$spec, 'public_operation' => $other]]));

        expect($results->where('ok', true))->toHaveCount(1)->and($results->where('ok', false)->pluck('exception')->all())->toBe([RecordChangedException::class])
            ->and(deadlocksResolvedSince($deadlocks))->toBe(0)
            ->and(freshModel($publication)->version)->toBe($publication->version + 1);
    });
})->with(['card', 'unpublish'])->skip(fn (): bool => ! concurrencyTestsEnabled(), 'Requires a disposable MySQL/PostgreSQL database and RUN_CONCURRENCY_TESTS=1.')->group('concurrency', 'postgres-concurrency');

test('result and card source edits race safely with publication without releasing new information', function (string $other): void {
    withCommittedData(function () use ($other): void {
        $world = publicEventFixture();
        $event = $world['event'];
        savePublicDraft($event);
        $publication = releasePublicPage($event);
        $source = app(PublicEventSourceService::class)->get($event);
        $spec = ['public_event_id' => $event->id, 'public_actor_id' => $world['actor']->id, 'public_version' => $publication->version,
            'public_fingerprint' => $source['fingerprint'], 'public_operation' => $other === 'result' ? 'results' : 'card', 'public_match_id' => $world['match']->id, 'public_clock' => now()->toIso8601String()];
        $deadlocks = resolvedDeadlocks();

        $results = collect(bookConcurrently([$spec, [...$spec, 'public_operation' => $other]]));

        expect($results->where('deadlock', true))->toBeEmpty()->and(deadlocksResolvedSince($deadlocks))->toBe(0)
            ->and($results->where('ok', false)->pluck('exception')->all())->each->toBeIn([EventPublicationException::class, RecordChangedException::class]);
        if ($other === 'result') {
            expect(EventMatch::query()->findOrFail($world['match']->id)->match_finish)->toBe(MatchFinish::Submission)
                ->and(freshModel($publication)->results['rows'][0]['outcome'] ?? 'not released')->not->toContain('Submission');
        }
    });
})->with(['result', 'card-edit'])->skip(fn (): bool => ! concurrencyTestsEnabled(), 'Requires a disposable MySQL/PostgreSQL database and RUN_CONCURRENCY_TESTS=1.')->group('concurrency', 'postgres-concurrency');
