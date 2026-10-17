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
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ExplainsKeptInput;
use App\Livewire\Matches\Support\BookableRosterSearch;
use App\Livewire\Tournaments\Forms\CreateEdit;
use App\Models\Events\Event;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\Title;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use App\Queries\Tournaments\TournamentShowQuery;
use App\Services\Tournaments\TournamentBracketService;
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

    /** Operations that act on one bracket node. */
    private const array NODE_OPERATIONS = [
        TournamentOperation::Book,
        TournamentOperation::Plan,
        TournamentOperation::Reset,
        TournamentOperation::Unbook,
        TournamentOperation::Walkover,
    ];

    /** Operations that act on one tournament entry. */
    private const array ENTRY_OPERATIONS = [
        TournamentOperation::Withdraw,
        TournamentOperation::Replace,
    ];

    /** Operations that need a written reason and an explicit confirmation. */
    private const array REASON_OPERATIONS = [
        TournamentOperation::Reset,
        TournamentOperation::Unbook,
        TournamentOperation::Withdraw,
        TournamentOperation::Replace,
        TournamentOperation::Walkover,
        TournamentOperation::Cancel,
    ];

    #[Locked]
    public int $tournamentId;

    #[Locked]
    public int $version = 0;

    #[Locked]
    public string $draftOperation = '';

    #[Locked]
    public ?int $nodeId = null;

    #[Locked]
    public ?int $entryId = null;

    #[Locked]
    public int $draftVersion = 0;

    public CreateEdit $form;

    public string $viewMode = 'rounds';

    public ?int $eventId = null;

    /** @var list<int|string> */
    public array $refereeIds = [];

    public ?int $titleId = null;

    public ?int $stipulationId = null;

    public ?int $replacementId = null;

    public string $reason = '';

    public bool $confirmed = false;

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
        $this->draftOperation = '';
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
        $this->perform(fn (): Tournament => $save->handle($tournament->promotion()->firstOrFail(), $data, $tournament, $this->version));
    }

    public function rename(RenameTournamentAction $rename): void
    {
        $tournament = $this->tournament();
        Gate::authorize('update', $tournament);
        $this->form->validateNaming();
        $this->perform(fn () => $rename->handle($tournament, $this->version, $this->form->name, $this->form->description));
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
        if (in_array($draft, self::ENTRY_OPERATIONS, true)) {
            $this->entryId = $tournament->entries()->findOrFail($id)->id;
        }
        if (in_array($draft, self::NODE_OPERATIONS, true)) {
            $node = $tournament->nodes()->findOrFail($id);
            $this->nodeId = $node->id;
            $this->eventId = $node->planned_event_id;
        }
        $this->draftOperation = $operation;
        $this->draftVersion = $this->version;
        $this->reason = '';
        $this->confirmed = false;
        $this->refereeIds = [];
        $this->titleId = null;
        $this->stipulationId = null;
        $this->replacementId = null;
        $this->notice = null;
        $this->resetErrorBag();
        $this->dispatch('tournament-panel-opened');
    }

    public function cancelDraft(): void
    {
        Gate::authorize('view', $this->tournament());
        $this->draftOperation = '';
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
            ['operation' => $this->draftOperation],
            ['operation' => [Rule::enum(TournamentOperation::class)]],
        )->validate();

        $operation = TournamentOperation::from($this->draftOperation);
        $this->validateDraft($tournament, $operation);
        $this->perform(function () use ($tournament, $operation, $book, $plan, $reset, $withdraw, $replace, $walkover, $cancel): void {
            match ($operation) {
                TournamentOperation::Book => $book->handle(
                    $this->node($tournament),
                    Event::query()->findOrFail($this->eventId),
                    Referee::query()->whereKey($this->refereeIds)->get(),
                    $this->draftVersion,
                    Title::query()->whereKey($this->titleId ?? [])->get(),
                    $this->stipulationId === null ? null : MatchStipulation::query()->findOrFail($this->stipulationId),
                ),
                TournamentOperation::Plan => $plan->handle(
                    $this->node($tournament),
                    $this->eventId === null ? null : Event::query()->findOrFail($this->eventId),
                    $this->draftVersion,
                ),
                TournamentOperation::Reset => $reset->handle($this->node($tournament), $this->draftVersion, $this->reason),
                TournamentOperation::Unbook => $reset->handle($this->node($tournament), $this->draftVersion, $this->reason, true),
                TournamentOperation::Withdraw => $withdraw->handle($this->entry($tournament), $this->draftVersion, $this->reason),
                TournamentOperation::Replace => $replace->handle(
                    $this->entry($tournament),
                    (int) $this->replacementId,
                    $this->draftVersion,
                    $this->reason,
                ),
                TournamentOperation::Walkover => $walkover->handle($this->node($tournament), $this->draftVersion, $this->reason),
                TournamentOperation::Cancel => $cancel->handle($tournament, $this->draftVersion, $this->reason),
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

    public function render(TournamentBracketService $bracket, TournamentShowQuery $read, BookableRosterSearch $search): View
    {
        $tournament = $this->tournament();
        Gate::authorize('view', $tournament);
        $bouts = $bracket->bouts($tournament);
        $tournament->setRelation('nodes', new Collection($bouts->map(fn (TournamentBoutData $bout): TournamentNode => $bout->node)->all()));
        $kind = $tournament->entrant_kind === EntrantKind::TagTeam ? BookableRosterKind::TagTeams : BookableRosterKind::Wrestlers;
        $reads = $read->get($tournament);

        return view('livewire.tournaments.components.show', [
            'tournament' => $tournament,
            'bouts' => $bouts,
            'availableIds' => $reads['availableIds'],
            'nextBout' => $this->nextBout($bouts, $reads['availableIds']),
            'kind' => $kind,
            'labels' => $search->labels($kind, $this->form->entrants),
            'entries' => $reads['entries'],
            'events' => $reads['events'],
            'titles' => $reads['titles'],
            'stipulations' => $reads['stipulations'],
            'refereeLabels' => $search->labels(BookableRosterKind::Referees, $this->refereeIds),
            'replacementLabels' => $search->labels($kind, [$this->replacementId]),
            'affected' => $this->affectedBouts($tournament, $bouts, $bracket),
            'selected' => $this->nodeId === null ? null : $bouts->get($this->nodeId),
            'view' => $this->viewMode === 'bracket' ? 'bracket' : 'rounds',
        ]);
    }

    /** Checks the fields the drafted operation needs before any Action runs. */
    private function validateDraft(Tournament $tournament, TournamentOperation $operation): void
    {
        if (in_array($operation, self::REASON_OPERATIONS, true)) {
            Validator::make(
                ['reason' => $this->reason, 'confirmed' => $this->confirmed],
                ['reason' => ['required', 'string', 'max:2000'], 'confirmed' => ['accepted']],
            )->validate();
        }
        if (in_array($operation, [TournamentOperation::Book, TournamentOperation::Plan], true)) {
            Validator::make(
                ['eventId' => $this->eventId],
                ['eventId' => [
                    $operation === TournamentOperation::Book ? 'required' : 'nullable',
                    'integer',
                    Rule::exists('events', 'id')->where('promotion_id', $tournament->promotion_id)->whereNull('deleted_at'),
                ]],
            )->validate();
        }
        if ($operation === TournamentOperation::Book) {
            Validator::make(
                ['refereeIds' => $this->refereeIds, 'titleId' => $this->titleId, 'stipulationId' => $this->stipulationId],
                [
                    'refereeIds' => ['required', 'list', 'min:1'],
                    'refereeIds.*' => [
                        'integer',
                        'distinct',
                        Rule::exists('referees', 'id')->where('promotion_id', $tournament->promotion_id),
                    ],
                    'titleId' => ['nullable', 'integer', Rule::exists('titles', 'id')->where('promotion_id', $tournament->promotion_id)],
                    'stipulationId' => ['nullable', 'integer', Rule::exists('matches_stipulations', 'id')],
                ],
            )->validate();
        }
        if ($operation === TournamentOperation::Replace) {
            Validator::make(
                ['replacementId' => $this->replacementId],
                ['replacementId' => [
                    'required',
                    'integer',
                    Rule::exists((new ($tournament->entrant_kind->modelClass()))->getTable(), 'id')->where('promotion_id', $tournament->promotion_id),
                ]],
            )->validate();
        }
    }

    /**
     * The bouts the drafted operation would change, shown for review before the operation is confirmed.
     *
     * @param  SupportCollection<int, TournamentBoutData>  $bouts
     * @return SupportCollection<int, TournamentBoutData>
     */
    private function affectedBouts(Tournament $tournament, SupportCollection $bouts, TournamentBracketService $bracket): SupportCollection
    {
        if (in_array($this->draftOperation, ['reset', 'unbook'], true) && $this->nodeId !== null) {
            $node = $this->node($tournament);
            $ids = $bracket->descendantNodeIds($node);
            if ($this->draftOperation === 'unbook') {
                $ids[] = $node->id;
            }

            return $bouts->only($ids);
        }
        if (in_array($this->draftOperation, ['withdraw', 'replace'], true) && $this->entryId !== null) {
            return $bouts->filter(fn (TournamentBoutData $bout): bool => $bout->first?->id === $this->entryId || $bout->second?->id === $this->entryId);
        }
        if ($this->draftOperation === 'cancel') {
            return $bouts->filter(fn (TournamentBoutData $bout): bool => $bout->link instanceof TournamentMatchLink && $bout->link->match->match_finish === null);
        }

        return collect();
    }

    /**
     * The first bout that is ready to book: both entrants known, still in the tournament and bookable.
     *
     * @param  SupportCollection<int, TournamentBoutData>  $bouts
     * @param  list<int>  $availableIds
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
