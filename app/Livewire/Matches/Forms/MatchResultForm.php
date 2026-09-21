<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Forms;

use App\Data\Matches\MatchEliminationData;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Form;

class MatchResultForm extends Form
{
    public ?string $finish = null;

    public ?int $winningSideId = null;

    /** @var array<int, array{order: int|string|null, eliminatedById: int|string|null}> */
    public array $eliminations = [];

    public function fillFrom(EventMatch $match): void
    {
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

    public function validateFor(EventMatch $match): void
    {
        $competitorIds = $match->competitors->modelKeys();
        $sideIds = $match->sides->modelKeys();
        $requiresWinningSide = MatchFinish::tryFrom($this->finish ?? '')?->requiresWinningSide();
        $competitorKeys = implode(',', $competitorIds);

        $this->validate([
            'finish' => ['required', Rule::enum(MatchFinish::class)],
            'winningSideId' => [
                'nullable',
                Rule::requiredIf($requiresWinningSide === true),
                Rule::prohibitedIf($requiresWinningSide === false),
                'integer',
                Rule::in($sideIds),
            ],
            'eliminations' => [
                "array:{$competitorKeys}",
                "required_array_keys:{$competitorKeys}",
            ],
            'eliminations.*' => ['array:order,eliminatedById', 'required_array_keys:order,eliminatedById'],
            'eliminations.*.order' => ['nullable', 'integer', 'min:1', 'distinct'],
            'eliminations.*.eliminatedById' => ['nullable', 'integer', Rule::in($competitorIds)],
        ]);
    }

    public function toData(EventMatch $match): MatchResultData
    {
        return new MatchResultData(
            finish: MatchFinish::from((string) $this->finish),
            winningSide: $this->winningSide($match),
            eliminations: $this->eliminationData($match),
        );
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
