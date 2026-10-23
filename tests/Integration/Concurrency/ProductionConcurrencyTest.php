<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventProductionAction;
use App\Data\Events\EventProductionData;
use App\Models\Crew\CrewContact;
use App\Models\Events\EventLiveRun;
use App\ViewModels\Events\EventPackViewModel;

require_once __DIR__.'/BookingConcurrencyTest.php';
require_once __DIR__.'/../../Fixtures/production.php';

test('production crew attendance races preserve the first committed mark', function (): void {
    withCommittedData(function (): void {
        // Arrange
        $fixture = productionFixture();
        $userId = auth()->id() ?? throw new LogicException('The production fixture requires an authenticated actor.');
        $event = $fixture['event'];
        $data = resolve(EventPackViewModel::class)->data($event);
        resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], administrator());
        $spec = ['live_event_id' => $event->id, 'user_id' => $userId, 'live_operation' => 'attendance', 'version' => 1,
            'pack_version' => 1, 'fingerprint' => $data['card']['fingerprint'], 'key' => 'crew:'.$fixture['contact']->id, 'clock' => now()->toIso8601String()];
        $deadlocks = resolvedDeadlocks();

        // Act
        $results = bookConcurrently([$spec, $spec]);

        // Assert
        expect(collect($results)->where('ok', true))->toHaveCount(1, workerOutcomes($results))
            ->and(deadlocksResolvedSince($deadlocks))->toBe(0);
        $run = EventLiveRun::query()->where('event_id', $event->id)->sole();
        expect($run->version)->toBe(2)
            ->and(data_get(collect($run->content['roster'])->firstWhere('key', $spec['key']), 'arrived'))->not->toBeNull();
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

test('production save races with live preparation or review without changing an adopted snapshot', function (string $operation): void {
    withCommittedData(function () use ($operation): void {
        // Arrange
        $fixture = productionFixture();
        $userId = auth()->id() ?? throw new LogicException('The production fixture requires an authenticated actor.');
        $event = $fixture['event'];
        $data = resolve(EventPackViewModel::class)->data($event);
        $input = $fixture['production'];
        $version = 1;

        if ($operation === 'review') {
            resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], administrator());
            $input['cues'][0]['instruction'] = 'Adoptable version two';
            resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated($input), false, administrator());
            $version = 2;
        }
        $input['cues'][0]['instruction'] = 'Next plan only';
        $save = ['production_save_event_id' => $event->id, 'user_id' => $userId, 'version' => $version,
            'fingerprint' => $data['card']['fingerprint'], 'production_json' => json_encode($input, JSON_THROW_ON_ERROR)];
        $live = ['live_event_id' => $event->id, 'user_id' => $userId, 'live_operation' => $operation, 'version' => $operation === 'prepare' ? 0 : 1,
            'pack_version' => $version, 'fingerprint' => $data['card']['fingerprint'], 'key' => '', 'clock' => now()->toIso8601String()];
        $deadlocks = resolvedDeadlocks();

        // Act
        $results = bookConcurrently([$save, $live]);

        // Assert
        expect($results[0]['ok'])->toBeTrue(workerOutcomes($results))
            ->and(deadlocksResolvedSince($deadlocks))->toBe(0)
            ->and($event->pack()->firstOrFail()->version)->toBe($version + 1);
        $run = EventLiveRun::query()->where('event_id', $event->id)->first();

        if ($run !== null) {
            expect(data_get($run->content, 'items.0.cues.0.instruction'))->not->toBe('Next plan only')
                ->and($run->pack_version)->toBeLessThan($version + 1);
        }
    });
})->with(['prepare', 'review'])->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

test('production contact selection racing archive either saves a complete plan or preserves the prior one', function (): void {
    withCommittedData(function (): void {
        // Arrange
        $fixture = productionFixture();
        $userId = auth()->id() ?? throw new LogicException('The production fixture requires an authenticated actor.');
        $event = $fixture['event'];
        $data = resolve(EventPackViewModel::class)->data($event);
        $new = CrewContact::factory()->create(['promotion_id' => $event->promotion_id]);
        $input = $fixture['production'];
        $input['assignments'][] = [...$input['assignments'][0], 'id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051555', 'contact_id' => $new->id];
        $save = ['production_save_event_id' => $event->id, 'user_id' => $userId, 'version' => 1,
            'fingerprint' => $data['card']['fingerprint'], 'production_json' => json_encode($input, JSON_THROW_ON_ERROR)];
        $archive = ['crew_archive_id' => $new->id, 'user_id' => $userId, 'version' => 1];
        $deadlocks = resolvedDeadlocks();

        // Act
        $results = bookConcurrently([$save, $archive]);

        // Assert
        expect($results[1]['ok'])->toBeTrue(workerOutcomes($results))
            ->and(deadlocksResolvedSince($deadlocks))->toBe(0)
            ->and($new->refresh()->archived_at)->not->toBeNull();
        expect($event->pack()->firstOrFail()->version)->toBe($results[0]['ok'] ? 2 : 1);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');
