<?php

declare(strict_types=1);

namespace App\Livewire\Tournaments\Components;

use App\Actions\Tournaments\SaveTournamentAction;
use App\Enums\Roster\BookableRosterKind;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ExplainsKeptInput;
use App\Livewire\Concerns\ResolvesActor;
use App\Livewire\Matches\Support\BookableRosterSearch;
use App\Livewire\Tournaments\Forms\CreateEdit;
use App\Models\Promotions\Promotion;
use App\Models\Tournaments\Tournament;
use App\Queries\Tournaments\TournamentIndexQuery;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ExplainsKeptInput;
    use ResolvesActor;
    use WithPagination;

    public CreateEdit $form;

    public bool $creating = false;

    public function start(): void
    {
        Gate::authorize('create', Tournament::class);
        $this->form->requestId ??= Str::uuid()->toString();
        $this->creating = true;
    }

    public function move(int $index, int $direction): void
    {
        Gate::authorize('create', Tournament::class);
        $this->form->move($index, $direction);
    }

    public function updatedFormKind(): void
    {
        $this->form->entrants = [];
    }

    public function save(SaveTournamentAction $save, PromotionContextService $context): void
    {
        Gate::authorize('create', Tournament::class);
        $promotion = $context->current();

        if (! $promotion instanceof Promotion) {
            $this->addError('operation', __('tournaments.choose_promotion'));

            return;
        }

        $data = $this->form->data();

        try {
            $tournament = $save->handle($promotion, $data, $this->actor());
        } catch (BaseBusinessException $exception) {
            $this->addError('operation', $this->failureMessageKeepingInput($exception));

            return;
        }

        $this->redirectRoute('tournaments.show', ['tournament' => $tournament]);
    }

    /** @return array<int, array{id:int|string,name:string}> */
    #[Renderless]
    public function searchRoster(PromotionContextService $context, BookableRosterSearch $search, string $kind, string $term): array
    {
        Gate::authorize('create', Tournament::class);
        $rosterKind = BookableRosterKind::tryFrom($kind);

        if (! in_array($rosterKind, [BookableRosterKind::Wrestlers, BookableRosterKind::TagTeams], true)) {
            return [];
        }

        $promotion = $context->current();

        return $promotion instanceof Promotion ? $search->search($rosterKind, $term, $promotion->id) : [];
    }

    public function render(TournamentIndexQuery $read, BookableRosterSearch $search): View
    {
        Gate::authorize('viewAny', Tournament::class);
        $kind = $this->form->kind === 'tag_team' ? BookableRosterKind::TagTeams : BookableRosterKind::Wrestlers;

        return view('livewire.tournaments.components.index', [
            'tournaments' => $read->get()->paginate(20),
            'labels' => $search->labels($kind, $this->form->entrants),
            'kind' => $kind,
        ]);
    }
}
