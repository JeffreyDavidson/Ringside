<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Data\Matches\MatchEliminationData;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use Illuminate\Database\Eloquent\Collection;

class MatchOutcomeRequirements
{
    /**
     * @param  Collection<int, MatchCompetitor>  $competitors
     */
    public function ensureSatisfied(EventMatch $match, MatchResultData $result, Collection $competitors): void
    {
        $this->ensureWinningSideIsValid($match, $result);
        $this->ensureEntryOrderIsValid($match, $competitors);
        $this->ensureEliminationsAreValid($match, $result, $competitors);
    }

    private function ensureWinningSideIsValid(EventMatch $match, MatchResultData $result): void
    {
        if ($result->finish->requiresWinningSide() && $result->winningSide === null) {
            throw InvalidMatchOutcomeException::missingWinningSide();
        }

        if (! $result->finish->requiresWinningSide() && $result->winningSide !== null) {
            throw InvalidMatchOutcomeException::unexpectedWinningSide();
        }

        if ($result->winningSide === null) {
            return;
        }

        if ($result->winningSide->match_id !== $match->id) {
            throw InvalidMatchOutcomeException::winningSideFromAnotherMatch();
        }

        if (! $result->winningSide->competitors()->exists()) {
            throw InvalidMatchOutcomeException::winningSideWithoutCompetitors();
        }
    }

    /**
     * @param  Collection<int, MatchCompetitor>  $competitors
     */
    private function ensureEntryOrderIsValid(EventMatch $match, Collection $competitors): void
    {
        if ($match->match_type !== MatchType::RoyalRumble) {
            return;
        }

        $entryOrders = $competitors
            ->map(fn (MatchCompetitor $competitor): ?int => $competitor->entry_order)
            ->all();

        if (! $this->isConsecutiveSequence($entryOrders, $competitors->count())) {
            throw InvalidMatchOutcomeException::invalidEntryOrder();
        }
    }

    /**
     * @param  Collection<int, MatchCompetitor>  $competitors
     */
    private function ensureEliminationsAreValid(
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

        if (! $this->isConsecutiveSequence($orders, $result->eliminations->count())) {
            throw InvalidMatchOutcomeException::invalidEliminationOrder();
        }

        foreach ($result->eliminations as $elimination) {
            $this->ensureEliminationParticipantsBelongToMatch($elimination, $competitorsById);
        }

        $this->ensureEliminationChronology($result);

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
    private function ensureEliminationParticipantsBelongToMatch(
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

    private function ensureEliminationChronology(MatchResultData $result): void
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

    /**
     * @param  array<int, int|null>  $values
     */
    private function isConsecutiveSequence(array $values, int $count): bool
    {
        if ($count === 0) {
            return $values === [];
        }

        sort($values);

        return $values === range(1, $count);
    }
}
