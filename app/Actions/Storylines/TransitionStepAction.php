<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Enums\Storylines\StepStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;
use Illuminate\Support\Facades\DB;

class TransitionStepAction
{
    public function __construct(private readonly LockStorylineAction $lock, private readonly RecordRevisionAction $revision) {}

    public function handle(Storyline $storyline, int $version, int $stepId, StepStatus $status, string $reason): void
    {
        DB::transaction(function () use ($storyline, $version, $stepId, $status, $reason): void {
            $story = $this->lock->handle($storyline, $version);
            $step = $story->steps()->findOrFail($stepId);
            if ($step->status === StepStatus::Happened || $status === StepStatus::Happened || $step->status === $status) {
                throw StorylineConflictException::state();
            }
            $before = $step->attributesToArray();
            $step->status = $status;
            $step->version++;
            $step->save();
            $this->revision->handle($story, $step, 'step_status', $before, $reason);
        }, attempts: 3);
    }
}
