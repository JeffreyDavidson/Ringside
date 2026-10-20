<?php

declare(strict_types=1);

namespace App\Livewire\Storylines\Components;

use App\Actions\Storylines\RecordDevelopmentAction;
use App\Actions\Storylines\RetractDevelopmentAction;
use App\Actions\Storylines\SaveParticipantAction;
use App\Actions\Storylines\SaveStepAction;
use App\Actions\Storylines\TransitionStepAction;
use App\Actions\Storylines\TransitionStorylineAction;
use App\Actions\Storylines\UpdateAction;
use App\Data\Storylines\DevelopmentData;
use App\Data\Storylines\ParticipantData;
use App\Data\Storylines\SourceData;
use App\Data\Storylines\StepData;
use App\Data\Storylines\StorylineData;
use App\Enums\Roster\BookableRosterKind;
use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineStatus;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Storylines\Forms\Planning;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineParticipant;
use App\Models\Storylines\StorylineStep;
use App\Queries\Storylines\CreativeRosterQuery;
use App\Services\Storylines\StorylineSourceService;
use App\Services\Storylines\StorylineTimezoneService;
use App\ViewModels\Storylines\StorylineViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithPagination;

class Manage extends Component
{
    use WithPagination;

    #[Locked]
    public int $storylineId;

    #[Locked]
    public int $version;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $stepId = null;

    #[Locked]
    public string $mode = '';

    #[Locked]
    public string $submissionId;

    #[Locked]
    public ?string $sourceFingerprint = null;

    #[Locked]
    public bool $needsReload = false;

    public Planning $form;

    public string $eventSearch = '';

    protected StorylineSourceService $sources;

    protected CreativeRosterQuery $roster;

    protected StorylineTimezoneService $timezones;

    public function boot(StorylineSourceService $sources, CreativeRosterQuery $roster, StorylineTimezoneService $timezones): void
    {
        $this->sources = $sources;
        $this->roster = $roster;
        $this->timezones = $timezones;
    }

    public function mount(int $storylineId): void
    {
        $this->storylineId = $storylineId;
        $story = $this->story();
        Gate::authorize('view', $story);
        $this->version = $story->version;
        $this->submissionId = (string) Str::uuid();
    }

    private function story(): Storyline
    {
        return Storyline::query()->findOrFail($this->storylineId);
    }

    public function begin(string $mode, ?int $id = null): void
    {
        $story = $this->story();
        Gate::authorize('update', $story);
        abort_unless(in_array($mode, ['story', 'step', 'development', 'record-step', 'participant', 'participant-end', 'step-status', 'story-status', 'retract'], true), 404);
        $this->form->reset();
        $this->resetValidation();
        $this->mode = $mode === 'record-step' ? 'development' : $mode;
        $this->editingId = $id;
        $this->stepId = null;
        $this->version = $story->version;
        $this->needsReload = false;
        $this->submissionId = (string) Str::uuid();
        $zone = Promotion::query()->findOrFail($story->promotion_id)->timezone;
        $this->form->occurredOn = now()->setTimezone($zone)->toDateString();
        $this->form->joinedOn = $this->form->occurredOn;
        $lastSequence = $story->steps()->max('sequence');
        $this->form->sequence = is_numeric($lastSequence) ? (int) $lastSequence + 1 : 1;
        if ($mode === 'story') {
            $this->form->heading = $story->title;
            $this->form->description = $story->premise;
            $this->form->payoff = $story->payoff;
        }
        if (in_array($mode, ['step', 'record-step'], true) && $id !== null) {
            $step = $story->steps()->findOrFail($id);
            $this->fillEntry($step);
            if ($mode === 'record-step') {
                $this->stepId = $id;
                $existing = $story->developments()->where('storyline_step_id', $id)->first();
                $this->editingId = $existing?->id;
                if ($existing !== null) {
                    $this->fillEntry($existing);
                }
                $this->form->occurredOn = $step->event_id === null ? $this->form->occurredOn : Event::query()->withTrashed()->findOrFail($step->event_id)->local_date?->toDateString() ?? $this->form->occurredOn;
            }
        }
        if ($mode === 'development' && $id !== null) {
            $development = $story->developments()->findOrFail($id);
            $this->stepId = $development->storyline_step_id;
            $this->fillEntry($development);
        }
        if ($mode === 'participant-end') {
            $person = $story->participants()->findOrFail($id);
            $this->form->participantId = $person->participant_id;
            $this->form->type = $person->participant_type;
            $this->form->joinedOn = $person->joined_on->toDateString();
            $this->form->leftOn = $this->form->occurredOn;
            $this->form->role = $person->role ?? '';
        }
        $this->sourceFingerprint = null;
        $this->reviewSource();
        $this->dispatch('storyline-form-opened');
    }

