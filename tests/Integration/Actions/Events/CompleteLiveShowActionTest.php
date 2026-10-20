<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Actions\Events\CompleteLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Data\Events\EventPackData;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use Spatie\Activitylog\Models\Activity;

test('pending or running items prevent completion and a completed show is read-only', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $complete = resolve(CompleteLiveShowAction::class);
    $item = resolve(TransitionLiveItemAction::class);

    // Act / Assert
    expect(fn () => $complete->handle($fixture['event'], 1))->toThrow(LiveShowException::class);
    $item->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    expect(fn () => $complete->handle($fixture['event'], 2))->toThrow(LiveShowException::class);
    $item->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Finish);
    $complete->handle($fixture['event'], 3);
    expect(fn () => $item->handle($fixture['event'], 4, $fixture['key'], LiveItemOperation::Reopen, 'Oops'))->toThrow(LiveShowException::class)
        ->and($fixture['run']->refresh()->version)->toBe(4);
});

test('a changed source prevents completion without writing an audit or completion time', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $item = resolve(TransitionLiveItemAction::class);
    $item->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    $item->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Finish);
    $pack = $fixture['event']->pack()->firstOrFail();
    resolve(SaveEventPackAction::class)->handle($fixture['event'], 1, $pack->card_fingerprint, EventPackData::fromValidated([...$pack->content, 'notes' => 'Changed before completion']), false);
    $audits = Activity::query()->where('log_name', 'live-show')->count();

    // Act / Assert
    expect(fn () => resolve(CompleteLiveShowAction::class)->handle($fixture['event'], 3))->toThrow(LiveShowException::class, __('live-show.review_required'));
    expect($fixture['run']->refresh()->version)->toBe(3)->and($fixture['run']->completed_at)->toBeNull()
        ->and(Activity::query()->where('log_name', 'live-show')->count())->toBe($audits);
});

test('a prepared show with a pending item is refused as unfinished and stays prepared', function (): void {
    // Arrange
    $fixture = liveShowFixture();

    // Act
    $complete = fn () => resolve(CompleteLiveShowAction::class)->handle($fixture['event'], 1);

    // Assert
    expect($complete)->toThrow(LiveShowException::class, __('live-show.unfinished'))
        ->and($fixture['run']->refresh()->status)->toBe(LiveShowStatus::Prepared)
        ->and($fixture['run']->completed_at)->toBeNull();
});

test('a running show with a running item is refused as unfinished and stays running', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    resolve(TransitionLiveItemAction::class)->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);

    // Act
    $complete = fn () => resolve(CompleteLiveShowAction::class)->handle($fixture['event'], 2);

    // Assert
    expect($complete)->toThrow(LiveShowException::class, __('live-show.unfinished'))
        ->and($fixture['run']->refresh()->status)->toBe(LiveShowStatus::Running);
});

test('a running show with every item finished completes', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $item = resolve(TransitionLiveItemAction::class);
    $item->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    $item->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Finish);

    // Act
    resolve(CompleteLiveShowAction::class)->handle($fixture['event'], 3);

    // Assert
    expect($fixture['run']->refresh()->status)->toBe(LiveShowStatus::Completed)
        ->and($fixture['run']->completed_at)->not->toBeNull();
});

test('a completed show refuses to complete again and to start an item with the completed message', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $item = resolve(TransitionLiveItemAction::class);
    $item->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    $item->handle($fixture['event'], 2, $fixture['key'], LiveItemOperation::Finish);
    resolve(CompleteLiveShowAction::class)->handle($fixture['event'], 3);

    // Act
    $completeAgain = fn () => resolve(CompleteLiveShowAction::class)->handle($fixture['event'], 4);
    $startAgain = fn () => $item->handle($fixture['event'], 4, $fixture['key'], LiveItemOperation::Start);

    // Assert
    expect($completeAgain)->toThrow(LiveShowException::class, __('live-show.completed'))
        ->and($startAgain)->toThrow(LiveShowException::class, __('live-show.completed'))
        ->and($fixture['run']->refresh()->status)->toBe(LiveShowStatus::Completed);
});
