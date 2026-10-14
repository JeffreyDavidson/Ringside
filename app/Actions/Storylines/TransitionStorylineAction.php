<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class TransitionStorylineAction
{
    public function __construct(private readonly LockStorylineAction $lock, private readonly RecordRevisionAction $revision) {}

    public function handle(Storyline $storyline, int $version, StorylineStatus $status, string $reason): void
    {
        Gate::authorize('update', $storyline);
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:2000']])->validate();
        DB::transaction(function () use ($storyline, $version, $status, $reason): void {
            $story = $this->lock->handle($storyline, $version, allowClosed: true);
            if ($story->status === $status || ($story->status === StorylineStatus::Archived && $status !== $story->previous_status)
                || ($story->status === StorylineStatus::Completed && ! in_array($status, [StorylineStatus::Active, StorylineStatus::Archived], true))) {
                throw StorylineConflictException::state();
            }
            if ($status === StorylineStatus::Completed && $story->steps()->whereIn('status', [StepStatus::Planned, StepStatus::Postponed])->exists()) {
                throw StorylineConflictException::state();
            }
            $before = $story->attributesToArray();
            if ($status === StorylineStatus::Archived) {
                $story->previous_status = $story->status;
            }
            $story->status = $status;
            $story->save();
            $this->revision->handle($story, $story, 'story_status', $before, $reason);
        }, attempts: 3);
    }
}
