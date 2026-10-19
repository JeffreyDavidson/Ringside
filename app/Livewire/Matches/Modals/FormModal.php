<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Modals;

use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\UpdateMatchAction;
use App\Enums\Matches\MatchType;
use App\Enums\Roster\BookableRosterKind;
use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsMatchTypesList;
use App\Livewire\Concerns\Data\PresentsTitlesList;
use App\Livewire\Matches\Enums\CompetitorSelectionLayout;
use App\Livewire\Matches\Forms\CreateEditForm;
use App\Livewire\Matches\Support\BookableRosterSearch;
use App\Livewire\Matches\Support\BookingHistoryLinks;
use App\Livewire\Matches\Support\BookingHistorySelection;
use App\Livewire\Matches\Support\MatchFormDummyData;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use App\Queries\Matches\SameDayBookingsQuery;
use App\Services\Promotions\PromotionTimeZoneService;
use App\ViewModels\Matches\BookingHistoryViewModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;

/**
 * @phpstan-import-type LinkedSnapshot from BookingHistoryLinks
 *
 * @extends BaseFormModal<CreateEditForm, EventMatch>
 *
 * @property-read array<string,string> $getMatchTypes
 * @property-read array<int|string,string|null> $getTitles
 * @property-read array<int, string> $getMatchStipulations
 * @property-read array<string, array<int, array{id: int|string, name: string}>> $selectedRosterLabels
 * @property-read list<array{person: string, event: string}> $sameDayBookings
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

    private BookableRosterSearch $rosterSearch;

    private SameDayBookingsQuery $sameDayBookingsQuery;

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
        BookableRosterSearch $rosterSearch,
        SameDayBookingsQuery $sameDayBookingsQuery,
    ): void {
        $this->dummyData = $dummyData;
        $this->addMatchForEventAction = $addMatchForEventAction;
        $this->updateMatchAction = $updateMatchAction;
        $this->rosterSearch = $rosterSearch;
        $this->sameDayBookingsQuery = $sameDayBookingsQuery;
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

        return $this->reportBusinessErrors(function () use ($event): void {
            if ($this->form->isEditing()) {
                $match = EventMatch::query()->findOrFail($this->form->modelId);
                $storedMatch = $this->updateMatchAction->handle($match, $this->form->toData());
            } else {
                $storedMatch = $this->addMatchForEventAction->handle($event, $this->form->toData());
            }

            $this->form->setModel($storedMatch);
        });
    }

    #[\Override]
    protected function businessErrorField(BaseBusinessException $exception): string
    {
        return $exception instanceof InvalidMatchConfigurationException
            && in_array($exception->reason(), [BusinessRuleReason::CurrentChampionMissing, BusinessRuleReason::TitleRequiresOneCompetitorPerSide], true)
                ? 'form.titles'
                : 'form.configuration';
    }

    /** @return array<int, string> */
    #[Computed]
    public function getMatchStipulations(): array
    {
        return MatchStipulation::query()
            ->active()
            ->alphabetical()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (MatchStipulation $stipulation): array => [
                $stipulation->id => $stipulation->name,
            ])
            ->all();
    }

    /** @return LinkedSnapshot|array{error: string} */
    #[Renderless]
    public function checkHistory(
        BookingHistorySelection $selection,
        BookingHistoryViewModel $history,
        BookingHistoryLinks $historyLinks,
        int $page = 1,
    ): array {
        $editedMatch = null;

        if ($this->form->isEditing()) {
            $editedMatch = EventMatch::query()->findOrFail($this->form->modelId);
            Gate::authorize('update', $editedMatch);
        } else {
            Gate::authorize('create', EventMatch::class);
        }

        $event = $this->bookedEvent();
        Gate::authorize('update', $event);

        if (! $this->form->matchType instanceof MatchType) {
            return ['error' => __('matches.history.selection_required')];
        }

        try {
            $selection->validate($event, $this->form->matchType, $this->form->competitors);
        } catch (ValidationException) {
            return ['error' => __('matches.history.unavailable_selection')];
        }

        $snapshot = $history->snapshot(
            $event, $this->form->matchType, $this->form->competitors, $editedMatch?->id, $page,
        );

        return $historyLinks->add($snapshot);
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

        $event = $this->bookedEvent();
        $event->loadMissing('promotion');

        return $this->rosterSearch->search($rosterKind, $term, $event->promotion_id, $event->local_date?->toDateString(), $event->date);
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
        $search = $this->rosterSearch;
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

    /**
     * Who among the chosen wrestlers, tag teams (and their current wrestlers) and referees is also booked on
     * another show of the same promotion-local day. A warning only: saving is not blocked, and a show at the
     * exact same time is still refused by the booking actions.
     *
     * @return list<array{person: string, event: string}>
     */
    #[Computed]
    public function sameDayBookings(): array
    {
        $sides = collect($this->form->competitors);
        $selection = [
            BookableRosterKind::Wrestlers->value => $this->selectedIds($sides->pluck('wrestlers')->flatten()->all()),
            BookableRosterKind::TagTeams->value => $this->selectedIds($sides->pluck('tag_teams')->flatten()->all()),
            BookableRosterKind::Referees->value => $this->selectedIds($this->form->referees),
        ];
        $event = $this->form->isEditing() ? $this->bookedEvent() : Event::query()->find($this->eventId);
        $date = $event?->date;

        if (! $event instanceof Event || ! $date instanceof Carbon || collect($selection)->flatten()->isEmpty()) {
            return [];
        }

        $event->loadMissing('promotion');
        $from = PromotionTimeZoneService::toLocalTime($event->promotion, $date)->startOfDay();

        return array_values(collect($selection)
            ->flatMap(fn (Collection $ids, string $kind): Collection => $this->sameDayBookingsQuery->get(
                BookableRosterKind::from($kind), $ids, $from, $from->copy()->addDay()->startOfDay(), $event->id,
            ))
            ->unique()
            ->all());
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return Collection<int, int>
     */
    private function selectedIds(array $values): Collection
    {
        return collect($values)->filter(fn (mixed $id): bool => is_numeric($id))->map(fn (mixed $id): int => (int) $id)->unique()->values();
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
