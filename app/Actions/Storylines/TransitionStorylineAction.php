<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Enums\Storylines\StorylineStatus;
use App\Lifecycle\Storylines\StorylineStateMachine;
use App\Models\Storylines\Storyline;
use Illuminate\Support\Facades\DB;

class TransitionStorylineAction
{
    public function __construct(private readonly LockStorylineAction $lock, private readonly RecordRevisionAction $revision, private readonly StorylineStateMachine $states) {}

    public function handle(Storyline $storyline, int $version, StorylineStatus $status, string $reason): void
    {
        DB::transaction(function () use ($storyline, $version, $status, $reason): void {
            $story = $this->lock->handle($storyline, $version, allowClosed: true);
            $this->states->ensureCanMove($story, $status, $story->steps()->whereIn('status', [StepStatus::Planned, StepStatus::Postponed])->exists());
            $before = $story->attributesToArray();
            if ($status === StorylineStatus::Archived) {
                $story->previous_status = $story->status;
            }
            $story->status = $status;
            $story->save();
            $this->revision->handle($story, $story, StorylineRevisionOperation::StoryStatus, $before, $reason);
        }, attempts: 3);
    }
}
