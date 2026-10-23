<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Enums\Events\LiveItemStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentOutcomeManager;
use App\Models\Events\EventLiveRun;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use App\Models\Users\User;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Support\Facades\DB;

class ResetTournamentBranchAction
{
    public function __construct(private readonly TournamentLocks $locks, private readonly TournamentBracketService $bracket, private readonly DeletionStateManager $deletion, private readonly TournamentOutcomeManager $outcomes) {}

    public function handle(TournamentNode $node, int $version, string $reason, User $actor, bool $includeCurrent = false): void
    {
        DB::transaction(function () use ($node, $version, $reason, $actor, $includeCurrent): void {
            $tournament = $this->locks->lock($node->tournament_id);

            if ($tournament->version !== $version) {
                throw RecordChangedException::changed();
            }

            $node = $tournament->nodes()->findOrFail($node->id);
            $ids = $this->bracket->descendantNodeIds($node);

            if ($includeCurrent) {
                $ids[] = $node->id;
            }

            $links = TournamentMatchLink::query()
                ->whereIn('node_id', $ids)
                ->whereNotNull('active_node_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->with('match')
                ->get();

            if ($links->isEmpty() && ! TournamentNode::query()->whereKey($ids)->whereNotNull('walkover_entry_id')->exists()) {
                return;
            }

            foreach ($links as $link) {
                $match = $link->match->refreshForUpdate();
                $run = EventLiveRun::query()
                    ->forEventId($match->event_id)
                    ->lockForUpdate()
                    ->first();

                $item = collect($run?->content['items'] ?? [])->firstWhere('key', 'match:'.$match->id);

                if ($match->match_finish !== null || ($item !== null && $item['state'] !== LiveItemStatus::Pending->value)) {
                    throw TournamentException::playedDependency();
                }
            }

            foreach ($links as $link) {
                $this->deletion->delete($link->match, now());
                $link->forceFill(['active_node_id' => null])->save();
            }

            TournamentNode::query()->whereKey($ids)->update(['walkover_entry_id' => null, 'walkover_reason' => null]);

            activity('tournaments')
                ->causedBy($actor)
                ->withProperties([
                    'promotion_id' => $tournament->promotion_id,
                    'tournament_id' => $tournament->id,
                    'node_ids' => $ids,
                    'match_ids' => $links->pluck('match_id')->all(),
                    'reason' => $reason,
                ])
                ->log('tournament.reset');

            $this->outcomes->synchronize($tournament, $actor);
        }, attempts: 3);
    }
}
