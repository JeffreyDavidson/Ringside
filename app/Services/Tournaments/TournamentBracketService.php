<?php

declare(strict_types=1);

namespace App\Services\Tournaments;

use App\Data\Tournaments\TournamentBoutData;
use App\Models\Matches\MatchCompetitor;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentNode;
use App\ValueObjects\BracketShape;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class TournamentBracketService
{
    /** @return Collection<int, TournamentBoutData> */
    public function bouts(Tournament $tournament): Collection
    {
        $entries = $tournament->entries()->with('competitor')->get()->keyBy('seed');
        $nodes = $tournament->nodes()->with(['links.match.event' => fn (Relation $query) => $query->getQuery()->withoutGlobalScope(SoftDeletingScope::class), 'links.match.winningSide.competitors', 'plannedEvent'])->get();
        /** @var Collection<string, TournamentBoutData> $positions */
        $positions = collect();
        /** @var Collection<int, TournamentBoutData> $bouts */
        $bouts = collect();
        $shape = new BracketShape($tournament->capacity);

        foreach ($nodes as $node) {
            $feeders = array_map(
                fn (array $feeder): ?TournamentBoutData => $positions->get("{$feeder[0]}:{$feeder[1]}"),
                $shape->feeders($node->round, $node->position),
            );
            $firstFeeder = $feeders[0] ?? null;
            $secondFeeder = $feeders[1] ?? null;
            $first = $node->round === 1 ? $entries->get($node->first_seed) : $firstFeeder?->winner;
            $second = $node->round === 1 ? $entries->get($node->second_seed) : $secondFeeder?->winner;
            $resolved = $node->round === 1 || ($firstFeeder?->complete && $secondFeeder?->complete);
            $link = $node->links->firstWhere('active_node_id', $node->id);
            $winner = null;
            $complete = false;
            $bye = false;

            if ($resolved && ($first === null || $second === null)) {
                $winner = $first ?? $second;
                $complete = true;
                $bye = $winner !== null;
            }

            if ($node->walkover_entry_id !== null) {
                $winner = $entries->firstWhere('id', $node->walkover_entry_id);
                $complete = true;
            }

            if ($link !== null && ! $link->match->trashed() && $link->match->match_finish?->requiresWinningSide()) {
                $competitor = $link->match->winningSide?->competitors->first();
                $winner = $competitor instanceof MatchCompetitor ? $entries->first(fn (TournamentEntry $entry): bool => $entry->competitor_type === $competitor->competitor_type && $entry->competitor_id === $competitor->competitor_id) : null;
                $complete = $winner !== null;
            }

            $bout = new TournamentBoutData($node, $first, $second, (bool) $resolved, $link, $winner, $complete, $bye);
            $positions->put($node->round.':'.$node->position, $bout);
            $bouts->put($node->id, $bout);
        }

        return $bouts;
    }

    public function bout(TournamentNode $node): TournamentBoutData
    {
        return $this->bouts($node->tournament()->firstOrFail())->sole(fn (TournamentBoutData $bout): bool => $bout->node->id === $node->id);
    }

    /** @return list<int> */
    public function ancestorNodeIds(TournamentNode $node): array
    {
        $ancestors = new BracketShape($node->tournament()->firstOrFail()->capacity)->ancestors($node->round, $node->position);

        return $this->nodeIdsAt($node, $ancestors, fn (Builder $query) => $query->orderByDesc('round')->orderBy('position'));
    }

    /** @return list<int> */
    public function descendantNodeIds(TournamentNode $node): array
    {
        $descendants = new BracketShape($node->tournament()->firstOrFail()->capacity)->descendants($node->round, $node->position);

        return $this->nodeIdsAt($node, $descendants, fn (Builder $query) => $query->orderBy('round'));
    }

    /**
     * The ids of the tournament's nodes at the given [round, position] pairs, in one query.
     *
     * @param  list<array{int, int}>  $coordinates
     * @return list<int>
     */
    private function nodeIdsAt(TournamentNode $node, array $coordinates, Closure $order): array
    {
        if ($coordinates === []) {
            return [];
        }

        $query = TournamentNode::query()
            ->where('tournament_id', $node->tournament_id)
            ->where(function (Builder $where) use ($coordinates): void {
                foreach ($coordinates as [$round, $position]) {
                    $where->orWhere(fn (Builder $pair) => $pair->where('round', $round)->where('position', $position));
                }
            });
        $order($query);

        return array_values($query->get()->map(fn (TournamentNode $found): int => $found->id)->all());
    }
}
