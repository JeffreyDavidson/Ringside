<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Data\Events\EventPackData;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Exceptions\RecordChangedException;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\travel;

test('repeated starts and unknown keys preserve the committed actual start and audit', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $action = resolve(TransitionLiveItemAction::class);
    $action->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    $before = $fixture['run']->refresh()->content;
    $audits = Activity::query()->where('log_name', 'live-show')->count();

    // Act / Assert
    expect(fn () => $action->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start))->toThrow(RecordChangedException::class);
    expect(fn () => $action->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Start))->toThrow(LiveShowException::class)
        ->and(fn () => $action->handle($fixture['event'], 2, 'segment:foreign', LiveItemOperation::Finish))->toThrow(LiveShowException::class)
        ->and($fixture['run']->refresh()->content)->toBe($before)
        ->and(Activity::query()->where('log_name', 'live-show')->count())->toBe($audits);
});

test('pending and completed item transitions reject invalid state and missing reasons', function (LiveItemOperation $operation, ?string $reason): void {
    // Arrange
    $fixture = liveShowFixture();

    // Act / Assert
    expect(fn () => resolve(TransitionLiveItemAction::class)->handle($fixture['event'], 1, $fixture['key'], $operation, $reason))->toThrow(LiveShowException::class);
    expect($fixture['run']->refresh()->version)->toBe(1);
})->with([
    'finish pending' => [LiveItemOperation::Finish, null],
    'skip without reason' => [LiveItemOperation::Skip, null],
    'whitespace reason' => [LiveItemOperation::Skip, '   '],
    'correct pending' => [LiveItemOperation::Correct, 'Wrong tap'],
    'reopen pending' => [LiveItemOperation::Reopen, 'Wrong tap'],
]);

test('a started show may continue across midnight', function (): void {
    // Arrange
    $fixture = liveShowFixture(segment: true);
    $action = resolve(TransitionLiveItemAction::class);
    $action->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    travel(1)->days();

    // Act
    $action->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Finish);
    $action->handle($fixture['event'], 3, $fixture['run']->content['items'][1]['key'], LiveItemOperation::Start);

    // Assert
    expect($fixture['run']->refresh()->content['items'][1]['state'])->toBe('running');
});

test('correcting actual times records a reason and reopening retains the original start', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $action = resolve(TransitionLiveItemAction::class);
    $action->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    travel(10)->minutes();
    $action->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Finish);
    $start = now()->subMinutes(8);
    $finish = now()->subMinute();

    // Act
    $action->handle($fixture['event'], 3, $fixture['key'], LiveItemOperation::Correct, 'Correct the missed bell', $start, $finish);
    $action->handle($fixture['event'], 4, $fixture['key'], LiveItemOperation::Reopen, 'Finished too soon');

    // Assert
    $item = $fixture['run']->refresh()->content['items'][0];
    expect($item['start'])->toBe($start->utc()->toIso8601String())->and($item['finish'])->toBeNull()->and($item['state'])->toBe('running');
});

test('time corrections reject future missing and reversed actual times', function (string $case): void {
    // Arrange
    $fixture = liveShowFixture();
    $action = resolve(TransitionLiveItemAction::class);
    $action->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    travel(10)->minutes();
    $action->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Finish);
    $start = match ($case) {
        'missing start' => null, 'future' => now()->addMinute(), default => now()->subMinute()
    };
    $finish = match ($case) {
        'missing finish' => null, 'reverse' => now()->subMinutes(2), default => now()
    };

    // Act / Assert
    expect(fn () => $action->handle($fixture['event'], 3, $fixture['key'], LiveItemOperation::Correct, 'Correction', $start, $finish))->toThrow(LiveShowException::class);
    expect($fixture['run']->refresh()->version)->toBe(3);
})->with(['missing start', 'missing finish', 'future', 'reverse']);

test('a changed pack blocks the next start but does not block finishing the current item', function (): void {
    // Arrange
    $fixture = liveShowFixture(segment: true);
    $action = resolve(TransitionLiveItemAction::class);
    $action->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    $pack = $fixture['event']->pack()->firstOrFail();
    resolve(SaveEventPackAction::class)->handle($fixture['event'], 1, $pack->card_fingerprint, EventPackData::fromValidated([...$pack->content, 'notes' => 'Changed']), false);

    // Act
    $action->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Finish);

    // Assert
    expect(fn () => $action->handle($fixture['event'], 3, $fixture['run']->content['items'][1]['key'], LiveItemOperation::Start))->toThrow(LiveShowException::class);
    expect($fixture['run']->refresh()->content['items'][0]['state'])->toBe('finished');
});

test('a running item cannot be skipped instead of finished', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $action = resolve(TransitionLiveItemAction::class);
    $action->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    $before = $fixture['run']->refresh()->content;

    // Act / Assert
    expect(fn () => $action->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Skip, 'Wrong item'))->toThrow(LiveShowException::class, __('live-show.transition_invalid'));
    expect($fixture['run']->refresh()->version)->toBe(2)->and($fixture['run']->content)->toBe($before);
});

test('corrected times cannot overlap an earlier or later recorded item', function (bool $earlier): void {
    // Arrange
    $fixture = liveShowFixture(segment: true);
    $action = resolve(TransitionLiveItemAction::class);
    $firstStart = now();
    $second = $fixture['run']->content['items'][1]['key'];
    $action->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    travel(10)->minutes();
    $action->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Finish);
    $action->handle($fixture['event'], 3, $second, LiveItemOperation::Start);
    travel(10)->minutes();
    $action->handle($fixture['event'], 4, $second, LiveItemOperation::Finish);
    $before = $fixture['run']->refresh()->content;
    $key = $earlier ? $second : $fixture['key'];
    $start = $earlier ? now()->subMinutes(11) : $firstStart;
    $finish = $earlier ? now() : now()->subMinutes(9);

    // Act / Assert
    expect(fn () => $action->handle($fixture['event'], 5, $key, LiveItemOperation::Correct, 'Correct bell time', $start, $finish))->toThrow(LiveShowException::class, __('live-show.invalid_times'));
    expect($fixture['run']->refresh()->version)->toBe(5)->and($fixture['run']->content)->toBe($before);
})->with(['overlaps previous finish' => true, 'overlaps next start' => false]);

test('starting the first item moves the show from prepared to running and finishing leaves it running', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $action = resolve(TransitionLiveItemAction::class);
    $preparedStatus = $fixture['run']->status;

    // Act
    $action->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    $runningStatus = $fixture['run']->refresh()->status;
    $action->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Finish);

    // Assert
    expect($preparedStatus)->toBe(LiveShowStatus::Prepared)
        ->and($runningStatus)->toBe(LiveShowStatus::Running)
        ->and($fixture['run']->refresh()->status)->toBe(LiveShowStatus::Running);
});

test('an item transition records the actor passed in, not the signed-in user', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $actor = administrator();

    // Act
    resolve(TransitionLiveItemAction::class)->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start, $actor);

    // Assert
    expect($fixture['run']->refresh()->content['items'][0]['updated_by'])->toBe($actor->id);
});
