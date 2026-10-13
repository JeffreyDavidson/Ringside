<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Actions\Events\CompleteLiveShowAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Enums\Events\LiveItemOperation;
use App\Exceptions\Events\LiveShowException;

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
