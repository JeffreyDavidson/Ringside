<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Data\Storylines\DevelopmentData;
use App\Enums\Storylines\StepStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class RecordDevelopmentAction
{
    public function __construct(private readonly LockSourceAction $source, private readonly LockStorylineAction $lock, private readonly RecordRevisionAction $revision) {}

    public function handle(Storyline $storyline, int $version, DevelopmentData $data, ?int $developmentId = null): StorylineDevelopment
    {
        Gate::authorize('update', $storyline);
        $zone = $developmentId === null ? Promotion::query()->findOrFail($storyline->promotion_id)->timezone : $storyline->developments()->findOrFail($developmentId)->timezone;
        Validator::make(['heading' => $data->heading, 'description' => $data->description, 'occurred' => $data->occurredOn,
            'sequence' => $data->sequence, 'participants' => $data->participantIds, 'submission' => $data->submissionId, 'reason' => $data->reason], [
                'heading' => ['required', 'string', 'max:160'], 'description' => ['required', 'string', 'max:4000'],
                'occurred' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now()->setTimezone($zone)->toDateString()],
                'sequence' => ['required', 'integer', 'min:1', 'max:10000'], 'participants' => ['array', 'list', 'max:100'],
                'participants.*' => ['integer', 'distinct'], 'submission' => ['required', 'uuid'],
                'reason' => [$developmentId === null ? 'nullable' : 'required', 'string', 'max:2000'],
            ])->validate();

        return DB::transaction(function () use ($storyline, $version, $data, $developmentId, $zone): StorylineDevelopment {
            $source = $this->source->handle($data->source, $storyline->promotion_id, historical: $data->reason !== null && mb_trim($data->reason) !== '');
            $story = $this->lock->handle($storyline, $version);
            $step = $data->stepId === null ? null : $story->steps()->findOrFail($data->stepId);
            if ($step !== null && $developmentId === null && $step->status !== StepStatus::Planned) {
                throw StorylineConflictException::state();
            }
            // MySQL reorders JSON object keys; value types and list order still matter.
            $contextChanged = $step !== null && collect($step->source_context)->sortKeys()->all() !== collect($source['context'])->sortKeys()->all();
            if (($source['warnings'] !== [] || $contextChanged) && mb_trim($data->reason ?? '') === '') {
                throw StorylineConflictException::source();
            }
            if ($data->source->eventId !== null) {
                $event = Event::query()->withTrashed()->where('promotion_id', $story->promotion_id)->findOrFail($data->source->eventId);
                if ($event->date === null || $event->date->isFuture() || $data->occurredOn !== $event->date->copy()->setTimezone($zone)->toDateString()) {
                    throw StorylineConflictException::source();
                }
            }
            $development = $developmentId === null ? new StorylineDevelopment : $story->developments()->findOrFail($developmentId);
            $savedCast = collect($development->cast ?? [])->keyBy(fn (array $person): string => $person['type'].':'.$person['id']);
            $participants = $story->participants()->with('participant')->whereIn('id', $data->participantIds)->orderBy('id')->get();
            if ($participants->count() !== count($data->participantIds)) {
                throw StorylineConflictException::source();
            }
            $cast = [];
            foreach ($participants as $participant) {
                if ($participant->joined_on->toDateString() > $data->occurredOn || ($participant->left_on !== null && $participant->left_on->toDateString() < $data->occurredOn)) {
                    throw StorylineConflictException::source();
                }
                $person = $participant->participant;
                if (! $person instanceof Wrestler && ! $person instanceof TagTeam) {
                    throw StorylineConflictException::source();
                }
                $cast[] = ['type' => $participant->participant_type, 'id' => $participant->participant_id, 'label' => $savedCast->get($participant->participant_type.':'.$participant->participant_id)['label'] ?? $person->name];
            }
            if ($developmentId !== null && $development->storyline_step_id !== $data->stepId) {
                throw StorylineConflictException::state();
            }
            if ($developmentId === null && $story->developments()->where('submission_id', $data->submissionId)->exists()) {
                throw StorylineConflictException::changed();
            }
            $before = $development->attributesToArray();
            $development->promotion_id = $story->promotion_id;
            $development->storyline_id = $story->id;
            $development->storyline_step_id = $data->stepId;
            $development->heading = $data->heading;
            $development->description = $data->description;
            $development->occurred_on = Carbon::parse($data->occurredOn);
            $development->timezone = $developmentId === null ? $zone : $development->timezone;
            $development->sequence = $data->sequence;
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
            $this->revision->handle($story, $development, $developmentId === null ? 'development_recorded' : 'development_corrected', $before, $data->reason);

            return $development;
        }, attempts: 3);
    }
}
