<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Modals;

use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\UpdateMatchAction;
use App\Enums\BusinessRuleReason;
use App\Enums\MatchType;
use App\Enums\Roster\BookableRosterKind;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsMatchTypesList;
use App\Livewire\Concerns\Data\PresentsTitlesList;
use App\Livewire\Matches\Enums\CompetitorSelectionLayout;
use App\Livewire\Matches\Forms\CreateEditForm;
use App\Livewire\Matches\Support\BookableRosterSearch;
use App\Livewire\Matches\Support\MatchFormDummyData;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;

/**
 * @extends BaseFormModal<CreateEditForm, EventMatch>
 *
 * @property-read array<string,string> $getMatchTypes
 * @property-read array<int|string,string|null> $getTitles
 * @property-read array<int, string> $getMatchStipulations
 * @property-read array<string, array<int, array{id: int|string, name: string}>> $selectedRosterLabels
 * @property-read bool $matchTypeAllowsTagTeams
 * @property-read CompetitorSelectionLayout|null $competitorSelectionLayout
 */
class FormModal extends BaseFormModal
{
    use PresentsMatchTypesList;
    use PresentsTitlesList;

    #[\Override]
    protected bool $resetFormAfterSubmission = true;

    #[Locked]
    public int $eventId = 0;

    public CreateEditForm $form;

    private MatchFormDummyData $dummyData;

    private AddMatchForEventAction $addMatchForEventAction;

    private UpdateMatchAction $updateMatchAction;

    #[\Override]
    public function mount(int|string|null $modelId = null, ?int $eventId = null): void
    {
        if ($eventId !== null) {
            $this->eventId = $eventId;
        }

        parent::mount($modelId);
    }

    public function boot(
        MatchFormDummyData $dummyData,
        AddMatchForEventAction $addMatchForEventAction,
        UpdateMatchAction $updateMatchAction,
    ): void {
        $this->dummyData = $dummyData;
        $this->addMatchForEventAction = $addMatchForEventAction;
        $this->updateMatchAction = $updateMatchAction;
    }

    protected function getModelClass(): string
    {
        return EventMatch::class;
    }

    #[\Override]
    protected function storeForm(): bool
    {
        $event = $this->bookedEvent();

        $this->form->validateForEvent($event);

        try {
            if ($this->form->isEditing()) {
                $match = EventMatch::query()->findOrFail($this->form->modelId);
                $storedMatch = $this->updateMatchAction->handle($match, $this->form->toData());
            } else {
                $storedMatch = $this->addMatchForEventAction->handle($event, $this->form->toData());
            }
        } catch (BaseBusinessException $exception) {
            $field = $exception instanceof InvalidMatchConfigurationException
                && $exception->reason() === BusinessRuleReason::CurrentChampionMissing
                    ? 'form.titles'
                    : 'form.configuration';

            $this->addError($field, $exception->getMessage());

            return false;
        }

        $this->form->setModel($storedMatch);

        return true;
    }

    /** @return array<int, string> */
    #[Computed]
    public function getMatchStipulations(): array
    {
        return MatchStipulation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn (MatchStipulation $stipulation): array => [
                $stipulation->id => $stipulation->name,
            ])
            ->all();
    }

    /**
     * Search bookable roster records by name for the form's searchable selects.
     *
     * The result is a convenience only; submission still validates every id server-side.
     *
     * @return array<int, array{id: int|string, name: string}>
     */
    #[Renderless]
    public function searchRoster(string $kind, string $term): array
    {
        Gate::authorize('create', EventMatch::class);

        $rosterKind = BookableRosterKind::tryFrom($kind);

        if (! $rosterKind instanceof BookableRosterKind) {
            return [];
        }

        return resolve(BookableRosterSearch::class)->search($rosterKind, $term, $this->bookedEvent()->promotion_id);
    }

    /**
     * The event the match is booked on: an edited match's own event, otherwise the modal's event. Its
     * promotion, not the request's promotion context, decides whose roster and titles may be booked.
     */
    private function bookedEvent(): Event
    {
        if ($this->form->isEditing()) {
            return EventMatch::query()->findOrFail($this->form->modelId)->event()->firstOrFail();
        }

        return Event::query()->findOrFail($this->eventId);
    }

    /** An event hidden from the user has no promotion to offer titles from; saving is rejected separately. */
    protected function titlesPromotionId(): ?int
    {
        return Event::query()->find($this->eventId)?->promotion_id;
    }

    /** @return array<int, mixed> */
    protected function selectedTitleIds(): array
    {
        return collect($this->form->titles)->flatten()->all();
    }

    /**
     * Names for the ids already chosen in the form, so editing shows them even when they are
     * no longer bookable or fall outside the first search results.
     *
     * @return array<string, array<int, array{id: int|string, name: string}>>
     */
    #[Computed]
    public function selectedRosterLabels(): array
    {
        $search = resolve(BookableRosterSearch::class);
        $sides = collect($this->form->competitors);

        return [
            BookableRosterKind::Wrestlers->value => $search->labels(
                BookableRosterKind::Wrestlers,
                $sides->pluck('wrestlers')->flatten()->all(),
            ),
            BookableRosterKind::TagTeams->value => $search->labels(
                BookableRosterKind::TagTeams,
                $sides->pluck('tag_teams')->flatten()->all(),
            ),
            BookableRosterKind::Referees->value => $search->labels(
                BookableRosterKind::Referees,
                $this->form->referees,
            ),
        ];
    }

    protected function populateDummyData(): void
    {
        $this->dummyData->fill($this->form);
    }

    #[\Override]
    public function getModalTitle(): string
    {
        if ($this->form->isEditing()) {
            return __('matches.modal.edit');
        }

        return parent::getModalTitle();
    }

    public function updatedFormMatchType(mixed $value): void
    {
        $matchType = match (true) {
            $value instanceof MatchType => $value,
            is_string($value) => MatchType::tryFrom($value),
            default => null,
        };

        if ($matchType !== null) {
            $this->form->resetCompetitorsFor($matchType);
        }
    }

    #[Computed]
    public function matchTypeAllowsTagTeams(): bool
    {
        return $this->form->matchType?->allowsTagTeams() ?? false;
    }

    #[Computed]
    public function competitorSelectionLayout(): ?CompetitorSelectionLayout
    {
        return $this->form->matchType instanceof MatchType
            ? CompetitorSelectionLayout::forMatchType($this->form->matchType)
            : null;
    }

    public function render(): View
    {
        return view('livewire.matches.modals.form-modal');
    }
}
