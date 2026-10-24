<?php

declare(strict_types=1);

namespace App\Livewire\Tournaments\Components;

use App\Actions\Tournaments\AwardTournamentWalkoverAction;
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\CancelTournamentAction;
use App\Actions\Tournaments\PlanTournamentBoutAction;
use App\Actions\Tournaments\RenameTournamentAction;
use App\Actions\Tournaments\ReplaceTournamentEntryAction;
use App\Actions\Tournaments\ResetTournamentBranchAction;
use App\Actions\Tournaments\SaveTournamentAction;
use App\Actions\Tournaments\WithdrawTournamentEntryAction;
use App\Data\Tournaments\TournamentBoutData;
use App\Enums\Roster\BookableRosterKind;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentOperation;
use App\Enums\Tournaments\TournamentOperationTarget;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ExplainsKeptInput;
use App\Livewire\Concerns\ResolvesActor;
use App\Livewire\Matches\Support\BookableRosterSearch;
use App\Livewire\Tournaments\Forms\CreateEdit;
use App\Livewire\Tournaments\Forms\TournamentOperationForm;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use App\Queries\Events\EventSearchQuery;
use App\Services\Promotions\PromotionTimeZoneService;
use App\Services\Tournaments\TournamentBracketService;
use App\ViewModels\Tournaments\TournamentShowViewModel;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class Show extends Component
{
    use ExplainsKeptInput;
    use ResolvesActor;

    #[Locked]
    public int $tournamentId;

    #[Locked]
    public int $version = 0;

    #[Locked]
    public ?int $nodeId = null;

    #[Locked]
    public ?int $entryId = null;

    #[Locked]
    public int $draftVersion = 0;

    public CreateEdit $form;

    public TournamentOperationForm $draft;

    public string $viewMode = 'rounds';

    public ?string $notice = null;

    public function mount(int $tournamentId): void
    {
        $this->tournamentId = $tournamentId;
        $this->refreshState();
    }

    #[On('refreshDatatable')]
    public function refreshState(): void
    {
        $tournament = $this->tournament();
        Gate::authorize('view', $tournament);
        $this->version = $tournament->version;
        $this->form->load($tournament);
        $this->draft->close();
        $this->resetErrorBag();
    }

    public function view(string $mode): void
    {
        Gate::authorize('view', $this->tournament());
        Validator::make(['mode' => $mode], ['mode' => [Rule::in(['rounds', 'bracket'])]])->validate();
        $this->viewMode = $mode;
    }

    public function move(int $index, int $direction): void
    {
        Gate::authorize('update', $this->tournament());
        $this->form->move($index, $direction);
    }

    public function updatedFormKind(): void
    {
        $this->form->entrants = [];
    }

    public function save(SaveTournamentAction $save): void
    {
        $tournament = $this->tournament();
        Gate::authorize('update', $tournament);
        $data = $this->form->data();
        $this->perform(fn (): Tournament => $save->handle($tournament->promotion()->firstOrFail(), $data, $this->actor(), $tournament, $this->version));
    }

    public function rename(RenameTournamentAction $rename): void
    {
        $tournament = $this->tournament();
        Gate::authorize('update', $tournament);
        $this->form->validateNaming();
        $this->perform(fn () => $rename->handle($tournament, $this->version, $this->form->name, $this->form->description, $this->actor()));
    }

    public function begin(string $operation, int $id = 0): void
    {
        $tournament = $this->tournament();
        Gate::authorize('update', $tournament);
        Validator::make(
            ['operation' => $operation],
            ['operation' => [Rule::enum(TournamentOperation::class)]],
        )->validate();

        $draft = TournamentOperation::from($operation);
        $this->nodeId = null;
        $this->entryId = null;
        $eventId = null;

        if ($draft->target() === TournamentOperationTarget::Entry) {
            $this->entryId = $tournament->entries()->findOrFail($id)->id;
        }

        if ($draft->target() === TournamentOperationTarget::Node) {
            $node = $tournament->nodes()->findOrFail($id);
            $this->nodeId = $node->id;
            $eventId = $node->planned_event_id;
        }

        $this->draft->start($tournament, $draft, $eventId);
        $this->draftVersion = $this->version;
        $this->notice = null;
        $this->resetErrorBag();
        $this->dispatch('tournament-panel-opened');
    }

    public function cancelDraft(): void
    {
        Gate::authorize('view', $this->tournament());
        $this->draft->close();
        $this->resetErrorBag();
    }

    public function confirm(
        BookTournamentMatchAction $book,
        PlanTournamentBoutAction $plan,
        ResetTournamentBranchAction $reset,
        WithdrawTournamentEntryAction $withdraw,
        ReplaceTournamentEntryAction $replace,
        AwardTournamentWalkoverAction $walkover,
        CancelTournamentAction $cancel,
    ): void {
        $tournament = $this->tournament();
        Gate::authorize('update', $tournament);
        Validator::make(
            ['operation' => $this->draft->operation],
            ['operation' => [Rule::enum(TournamentOperation::class)]],
        )->validate();

        $operation = TournamentOperation::from($this->draft->operation);
        $this->draft->validate();
        $this->perform(function () use ($tournament, $operation, $book, $plan, $reset, $withdraw, $replace, $walkover, $cancel): void {
            match ($operation) {
                TournamentOperation::Book => $book->handle($this->node($tournament), $this->draftVersion, $this->draft->bookingData(), $this->actor()),
                TournamentOperation::Plan => $plan->handle(
                    $this->node($tournament),
                    $this->draft->eventId === null ? null : Event::query()->findOrFail($this->draft->eventId),
                    $this->draftVersion,
                    $this->actor(),
                ),
                TournamentOperation::Reset => $reset->handle($this->node($tournament), $this->draftVersion, $this->draft->reason, $this->actor()),
                TournamentOperation::Unbook => $reset->handle($this->node($tournament), $this->draftVersion, $this->draft->reason, $this->actor(), true),
                TournamentOperation::Withdraw => $withdraw->handle($this->entry($tournament), $this->draftVersion, $this->draft->reason, $this->actor()),
                TournamentOperation::Replace => $replace->handle(
                    $this->entry($tournament),
                    (int) $this->draft->replacementId,
                    $this->draftVersion,
                    $this->draft->reason,
                    $this->actor(),
                ),
                TournamentOperation::Walkover => $walkover->handle($this->node($tournament), $this->draftVersion, $this->draft->reason, $this->actor()),
                TournamentOperation::Cancel => $cancel->handle($tournament, $this->draftVersion, $this->draft->reason, $this->actor()),
            };
        });
    }

    /** @return array<int, array{id:int|string,name:string}> */
    #[Renderless]
    public function searchRoster(BookableRosterSearch $search, string $kind, string $term): array
    {
        $tournament = $this->tournament();
        Gate::authorize('update', $tournament);
        $rosterKind = BookableRosterKind::tryFrom($kind);

        return $rosterKind === null ? [] : $search->search($rosterKind, $term, $tournament->promotion_id);
    }

    /**
     * Events of the tournament's promotion whose name contains the term, for the event search box. The booking
     * validation still decides which event may be chosen.
     *
     * @return array<int, array{id: int, name: string}>
     */
    #[Renderless]
    public function searchEvents(EventSearchQuery $events, string $term): array
    {
        $tournament = $this->tournament();
        Gate::authorize('update', $tournament);
        $promotion = $tournament->promotion()->firstOrFail();

        return $events->get($term, $promotion->id)
            ->map(fn (Event $event): array => $this->eventOption($promotion, $event))
            ->all();
    }

    public function render(TournamentBracketService $bracket, TournamentShowViewModel $page, BookableRosterSearch $search): View
    {
        $tournament = $this->tournament();
        Gate::authorize('view', $tournament);
        $bouts = $bracket->bouts($tournament);
        $tournament->setRelation('nodes', new Collection($bouts->map(fn (TournamentBoutData $bout): TournamentNode => $bout->node)->all()));
        $kind = $tournament->entrant_kind === EntrantKind::TagTeam ? BookableRosterKind::TagTeams : BookableRosterKind::Wrestlers;
        $reads = $page->data($tournament);

        return view('livewire.tournaments.components.show', [
            'tournament' => $tournament,
            'bouts' => $bouts,
            'availableIds' => $reads['availableIds'],
            'nextBout' => $this->nextBout($bouts, $reads['availableIds']),
            'kind' => $kind,
            'labels' => $search->labels($kind, $this->form->entrants),
            'entries' => $reads['entries'],
            'eventLabels' => $this->eventLabels($tournament),
            'titles' => $reads['titles'],
            'stipulations' => $reads['stipulations'],
            'refereeLabels' => $search->labels(BookableRosterKind::Referees, $this->draft->refereeIds),
            'replacementLabels' => $search->labels($kind, [$this->draft->replacementId]),
            'affected' => $this->affectedBouts($tournament, $bouts, $bracket),
            'selected' => $this->nodeId === null ? null : $bouts->get($this->nodeId),
            'view' => $this->viewMode === 'bracket' ? 'bracket' : 'rounds',
        ]);
    }

    /**
     * The chosen event's name, so the search box shows it when the booking panel opens.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function eventLabels(Tournament $tournament): array
    {
        if ($this->draft->eventId === null) {
            return [];
        }

        $promotion = $tournament->promotion()->firstOrFail();

        return Event::query()
            ->where('promotion_id', $promotion->id)
            ->whereKey($this->draft->eventId)
            ->get()
            ->map(fn (Event $event): array => $this->eventOption($promotion, $event))
            ->all();
    }

    /**
     * An event as the search box lists it: its name and its date in the promotion's time zone.
     *
     * @return array{id: int, name: string}
     */
    private function eventOption(Promotion $promotion, Event $event): array
    {
        $when = $event->date === null
            ? __('tournaments.unscheduled')
            : PromotionTimeZoneService::toLocalTime($promotion, $event->date)->format('Y-m-d H:i T');

        return ['id' => $event->id, 'name' => "{$event->name} · {$when}"];
    }

    /**
     * The bouts the drafted operation would change, shown for review before the operation is confirmed.
     *
     * @param  SupportCollection<int, TournamentBoutData>  $bouts
     * @return SupportCollection<int, TournamentBoutData>
     */
    private function affectedBouts(Tournament $tournament, SupportCollection $bouts, TournamentBracketService $bracket): SupportCollection
    {
        $operation = TournamentOperation::tryFrom($this->draft->operation);

        if (in_array($operation, [TournamentOperation::Reset, TournamentOperation::Unbook], true) && $this->nodeId !== null) {
            $node = $this->node($tournament);
            $ids = $bracket->descendantNodeIds($node);

            if ($operation === TournamentOperation::Unbook) {
                $ids[] = $node->id;
            }

            return $bouts->only($ids);
        }

        if ($operation?->target() === TournamentOperationTarget::Entry && $this->entryId !== null) {
            return $bouts->filter(fn (TournamentBoutData $bout): bool => $bout->first?->id === $this->entryId || $bout->second?->id === $this->entryId);
        }

        if ($operation === TournamentOperation::Cancel) {
            return $bouts->filter(fn (TournamentBoutData $bout): bool => $bout->link instanceof TournamentMatchLink && $bout->link->match->match_finish === null);
        }

        return collect();
    }

    /**
     * The first bout that is ready to book: both entrants known, still in the tournament and bookable.
     *
     * @param  SupportCollection<int, TournamentBoutData>  $bouts
     * @param  array<int, int>  $availableIds
     */
    private function nextBout(SupportCollection $bouts, array $availableIds): ?TournamentBoutData
    {
        return $bouts->first(fn (TournamentBoutData $bout): bool => ! $bout->complete
            && $bout->inputsResolved
            && $bout->first instanceof TournamentEntry
            && $bout->second instanceof TournamentEntry
            && ! $bout->first->withdrawn
            && ! $bout->second->withdrawn
            && in_array($bout->first->competitor_id, $availableIds, true)
            && in_array($bout->second->competitor_id, $availableIds, true));
    }

    /** @param Closure(): mixed $operation */
    private function perform(Closure $operation): void
    {
        Gate::authorize('update', $this->tournament());

        try {
            $operation();
        } catch (BaseBusinessException $exception) {
            $this->addError('operation', $this->failureMessageKeepingInput($exception));

            return;
        }

        $this->refreshState();
        $this->notice = __('tournaments.saved');
    }

    private function tournament(): Tournament
    {
        return Tournament::query()->findOrFail($this->tournamentId);
    }

    private function node(Tournament $tournament): TournamentNode
    {
        return $tournament->nodes()->findOrFail($this->nodeId);
    }

    private function entry(Tournament $tournament): TournamentEntry
    {
        return $tournament->entries()->findOrFail($this->entryId);
    }
}
