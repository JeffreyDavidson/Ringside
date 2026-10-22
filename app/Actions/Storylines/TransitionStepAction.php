<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Lifecycle\Storylines\StorylineStepStateMachine;
use App\Models\Storylines\Storyline;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class TransitionStepAction
{
    public function __construct(private readonly LockStorylineAction $lock, private readonly RecordRevisionAction $revision, private readonly StorylineStepStateMachine $states) {}

    public function handle(Storyline $storyline, int $version, int $stepId, StepStatus $status, string $reason, User $actor): void
    {
        DB::transaction(function () use ($storyline, $version, $stepId, $status, $reason, $actor): void {
            $story = $this->lock->handle($storyline, $version);
            $step = $story->steps()->findOrFail($stepId);
            $this->states->ensureCanMove($step, $status);
            $before = $step->attributesToArray();
            $step->status = $status;
            $step->version++;
            $step->save();
            $this->revision->handle($story, $step, StorylineRevisionOperation::StepStatus, $actor, $before, $reason);
        }, attempts: 3);
    }
}
