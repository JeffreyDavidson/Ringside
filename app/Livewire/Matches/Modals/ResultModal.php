<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Modals;

use App\Actions\Matches\RecordResultAction;
use App\Enums\MatchFinish;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Matches\Forms\MatchResultForm;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use LivewireUI\Modal\ModalComponent;

/**
 * @property-read EventMatch $match
 * @property-read array<string, string> $finishOptions
 * @property-read array<int, string> $sideOptions
 * @property-read array<int, string> $competitorOptions
 */
class ResultModal extends ModalComponent
{
    #[Locked]
    public int $matchId;

    public MatchResultForm $form;

    public function mount(int $matchId): void
    {
        $this->matchId = $matchId;

        Gate::authorize('update', $this->match);

        $this->form->fillFrom($this->match);
    }

    public function save(RecordResultAction $recordResultAction): void
    {
        $match = $this->match;
        Gate::authorize('update', $match);

        $this->form->validateFor($match);

        try {
            $recordResultAction->handle(
                $match,
                $this->form->toData($match),
            );
        } catch (BaseBusinessException $exception) {
            $this->addError('outcome', $exception->getMessage());

            return;
        } finally {
            // The action refreshes the match, which drops its nested competitor relations; reload them on the next read.
            unset($this->match);
        }

        $this->dispatch('refreshDatatable');
        $this->dispatch('closeModal');
    }

    public function updatedFormFinish(?string $finish): void
    {
        if ($finish === null || $finish === '') {
            return;
        }

        $matchFinish = MatchFinish::tryFrom($finish);

        if ($matchFinish !== null && ! $matchFinish->requiresWinningSide()) {
            $this->form->winningSideId = null;
        }
    }

    #[Computed]
    public function match(): EventMatch
    {
        return EventMatch::query()
            ->with(['sides.competitors.competitor', 'competitors.competitor'])
            ->findOrFail($this->matchId);
    }

    /** @return array<string, string> */
    #[Computed]
    public function finishOptions(): array
    {
        return MatchFinish::options();
    }

    /** @return array<int, string> */
    #[Computed]
    public function sideOptions(): array
    {
        return $this->match->sides
            ->mapWithKeys(fn (MatchSide $side): array => [
                $side->id => $side->competitors
                    ->map(fn (MatchCompetitor $competitor): string => $competitor->competitor->name)
                    ->join(' & '),
            ])
            ->all();
    }

    /** @return array<int, string> */
    #[Computed]
    public function competitorOptions(): array
    {
        return $this->match->competitors
            ->mapWithKeys(fn (MatchCompetitor $competitor): array => [
                $competitor->id => $competitor->competitor->name,
            ])
            ->all();
    }

    public function getModalTitle(): string
    {
        return $this->match->match_finish === null
            ? __('matches.modal.record_result')
            : __('matches.modal.correct_result');
    }

    public function render(): View
    {
        return view('livewire.matches.modals.result-modal');
    }
}
