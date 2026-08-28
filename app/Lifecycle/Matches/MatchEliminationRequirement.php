<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Data\Matches\MatchEliminationData;
use App\Data\Matches\MatchResultData;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Support\ConsecutiveIntegerSequence;
use Illuminate\Database\Eloquent\Collection;

final class MatchEliminationRequirement
{
    public function __construct(private readonly ConsecutiveIntegerSequence $sequence) {}

    /**
     * @param  Collection<int, MatchCompetitor>  $competitors
     */
    public function ensureSatisfied(
        EventMatch $match,
        MatchResultData $result,
        Collection $competitors,
    ): void {
        if (! $match->match_type->recordsIndividualEliminations()) {
            if ($result->eliminations->isNotEmpty()) {
                throw InvalidMatchOutcomeException::eliminationsNotSupported();
            }

            return;
        }

        $competitorsById = $competitors->keyBy('id');
        $eliminatedCompetitorIds = $result->eliminations
            ->map(fn (MatchEliminationData $elimination): int => $elimination->competitor->id);

        if ($eliminatedCompetitorIds->unique()->count() !== $eliminatedCompetitorIds->count()) {
            throw InvalidMatchOutcomeException::duplicateEliminatedCompetitor();
        }

        $orders = $result->eliminations
            ->map(fn (MatchEliminationData $elimination): int => $elimination->order)
            ->all();

        if (! $this->sequence->isValid($orders)) {
            throw InvalidMatchOutcomeException::invalidEliminationOrder();
        }

        foreach ($result->eliminations as $elimination) {
            $this->ensureParticipantsBelongToMatch($elimination, $competitorsById);
        }

        $this->ensureChronology($result);

        if (! $result->finish->requiresWinningSide()) {
            return;
        }

        $winningSideId = $result->winningSide?->id;
        $winningCompetitorIds = collect($competitors
            ->where('match_side_id', $winningSideId)
            ->modelKeys());

        if ($eliminatedCompetitorIds->intersect($winningCompetitorIds)->isNotEmpty()) {
            throw InvalidMatchOutcomeException::winnerEliminated();
        }

        $expectedEliminatedCompetitorIds = collect($competitors
            ->where('match_side_id', '!=', $winningSideId)
            ->modelKeys());

        if ($eliminatedCompetitorIds->sort()->values()->all() !== $expectedEliminatedCompetitorIds->sort()->values()->all()) {
            throw InvalidMatchOutcomeException::incompleteEliminationHistory();
        }
    }

    /**
     * @param  Collection<int, MatchCompetitor>  $competitorsById
     */
    private function ensureParticipantsBelongToMatch(
        MatchEliminationData $elimination,
        Collection $competitorsById,
    ): void {
        if (! $competitorsById->has($elimination->competitor->id)) {
            throw InvalidMatchOutcomeException::competitorFromAnotherMatch();
        }

        if ($elimination->eliminatedBy === null) {
            return;
        }

        if (! $competitorsById->has($elimination->eliminatedBy->id)) {
            throw InvalidMatchOutcomeException::eliminatorFromAnotherMatch();
        }

        if ($elimination->competitor->is($elimination->eliminatedBy)) {
            throw InvalidMatchOutcomeException::selfElimination();
        }
    }

    private function ensureChronology(MatchResultData $result): void
    {
        $eliminationOrders = $result->eliminations
            ->mapWithKeys(fn (MatchEliminationData $elimination): array => [
                $elimination->competitor->id => $elimination->order,
            ]);

        foreach ($result->eliminations as $elimination) {
            if ($elimination->eliminatedBy === null) {
                continue;
            }

            $eliminatorExitOrder = $eliminationOrders->get($elimination->eliminatedBy->id);

            if (is_int($eliminatorExitOrder) && $eliminatorExitOrder <= $elimination->order) {
                throw InvalidMatchOutcomeException::eliminationAfterEliminatorExited();
            }
        }
    }
}
