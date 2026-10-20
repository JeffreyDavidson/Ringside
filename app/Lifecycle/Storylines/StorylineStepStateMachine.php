<?php

declare(strict_types=1);

namespace App\Lifecycle\Storylines;

use App\Enums\Storylines\StepStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\StorylineStep;

/**
 * The moves a storyline step may make by hand between its stored statuses.
 *
 * Happened is never set or left through a status change: recording a development confirms a step, so a step cannot be
 * marked happened (or taken back from it) here. It refuses with StorylineConflictException and never locks or writes.
 * TransitionStepAction locks the story, writes the status and records the revision.
 */
final readonly class StorylineStepStateMachine
{
    /** The allowed moves, by the status the step is in (the key is the status value). Anything not listed is refused. */
    private const array MOVES = [
        'planned' => [StepStatus::Postponed, StepStatus::Cancelled],
        'postponed' => [StepStatus::Planned, StepStatus::Cancelled],
        'cancelled' => [StepStatus::Planned, StepStatus::Postponed],
        'happened' => [],
    ];

    public function canMove(StepStatus $from, StepStatus $to): bool
    {
        return in_array($to, self::MOVES[$from->value], true);
    }

    /** @throws StorylineConflictException */
    public function ensureCanMove(StorylineStep $step, StepStatus $to): void
    {
        if (! $this->canMove($step->status, $to)) {
            throw StorylineConflictException::state();
        }
    }
}
