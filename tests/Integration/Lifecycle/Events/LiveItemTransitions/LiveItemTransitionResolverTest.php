<?php

declare(strict_types=1);

use App\Enums\Events\LiveItemOperation;
use App\Lifecycle\Events\LiveItemTransitions\CorrectLiveItemTimes;
use App\Lifecycle\Events\LiveItemTransitions\FinishLiveItem;
use App\Lifecycle\Events\LiveItemTransitions\LiveItemTransitionResolver;
use App\Lifecycle\Events\LiveItemTransitions\ReopenLiveItem;
use App\Lifecycle\Events\LiveItemTransitions\SkipLiveItem;
use App\Lifecycle\Events\LiveItemTransitions\StartLiveItem;

test('it picks the transition for each operation', function (LiveItemOperation $operation, string $transition): void {
    // Act
    $resolved = resolve(LiveItemTransitionResolver::class)->resolve($operation);

    // Assert
    expect($resolved)->toBeInstanceOf($transition);
})->with([
    'start' => [LiveItemOperation::Start, StartLiveItem::class],
    'finish' => [LiveItemOperation::Finish, FinishLiveItem::class],
    'skip' => [LiveItemOperation::Skip, SkipLiveItem::class],
    'correct' => [LiveItemOperation::Correct, CorrectLiveItemTimes::class],
    'reopen' => [LiveItemOperation::Reopen, ReopenLiveItem::class],
]);
