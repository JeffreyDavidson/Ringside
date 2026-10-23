<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Data\Storylines\DevelopmentData;
use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Exceptions\RecordChangedException;
use App\Lifecycle\Storylines\StorylineDevelopmentRequirements;
use App\Models\Events\Event;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Users\User;
use App\Services\Storylines\StorylineTimezoneService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecordDevelopmentAction
{
    public function __construct(private readonly LockSourceAction $source, private readonly LockStorylineAction $lock, private readonly RecordRevisionAction $revision, private readonly StorylineDevelopmentRequirements $requirements, private readonly StorylineTimezoneService $timezones) {}

    public function handle(Storyline $storyline, int $version, DevelopmentData $data, User $actor, ?int $developmentId = null): StorylineDevelopment
    {
        $zone = $this->timezones->forDevelopment($storyline, $developmentId);

        return DB::transaction(function () use ($storyline, $version, $data, $developmentId, $zone, $actor): StorylineDevelopment {
            $source = $this->source->handle($data->source, $storyline->promotion_id, historical: $data->reason !== null && mb_trim($data->reason) !== '');
            $story = $this->lock->handle($storyline, $version);
            $step = $data->stepId === null ? null : $story->steps()->findOrFail($data->stepId);
            $this->requirements->ensureStepCanBeConfirmed($step, $developmentId);
            $this->requirements->ensureSourceExplained($step, $source, $data->reason);

            if ($data->source->eventId !== null) {
                $event = Event::query()->withTrashed()->where('promotion_id', $story->promotion_id)->findOrFail($data->source->eventId);
                $this->requirements->ensureEventHeldOn($event, $data->occurredOn, $zone);
            }

            $development = $developmentId === null ? new StorylineDevelopment : $story->developments()->findOrFail($developmentId);
            $participants = $story->participants()->with('participant')->whereIn('id', $data->participantIds)->orderBy('id')->get();
            $cast = $this->requirements->castFor($participants, $data->participantIds, $data->occurredOn, $development->cast);
            $this->requirements->ensureStepUnchanged($development, $developmentId, $data->stepId);

            if ($developmentId === null && $story->developments()->where('submission_id', $data->submissionId)->exists()) {
                throw RecordChangedException::changed();
            }

            $before = $development->attributesToArray();
            $development->promotion_id = $story->promotion_id;
            $development->storyline_id = $story->id;
            $development->storyline_step_id = $data->stepId;
            $development->fill([
                'heading' => $data->heading,
                'description' => $data->description,
                'occurred_on' => Carbon::parse($data->occurredOn),
                'sequence' => $data->sequence,
            ]);
            $development->timezone = $developmentId === null ? $zone : $development->timezone;
            $development->event_id = $data->source->eventId;
            $development->match_id = $data->source->matchId;
            $development->segment_id = $data->source->segmentId;
            $development->source_context = $source['context'];
            $development->cast = $cast;
            $development->submission_id = $developmentId === null ? $data->submissionId : $development->submission_id;
            $development->version = $developmentId === null ? 1 : $development->version + 1;
            $development->retracted_at = null;
            $development->save();

            if ($step !== null) {
                $step->status = StepStatus::Happened;
                $step->version++;
                $step->save();
            }

            $this->revision->handle($story, $development, $developmentId === null ? StorylineRevisionOperation::DevelopmentRecorded : StorylineRevisionOperation::DevelopmentCorrected, $actor, $before, $data->reason);

            return $development;
        }, attempts: 3);
    }
}