    private function fillEntry(StorylineStep|StorylineDevelopment $entry): void
    {
        $this->form->heading = $entry->heading;
        $this->form->description = $entry->description;
        $this->form->sequence = $entry->sequence;
        $this->form->eventId = $entry->event_id;
        $this->form->matchId = $entry->match_id;
        $this->form->segmentId = $entry->segment_id;
        if ($entry instanceof StorylineStep) {
            $this->form->targetOn = $entry->target_on?->toDateString();

            return;
        }
        $this->form->occurredOn = $entry->occurred_on->toDateString();
        if ($entry->cast === []) {
            $this->form->participantIds = [];

            return;
        }
        $this->form->participantIds = array_values($this->story()->participants()->where(function (Builder $query) use ($entry): void {
            foreach ($entry->cast as $person) {
                $query->orWhere(fn (Builder $cast) => $cast->where('participant_type', $person['type'])->where('participant_id', $person['id']));
            }
        })->where('joined_on', '<=', $entry->occurred_on)->where(fn (Builder $query) => $query->whereNull('left_on')->orWhere('left_on', '>=', $entry->occurred_on))->get(['id'])->map(fn (StorylineParticipant $person): int => $person->id)->values()->all());
    }

    public function updatedFormType(): void
    {
        Gate::authorize('update', $this->story());
        $this->form->participantId = null;
    }

    public function updatedFormEventId(): void
    {
        $this->form->matchId = null;
        $this->form->segmentId = null;
        $this->form->targetOn = null;
        $this->reviewSource();
    }

    public function updatedFormMatchId(): void
    {
        $this->form->segmentId = null;
        $this->reviewSource();
    }

    public function updatedFormSegmentId(): void
    {
        $this->form->matchId = null;
        $this->reviewSource();
    }

    public function updatedFormTargetOn(): void
    {
        $this->reviewSource();
    }

    public function reviewSource(): void
    {
        $story = $this->story();
        Gate::authorize('update', $story);
        $this->sourceFingerprint = ($this->sources->get($this->form->source(null), $story->promotion_id) ?? abort(404))['fingerprint'];
    }

    public function reload(): void
    {
        $story = $this->story();
        Gate::authorize('update', $story);
        $this->version = $story->version;
        $this->reviewSource();
        $this->needsReload = false;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        Gate::authorize('view', $this->story());
        $this->mode = '';
        $this->resetValidation();
    }

    /** @return list<array{id: int|string, name: string}> */
    #[Renderless]
    public function searchRoster(string $kind, string $term): array
    {
        $story = $this->story();
        Gate::authorize('update', $story);

        return $this->roster->get(BookableRosterKind::from($kind), mb_substr($term, 0, 160), $story->promotion_id);
    }

    public function save(
        UpdateAction $updateStory,
        SaveStepAction $saveStep,
        RecordDevelopmentAction $recordDevelopment,
        SaveParticipantAction $saveParticipant,
        TransitionStepAction $transitionStep,
        TransitionStorylineAction $transitionStory,
        RetractDevelopmentAction $retractDevelopment,
    ): void {
        $story = $this->story();
        Gate::authorize('update', $story);
        if ($this->needsReload) {
            return;
        }
        $rules = ['form.status' => ['string'], 'form.type' => ['in:wrestler,tag_team'], 'form.participantId' => ['nullable', 'integer'], ...$this->modeRules($story)];
        $this->validate($rules, attributes: $this->attributeNames($rules));
        try {
            match ($this->mode) {
                'story' => $updateStory->handle($story, $this->version, new StorylineData($this->form->heading, $this->form->description, $this->form->payoff, $this->submissionId), $this->form->reason),
                'step' => $saveStep->handle($story, $this->version, new StepData($this->form->heading, $this->form->description, $this->form->sequence, $this->form->source($this->sourceFingerprint), $this->submissionId, $this->form->reason ?: null), $this->editingId),
                'development' => $recordDevelopment->handle($story, $this->version, new DevelopmentData($this->form->heading, $this->form->description, $this->form->occurredOn, $this->form->sequence, $this->form->participantIds, $this->form->source($this->sourceFingerprint), $this->submissionId, $this->stepId, $this->form->reason ?: null), $this->editingId),
                'participant', 'participant-end' => $saveParticipant->handle($story, $this->version, new ParticipantData($this->form->type, $this->form->participantId ?? 0, $this->form->role ?: null, $this->form->joinedOn, $this->form->leftOn, $this->form->reason ?: null), $this->editingId),
                'step-status' => $transitionStep->handle($story, $this->version, $this->editingId ?? 0, StepStatus::tryFrom($this->form->status) ?? StepStatus::Happened, $this->form->reason),
                'story-status' => $transitionStory->handle($story, $this->version, StorylineStatus::tryFrom($this->form->status) ?? $story->status, $this->form->reason),
                'retract' => $retractDevelopment->handle($story, $this->version, $this->editingId ?? 0, $this->form->reason, $this->form->reopenStep),
                default => abort(404),
            };
        } catch (BaseBusinessException $exception) {
            $this->addError('storyline', $exception->getMessage());
            $this->needsReload = true;

            return;
        }
        $this->version = $this->story()->version;
        $this->mode = '';
        $this->dispatch('storyline-saved');
    }

