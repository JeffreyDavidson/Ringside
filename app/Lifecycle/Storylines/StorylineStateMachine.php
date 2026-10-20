<?php

declare(strict_types=1);

namespace App\Lifecycle\Storylines;

use App\Enums\Storylines\StorylineStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;

/**
 * The moves a storyline may make between its four stored statuses.
 *
 * It reads the storyline and refuses with StorylineConflictException; it never locks or writes. TransitionStorylineAction
 * locks the story, writes the new status (and remembers the old one when archiving) and records the revision.
 */
final readonly class StorylineStateMachine
{
    /**
     * The allowed moves, by the status the story is in (the key is the status value). Anything not listed is refused.
     * An archived story may be listed as moving to any other status here, but ensureCanMove() narrows it to the one it was archived from.
     */
    private const array MOVES = [
        'active' => [StorylineStatus::Paused, StorylineStatus::Completed, StorylineStatus::Archived],
        'paused' => [StorylineStatus::Active, StorylineStatus::Completed, StorylineStatus::Archived],
        'completed' => [StorylineStatus::Active, StorylineStatus::Archived],
        'archived' => [StorylineStatus::Active, StorylineStatus::Paused, StorylineStatus::Completed],
    ];

    public function canMove(StorylineStatus $from, StorylineStatus $to): bool
    {
        return in_array($to, self::MOVES[$from->value], true);
    }

    /**
     * Refuse a move the table does not allow, an archived story going anywhere but back to the status it was archived from,
     * and completing a story that still has planned or postponed steps.
     *
     * @throws StorylineConflictException
     */
    public function ensureCanMove(Storyline $story, StorylineStatus $to, bool $hasOpenSteps): void
    {
        if (! $this->canMove($story->status, $to)) {
            throw StorylineConflictException::state();
        }

        if ($story->status === StorylineStatus::Archived && $to !== $story->previous_status) {
            throw StorylineConflictException::state();
        }

        if ($to === StorylineStatus::Completed && $hasOpenSteps) {
            throw StorylineConflictException::state();
        }
    }

    /**
     * Refuse any edit to a story that is completed or archived.
     *
     * @throws StorylineConflictException
     */
    public function ensureOpen(Storyline $story): void
    {
        if (! in_array($story->status, [StorylineStatus::Active, StorylineStatus::Paused], true)) {
            throw StorylineConflictException::state();
        }
    }
}
