<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class RetractDevelopmentAction
{
    public function __construct(private readonly LockStorylineAction $lock, private readonly RecordRevisionAction $revision) {}

    public function handle(Storyline $storyline, int $version, int $developmentId, string $reason, bool $reopenStep, User $actor): void
    {
        DB::transaction(function () use ($storyline, $version, $developmentId, $reason, $reopenStep, $actor): void {
            $story = $this->lock->handle($storyline, $version);
            $development = $story->developments()->findOrFail($developmentId);

            if ($development->retracted_at !== null) {
                throw StorylineConflictException::state();
            }

            $before = $development->attributesToArray();
            $development->retracted_at = now();
            $development->version++;
            $development->save();

            if ($development->storyline_step_id !== null) {
                $step = $story->steps()->findOrFail($development->storyline_step_id);
                $step->status = $reopenStep ? StepStatus::Planned : StepStatus::Cancelled;
                $step->version++;
                $step->save();
            }

            $this->revision->handle($story, $development, $reopenStep ? StorylineRevisionOperation::DevelopmentRetractedReopen : StorylineRevisionOperation::DevelopmentRetracted, $actor, $before, $reason);
        }, attempts: 3);
    }
}
