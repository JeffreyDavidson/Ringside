<?php

declare(strict_types=1);

namespace App\Services\Tournaments;

use App\Data\Tournaments\TournamentBoutData;
use App\Models\Matches\MatchCompetitor;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentNode;
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
        foreach ($nodes as $node) {
            $first = $node->round === 1 ? $entries->get($node->first_seed) : $positions->get(($node->round - 1).':'.($node->position * 2 - 1))?->winner;
            $second = $node->round === 1 ? $entries->get($node->second_seed) : $positions->get(($node->round - 1).':'.($node->position * 2))?->winner;
            $resolved = $node->round === 1 || ($positions->get(($node->round - 1).':'.($node->position * 2 - 1))?->complete && $positions->get(($node->round - 1).':'.($node->position * 2))?->complete);
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
        $ids = [];
        $positions = [$node->position];
        for ($round = $node->round - 1; $round >= 1; $round--) {
            $positions = array_merge(...array_map(fn (int $position): array => [$position * 2 - 1, $position * 2], $positions));
            $ids = array_merge($ids, TournamentNode::query()->where('tournament_id', $node->tournament_id)->where('round', $round)->whereIn('position', $positions)->get()->map(fn (TournamentNode $ancestor): int => $ancestor->id)->all());
        }

        return $ids;
    }

    /** @return list<int> */
    public function descendantNodeIds(TournamentNode $node): array
    {
        $ids = [];
        $position = $node->position;
        for ($round = $node->round + 1; $round <= (int) log($node->tournament()->firstOrFail()->capacity, 2); $round++) {
            $position = (int) ceil($position / 2);
            $id = TournamentNode::query()->where('tournament_id', $node->tournament_id)->where('round', $round)->where('position', $position)->first()?->id;
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
