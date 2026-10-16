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
use App\Services\Tournaments\TournamentBracketService;
use Closure;
use Illuminate\Database\Eloquent\Collection;
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
        $this->perform(fn () => $rename->handle($tournament, $this->version, $this->form->name, $this->form->description));
    }

    public function begin(string $operation, int $id = 0): void
    {
        $tournament = $this->tournament();
        Gate::authorize('update', $tournament);
        Validator::make(['operation' => $operation], ['operation' => [Rule::in(['book', 'plan', 'reset', 'unbook', 'withdraw', 'replace', 'walkover', 'cancel'])]])->validate();
        $this->nodeId = null;
        $this->entryId = null;
        if (in_array($operation, ['withdraw', 'replace'], true)) {
            $this->entryId = $tournament->entries()->findOrFail($id)->id;
        }
        if (in_array($operation, ['book', 'plan', 'reset', 'unbook', 'walkover'], true)) {
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

    public function confirm(): void
    {
        $tournament = $this->tournament();
        Gate::authorize('update', $tournament);
        Validator::make(['operation' => $this->draftOperation], ['operation' => [Rule::in(['book', 'plan', 'reset', 'unbook', 'withdraw', 'replace', 'walkover', 'cancel'])]])->validate();
        $reasonRequired = in_array($this->draftOperation, ['reset', 'unbook', 'withdraw', 'replace', 'walkover', 'cancel'], true);
        if ($reasonRequired) {
            Validator::make(['reason' => $this->reason, 'confirmed' => $this->confirmed], ['reason' => ['required', 'string', 'max:2000'], 'confirmed' => ['accepted']])->validate();
        }
        if (in_array($this->draftOperation, ['book', 'plan'], true)) {
            Validator::make(['eventId' => $this->eventId], ['eventId' => [$this->draftOperation === 'book' ? 'required' : 'nullable', 'integer', Rule::exists('events', 'id')->where('promotion_id', $tournament->promotion_id)->whereNull('deleted_at')]])->validate();
        }
        if ($this->draftOperation === 'book') {
            Validator::make(['refereeIds' => $this->refereeIds, 'titleId' => $this->titleId, 'stipulationId' => $this->stipulationId], ['refereeIds' => ['required', 'list', 'min:1'], 'refereeIds.*' => ['integer', 'distinct', Rule::exists('referees', 'id')->where('promotion_id', $tournament->promotion_id)], 'titleId' => ['nullable', 'integer', Rule::exists('titles', 'id')->where('promotion_id', $tournament->promotion_id)], 'stipulationId' => ['nullable', 'integer', Rule::exists('matches_stipulations', 'id')]])->validate();
        }
        if ($this->draftOperation === 'replace') {
            Validator::make(['replacementId' => $this->replacementId], ['replacementId' => ['required', 'integer', Rule::exists((new ($tournament->entrant_kind->modelClass()))->getTable(), 'id')->where('promotion_id', $tournament->promotion_id)]])->validate();
        }
        $this->perform(function () use ($tournament): void {
            match (TournamentOperation::from($this->draftOperation)) {
                TournamentOperation::Book => app(BookTournamentMatchAction::class)->handle($this->node($tournament), Event::query()->findOrFail($this->eventId), Referee::query()->whereKey($this->refereeIds)->get(), $this->draftVersion, Title::query()->whereKey($this->titleId ?? [])->get(), $this->stipulationId === null ? null : MatchStipulation::query()->findOrFail($this->stipulationId)),
                TournamentOperation::Plan => app(PlanTournamentBoutAction::class)->handle($this->node($tournament), $this->eventId === null ? null : Event::query()->findOrFail($this->eventId), $this->draftVersion),
                TournamentOperation::Reset => app(ResetTournamentBranchAction::class)->handle($this->node($tournament), $this->draftVersion, $this->reason),
                TournamentOperation::Unbook => app(ResetTournamentBranchAction::class)->handle($this->node($tournament), $this->draftVersion, $this->reason, true),
                TournamentOperation::Withdraw => app(WithdrawTournamentEntryAction::class)->handle($this->entry($tournament), $this->draftVersion, $this->reason),
                TournamentOperation::Replace => app(ReplaceTournamentEntryAction::class)->handle($this->entry($tournament), (int) $this->replacementId, $this->draftVersion, $this->reason),
                TournamentOperation::Walkover => app(AwardTournamentWalkoverAction::class)->handle($this->node($tournament), $this->draftVersion, $this->reason),
                TournamentOperation::Cancel => app(CancelTournamentAction::class)->handle($tournament, $this->draftVersion, $this->reason),
            };
        });
    }

    /** @return array<int, array{id:int|string,name:string}> */
    #[Renderless]
    public function searchRoster(string $kind, string $term): array
    {
        $tournament = $this->tournament();
        Gate::authorize('update', $tournament);
        $rosterKind = BookableRosterKind::tryFrom($kind);

        return $rosterKind === null ? [] : app(BookableRosterSearch::class)->search($rosterKind, $term, $tournament->promotion_id);
    }

    public function render(): View
    {
        $tournament = $this->tournament();
        Gate::authorize('view', $tournament);
        $bracket = app(TournamentBracketService::class);
        $bouts = $bracket->bouts($tournament);
        $tournament->setRelation('nodes', new Collection($bouts->map(fn (TournamentBoutData $bout): TournamentNode => $bout->node)->all()));
        $kind = $tournament->entrant_kind->value === 'tag_team' ? BookableRosterKind::TagTeams : BookableRosterKind::Wrestlers;
        $affected = collect();
        if (in_array($this->draftOperation, ['reset', 'unbook'], true) && $this->nodeId !== null) {
            $node = $this->node($tournament);
            $ids = $bracket->descendantNodeIds($node);
            if ($this->draftOperation === 'unbook') {
                $ids[] = $node->id;
            } $affected = $bouts->only($ids);
        }
        if (in_array($this->draftOperation, ['withdraw', 'replace'], true) && $this->entryId !== null) {
            $affected = $bouts->filter(fn (TournamentBoutData $bout): bool => $bout->first?->id === $this->entryId || $bout->second?->id === $this->entryId);
        }
        if ($this->draftOperation === 'cancel') {
            $affected = $bouts->filter(fn (TournamentBoutData $bout): bool => $bout->link instanceof TournamentMatchLink && $bout->link->match->match_finish === null);
        }
        $labels = app(BookableRosterSearch::class)->labels($kind, $this->form->entrants);
        $entries = $tournament->entries()->with('competitor')->get();
        $availableIds = $tournament->entrant_kind->modelClass()::query()->whereKey($entries->map(fn (TournamentEntry $entry): int => $entry->competitor_id))->bookable()->pluck('id')->all();

        return view('livewire.tournaments.components.show', ['tournament' => $tournament, 'bouts' => $bouts, 'availableIds' => $availableIds, 'nextBout' => $bouts->first(fn (TournamentBoutData $bout): bool => ! $bout->complete && $bout->inputsResolved && $bout->first instanceof TournamentEntry && $bout->second instanceof TournamentEntry && ! $bout->first->withdrawn && ! $bout->second->withdrawn && in_array($bout->first->competitor_id, $availableIds, true) && in_array($bout->second->competitor_id, $availableIds, true)), 'kind' => $kind, 'labels' => $labels, 'entries' => $entries, 'events' => Event::query()->where('promotion_id', $tournament->promotion_id)->orderByDesc('date')->orderBy('id')->get(), 'titles' => Title::query()->where('promotion_id', $tournament->promotion_id)->orderBy('name')->get(), 'stipulations' => MatchStipulation::query()->orderBy('name')->get(), 'refereeLabels' => app(BookableRosterSearch::class)->labels(BookableRosterKind::Referees, $this->refereeIds), 'replacementLabels' => app(BookableRosterSearch::class)->labels($kind, [$this->replacementId]), 'affected' => $affected, 'selected' => $this->nodeId === null ? null : $bouts->get($this->nodeId), 'view' => $this->viewMode === 'bracket' ? 'bracket' : 'rounds']);
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
