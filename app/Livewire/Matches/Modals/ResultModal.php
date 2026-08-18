<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Modals;

use App\Actions\Matches\RecordResultAction;
use App\Data\Matches\MatchEliminationData;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Exceptions\BaseBusinessException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use LivewireUI\Modal\ModalComponent;

class ResultModal extends ModalComponent
{
    public int $matchId;

    public ?string $finish = null;

    public ?int $winningSideId = null;

    /** @var array<int, array{order: int|string|null, eliminatedById: int|string|null}> */
    public array $eliminations = [];

    public function mount(int $matchId): void
    {
        $this->matchId = $matchId;

        $match = $this->match();
        $this->finish = $match->match_finish?->value;
        $this->winningSideId = $match->winning_side_id;
        $this->eliminations = $match->competitors
            ->mapWithKeys(fn (MatchCompetitor $competitor): array => [
                $competitor->id => [
                    'order' => $competitor->elimination_order,
                    'eliminatedById' => $competitor->eliminated_by_match_competitor_id,
                ],
            ])
            ->all();
    }

    public function save(): void
    {
        $match = $this->match();
        Gate::authorize('update', $match);

        $competitorIds = $match->competitors->modelKeys();
        $sideIds = $match->sides->modelKeys();

        $validated = $this->validate([
            'finish' => ['required', Rule::enum(MatchFinish::class)],
            'winningSideId' => ['nullable', 'integer', Rule::in($sideIds)],
            'eliminations' => ['array'],
            'eliminations.*.order' => ['nullable', 'integer', 'min:1', 'distinct'],
            'eliminations.*.eliminatedById' => ['nullable', 'integer', Rule::in($competitorIds)],
        ]);

        $finish = MatchFinish::from($validated['finish']);
        $winningSide = $this->winningSide($match);

        try {
            resolve(RecordResultAction::class)->handle(
                $match,
                new MatchResultData(
                    finish: $finish,
                    winningSide: $winningSide,
                    eliminations: $this->eliminationData($match),
                ),
            );
        } catch (BaseBusinessException $exception) {
            $this->addError('outcome', $exception->getMessage());

            return;
        }

        $this->dispatch('refreshDatatable');
        $this->dispatch('closeModal');
    }

    public function updatedFinish(?string $finish): void
    {
        if ($finish === null || $finish === '') {
            return;
        }

        $matchFinish = MatchFinish::tryFrom($finish);

        if ($matchFinish !== null && ! $matchFinish->requiresWinningSide()) {
            $this->winningSideId = null;
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
        return $this->match()->sides
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
        return $this->match()->competitors
            ->mapWithKeys(fn (MatchCompetitor $competitor): array => [
                $competitor->id => $competitor->competitor->name,
            ])
            ->all();
    }

    public function getModalTitle(): string
    {
        return $this->match()->match_finish === null ? 'Record Match Result' : 'Correct Match Result';
    }

    public function render(): View
    {
        return view('livewire.matches.modals.result-modal');
    }

    private function winningSide(EventMatch $match): ?MatchSide
    {
        if ($this->winningSideId === null) {
            return null;
        }

        return $match->sides->sole('id', $this->winningSideId);
    }

    /** @return Collection<int, MatchEliminationData> */
    private function eliminationData(EventMatch $match): Collection
    {
        return collect($this->eliminations)
            ->filter(fn (array $elimination): bool => is_numeric($elimination['order']))
            ->map(function (array $elimination, int $competitorId) use ($match): MatchEliminationData {
                $eliminatedById = $elimination['eliminatedById'];

                return new MatchEliminationData(
                    competitor: $match->competitors->sole('id', $competitorId),
                    order: (int) $elimination['order'],
                    eliminatedBy: is_numeric($eliminatedById)
                        ? $match->competitors->sole('id', (int) $eliminatedById)
                        : null,
                );
            })
            ->values();
    }
}
