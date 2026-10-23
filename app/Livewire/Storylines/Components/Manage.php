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
use App\Data\Storylines\SourceData;
use App\Enums\Roster\BookableRosterKind;
use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineFormMode;
use App\Enums\Storylines\StorylineStatus;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ResolvesActor;
use App\Livewire\Storylines\Forms\Planning;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Queries\Storylines\CreativeRosterQuery;
use App\Services\Storylines\StorylineSourceService;
use App\Services\Storylines\StorylineTimezoneService;
use App\Support\Search\LikeTerm;
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
    use ResolvesActor;
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
    public ?StorylineFormMode $mode = null;

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
        $recordStep = $mode === 'record_step';
        $formMode = StorylineFormMode::tryFrom($recordStep ? StorylineFormMode::Development->value : $mode) ?? abort(404);
        $this->form->reset();
        $this->resetValidation();
        $this->mode = $formMode;
        $this->version = $story->version;
        $this->needsReload = false;
        $this->submissionId = (string) Str::uuid();
        $zone = Promotion::query()->findOrFail($story->promotion_id)->timezone;
        ['editingId' => $this->editingId, 'stepId' => $this->stepId] = $this->form->open($formMode, $story, $id, $recordStep, now()->setTimezone($zone)->toDateString());
        $this->sourceFingerprint = null;
        $this->reviewSource();
        $this->dispatch('storyline-form-opened');
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
        $this->mode = null;
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
        $mode = $this->mode ?? abort(404);
        $rules = ['form.status' => ['string'], 'form.type' => ['in:wrestler,tag_team'], 'form.participantId' => ['nullable', 'integer'], ...$this->modeRules($mode, $story)];
        $this->validate($rules, attributes: $this->attributeNames($rules));

        try {
            match ($mode) {
                StorylineFormMode::Story => $updateStory->handle($story, $this->version, $this->form->storyData($this->submissionId), $this->form->reason, $this->actor()),
                StorylineFormMode::Step => $saveStep->handle($story, $this->version, $this->form->stepData($this->sourceFingerprint, $this->submissionId), $this->actor(), $this->editingId),
                StorylineFormMode::Development => $recordDevelopment->handle($story, $this->version, $this->form->developmentData($this->sourceFingerprint, $this->submissionId, $this->stepId), $this->actor(), $this->editingId),
                StorylineFormMode::Participant, StorylineFormMode::ParticipantEnd => $saveParticipant->handle($story, $this->version, $this->form->participantData(), $this->actor(), $this->editingId),
                StorylineFormMode::StepStatus => $transitionStep->handle($story, $this->version, $this->editingId ?? 0, StepStatus::tryFrom($this->form->status) ?? StepStatus::Happened, $this->form->reason, $this->actor()),
                StorylineFormMode::StoryStatus => $transitionStory->handle($story, $this->version, StorylineStatus::tryFrom($this->form->status) ?? $story->status, $this->form->reason, $this->actor()),
                StorylineFormMode::Retract => $retractDevelopment->handle($story, $this->version, $this->editingId ?? 0, $this->form->reason, $this->form->reopenStep, $this->actor()),
            };
        } catch (BaseBusinessException $exception) {
            $this->addError('storyline', $exception->getMessage());
            $this->needsReload = true;

            return;
        }
        $this->version = $this->story()->version;
        $this->mode = null;
        $this->dispatch('storyline-saved');
    }

    /**
     * The field rules for the open form; the Actions receive only input that passed them.
     *
     * @return array<string, list<mixed>>
     */
    private function modeRules(StorylineFormMode $mode, Storyline $story): array
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

        return match ($mode) {
            StorylineFormMode::Story => [...$entry, 'form.payoff' => ['nullable', 'string', 'max:4000'], 'form.reason' => ['required', ...$reason]],
            StorylineFormMode::Step => [...$entry, ...$planned],
            StorylineFormMode::Development => [
                ...$entry,
                ...$planned,
                'form.occurredOn' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now()->setTimezone($this->timezones->forDevelopment($story, $this->editingId))->toDateString()],
                'form.participantIds' => ['array', 'list', 'max:100'],
                'form.participantIds.*' => ['integer', 'distinct'],
            ],
            StorylineFormMode::Participant, StorylineFormMode::ParticipantEnd => [
                'form.type' => ['required', Rule::in(['wrestler', 'tag_team'])],
                'form.participantId' => ['required', 'integer', 'min:1'],
                'form.role' => ['nullable', 'string', 'max:160'],
                'form.joinedOn' => ['required', 'date_format:Y-m-d'],
                'form.leftOn' => [$changing, 'date_format:Y-m-d', 'after_or_equal:form.joinedOn'],
                'form.reason' => [$changing, ...$reason],
            ],
            StorylineFormMode::StepStatus, StorylineFormMode::StoryStatus, StorylineFormMode::Retract => ['form.reason' => ['required', ...$reason]],
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
        $like = LikeTerm::from($this->eventSearch);
        $events = Event::query()->where('promotion_id', $story->promotion_id)->when(! $like->isEmpty(), fn (Builder $query) => $query->whereLike('name', $like->contains()))->orderByDesc('date')->orderByDesc('id')->limit(20)->get();
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