    /**
     * The field rules for the open form; the Actions receive only input that passed them.
     *
     * @return array<string, list<mixed>>
     */
    private function modeRules(Storyline $story): array
    {
        $reason = ['string', 'max:2000'];
        $changing = $this->editingId === null ? 'nullable' : 'required';
        $entry = [
            'form.heading' => ['required', 'string', 'max:160'],
            'form.description' => ['required', 'string', 'max:4000'],
        ];
        $planned = [
            'form.sequence' => ['required', 'integer', 'min:1', 'max:10000'],
            'submissionId' => ['required', 'uuid'],
            'form.reason' => [$changing, ...$reason],
        ];

        return match ($this->mode) {
            'story' => [...$entry, 'form.payoff' => ['nullable', 'string', 'max:4000'], 'form.reason' => ['required', ...$reason]],
            'step' => [...$entry, ...$planned],
            'development' => [
                ...$entry,
                ...$planned,
                'form.occurredOn' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now()->setTimezone($this->timezones->forDevelopment($story, $this->editingId))->toDateString()],
                'form.participantIds' => ['array', 'list', 'max:100'],
                'form.participantIds.*' => ['integer', 'distinct'],
            ],
            'participant', 'participant-end' => [
                'form.type' => ['required', Rule::in(['wrestler', 'tag_team'])],
                'form.participantId' => ['required', 'integer', 'min:1'],
                'form.role' => ['nullable', 'string', 'max:160'],
                'form.joinedOn' => ['required', 'date_format:Y-m-d'],
                'form.leftOn' => [$changing, 'date_format:Y-m-d', 'after_or_equal:form.joinedOn'],
                'form.reason' => [$changing, ...$reason],
            ],
            'step-status', 'story-status', 'retract' => ['form.reason' => ['required', ...$reason]],
            default => [],
        };
    }

    /**
     * @param  array<string, list<mixed>>  $rules
     * @return array<string, string>
     */
    private function attributeNames(array $rules): array
    {
        return collect($rules)->mapWithKeys(fn (array $fieldRules, string $field): array => [$field => Str::of($field)->afterLast('.')->replace('*', 'entry')->snake(' ')->toString()])->all();
    }

    public function render(StorylineViewModel $summary): View
    {
        $story = $this->story();
        Gate::authorize('view', $story);
        $needle = mb_trim(str_replace(['%', '_', '\\'], '', $this->eventSearch));
        $events = Event::query()->where('promotion_id', $story->promotion_id)->when($needle !== '', fn (Builder $query) => $query->whereLike('name', "%{$needle}%"))->orderByDesc('date')->orderByDesc('id')->limit(20)->get();
        $selected = $this->form->eventId === null ? null : Event::query()->withTrashed()->where('promotion_id', $story->promotion_id)->with(['matches', 'pack', 'promotion'])->findOrFail($this->form->eventId);
        if ($selected !== null && ! $events->contains('id', $selected->id)) {
            $events->push($selected);
        }
        $source = $this->sources->get($this->form->source(null), $story->promotion_id) ?? abort(404);

        $steps = $story->steps()->with(['event.promotion', 'event.pack', 'event.liveRun', 'event.matches', 'match.competitors', 'match.titles'])->orderBy('sequence')->orderBy('id')->paginate(15, pageName: 'stepsPage');
        $sourceStates = [];
        foreach ($steps as $step) {
            $sourceStates[$step->id] = $this->sources->get(new SourceData($step->event_id, $step->match_id, $step->segment_id, $step->target_on?->toDateString()), $story->promotion_id, $step->event, $step->match) ?? abort(404);
        }

        return view('livewire.storylines.components.manage', [
            'story' => $story, ...$summary->summary($story),
            'participants' => $story->participants()->orderBy('joined_on')->orderBy('id')->get(),
            'steps' => $steps, 'sourceStates' => $sourceStates,
            'developments' => $story->developments()->with('event.promotion')->orderByDesc('occurred_on')->orderByDesc('sequence')->orderByDesc('id')->paginate(15, pageName: 'historyPage'),
            'revisions' => $story->revisions()->with('actor')->orderByDesc('id')->paginate(15, pageName: 'changesPage'),
            'events' => $events, 'selectedEvent' => $selected, 'source' => $source,
        ]);
    }
}
