<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Data\Storylines\StepData;
use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineStep;
use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaveStepAction
{
    public function __construct(private readonly LockSourceAction $source, private readonly LockStorylineAction $lock, private readonly RecordRevisionAction $revision) {}

    public function handle(Storyline $storyline, int $version, StepData $data, User $actor, ?int $stepId = null): StorylineStep
    {
        return DB::transaction(function () use ($storyline, $version, $data, $stepId, $actor): StorylineStep {
            $source = $this->source->handle($data->source, $storyline->promotion_id);
            $story = $this->lock->handle($storyline, $version);
            $step = $stepId === null ? new StorylineStep : $story->steps()->findOrFail($stepId);
            if ($stepId !== null && $step->status === StepStatus::Happened) {
                throw StorylineConflictException::state();
            }
            if ($stepId === null && $story->steps()->where('submission_id', $data->submissionId)->exists()) {
                throw RecordChangedException::changed();
            }
            $before = $step->attributesToArray();
            $step->promotion_id = $story->promotion_id;
            $step->storyline_id = $story->id;
            $step->event_id = $data->source->eventId;
            $step->match_id = $data->source->matchId;
            $step->segment_id = $data->source->segmentId;
            $previousTarget = $step->target_on?->toDateString();
            $step->fill([
                'heading' => $data->heading,
                'description' => $data->description,
                'sequence' => $data->sequence,
                'target_on' => $data->source->targetOn === null ? null : Carbon::parse($data->source->targetOn),
            ]);
            $step->timezone = $stepId === null || $previousTarget !== $data->source->targetOn ? Promotion::query()->findOrFail($story->promotion_id)->timezone : $step->timezone;
            $step->target_at = $data->source->targetOn === null ? null : Carbon::parse($data->source->targetOn, $step->timezone)->utc();
            $step->source_context = $source['context'];
            $step->submission_id = $stepId === null ? $data->submissionId : $step->submission_id;
            $step->version = $stepId === null ? 1 : $step->version + 1;
            $step->save();
            $this->revision->handle($story, $step, $stepId === null ? StorylineRevisionOperation::StepPlanned : StorylineRevisionOperation::StepRevised, $actor, $before, $data->reason);

            return $step->refresh();
        }, attempts: 3);
    }
}
