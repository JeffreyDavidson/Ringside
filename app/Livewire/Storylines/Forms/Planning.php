<?php

declare(strict_types=1);

namespace App\Livewire\Storylines\Forms;

use App\Data\Storylines\DevelopmentData;
use App\Data\Storylines\ParticipantData;
use App\Data\Storylines\SourceData;
use App\Data\Storylines\StepData;
use App\Data\Storylines\StorylineData;
use App\Enums\Storylines\StorylineFormMode;
use App\Models\Events\Event;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineParticipant;
use App\Models\Storylines\StorylineStep;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Form;

class Planning extends Form
{
    public string $heading = '';

    public string $description = '';

    public ?string $payoff = null;

    public ?int $eventId = null;

    public ?int $matchId = null;

    public ?string $segmentId = null;

    public ?string $targetOn = null;

    public string $occurredOn = '';

    public int $sequence = 1;

    public string $reason = '';

    public string $type = 'wrestler';

    public ?int $participantId = null;

    public string $role = '';

    public string $joinedOn = '';

    public ?string $leftOn = null;

    public string $status = '';

    public bool $reopenStep = false;

    /** @var list<int> */
    public array $participantIds = [];

    /**
     * The chosen event, match, segment and standalone date must fit together before anything reads or locks the source.
     * Errors are keyed event, match, segment and target, as the source fields are labelled on the page.
     */
    public function source(?string $fingerprint): SourceData
    {
        $source = new SourceData($this->eventId, $this->matchId, $this->segmentId ?: null, $this->targetOn ?: null, $fingerprint);
        Validator::make(['event' => $source->eventId, 'match' => $source->matchId, 'segment' => $source->segmentId, 'target' => $source->targetOn], [
            'event' => ['nullable', 'integer', 'required_with:match,segment'],
            'match' => ['nullable', 'integer', Rule::prohibitedIf($source->segmentId !== null)],
            'segment' => ['nullable', 'uuid', Rule::requiredIf($source->segmentId !== null)],
            'target' => ['nullable', 'date_format:Y-m-d', Rule::requiredIf($source->targetOn !== null), Rule::prohibitedIf($source->eventId !== null)],
        ])->validate();

        return $source;
    }

    /**
     * Fill the freshly reset form for the opened mode and report the entry it edits.
     * Recording a step opens the Development form on the step, or on the account that step already has.
     *
     * @return array{editingId: ?int, stepId: ?int}
     */
    public function open(StorylineFormMode $mode, Storyline $story, ?int $id, bool $recordStep, string $today): array
    {
        $this->occurredOn = $today;
        $this->joinedOn = $today;
        $lastSequence = $story->steps()->max('sequence');
        $this->sequence = is_numeric($lastSequence) ? (int) $lastSequence + 1 : 1;
        $editingId = $id;
        $stepId = null;

        if ($mode === StorylineFormMode::Story) {
            $this->heading = $story->title;
            $this->description = $story->premise;
            $this->payoff = $story->payoff;
        }

        if ($mode === StorylineFormMode::Step && $id !== null) {
            $this->fillEntry($story, $story->steps()->findOrFail($id));
        }

        if ($recordStep && $id !== null) {
            $step = $story->steps()->findOrFail($id);
            $this->fillEntry($story, $step);
            $stepId = $id;
            $existing = $story->developments()->where('storyline_step_id', $id)->first();
            $editingId = $existing?->id;

            if ($existing !== null) {
                $this->fillEntry($story, $existing);
            }

            $this->occurredOn = $step->event_id === null ? $this->occurredOn : Event::query()->withTrashed()->findOrFail($step->event_id)->local_date?->toDateString() ?? $this->occurredOn;
        }

        if ($mode === StorylineFormMode::Development && ! $recordStep && $id !== null) {
            $development = $story->developments()->findOrFail($id);
            $stepId = $development->storyline_step_id;
            $this->fillEntry($story, $development);
        }

        if ($mode === StorylineFormMode::ParticipantEnd) {
            $person = $story->participants()->findOrFail($id);
            $this->participantId = $person->participant_id;
            $this->type = $person->participant_type;
            $this->joinedOn = $person->joined_on->toDateString();
            $this->leftOn = $this->occurredOn;
            $this->role = $person->role ?? '';
        }

        return ['editingId' => $editingId, 'stepId' => $stepId];
    }

    public function storyData(string $submissionId): StorylineData
    {
        return new StorylineData($this->heading, $this->description, $this->payoff, $submissionId);
    }

    public function stepData(?string $fingerprint, string $submissionId): StepData
    {
        return new StepData($this->heading, $this->description, $this->sequence, $this->source($fingerprint), $submissionId, $this->reason ?: null);
    }

    public function developmentData(?string $fingerprint, string $submissionId, ?int $stepId): DevelopmentData
    {
        return new DevelopmentData($this->heading, $this->description, $this->occurredOn, $this->sequence, $this->participantIds, $this->source($fingerprint), $submissionId, $stepId, $this->reason ?: null);
    }

    public function participantData(): ParticipantData
    {
        return new ParticipantData($this->type, $this->participantId ?? 0, $this->role ?: null, $this->joinedOn, $this->leftOn, $this->reason ?: null);
    }

    private function fillEntry(Storyline $story, StorylineStep|StorylineDevelopment $entry): void
    {
        $this->heading = $entry->heading;
        $this->description = $entry->description;
        $this->sequence = $entry->sequence;
        $this->eventId = $entry->event_id;
        $this->matchId = $entry->match_id;
        $this->segmentId = $entry->segment_id;

        if ($entry instanceof StorylineStep) {
            $this->targetOn = $entry->target_on?->toDateString();

            return;
        }

        $this->occurredOn = $entry->occurred_on->toDateString();

        if ($entry->cast === []) {
            $this->participantIds = [];

            return;
        }

        $this->participantIds = array_values($story->participants()->where(function (Builder $query) use ($entry): void {
            foreach ($entry->cast as $person) {
                $query->orWhere(fn (Builder $cast) => $cast->where('participant_type', $person['type'])->where('participant_id', $person['id']));
            }
        })->where('joined_on', '<=', $entry->occurred_on)->where(fn (Builder $query) => $query->whereNull('left_on')->orWhere('left_on', '>=', $entry->occurred_on))->get(['id'])->map(fn (StorylineParticipant $person): int => $person->id)->values()->all());
    }
}
