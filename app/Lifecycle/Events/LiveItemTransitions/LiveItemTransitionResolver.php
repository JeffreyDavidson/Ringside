<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\LiveItemTransitions;

use App\Enums\Events\LiveItemOperation;

/** Picks the transition that applies a live-show item operation. */
final readonly class LiveItemTransitionResolver
{
    public function __construct(
        private StartLiveItem $start,
        private FinishLiveItem $finish,
        private SkipLiveItem $skip,
        private CorrectLiveItemTimes $correct,
        private ReopenLiveItem $reopen,
    ) {}

    public function resolve(LiveItemOperation $operation): LiveItemTransition
    {
        return match ($operation) {
            LiveItemOperation::Start => $this->start,
            LiveItemOperation::Finish => $this->finish,
            LiveItemOperation::Skip => $this->skip,
            LiveItemOperation::Correct => $this->correct,
            LiveItemOperation::Reopen => $this->reopen,
        };
    }
}
