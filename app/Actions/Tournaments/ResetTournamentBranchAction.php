<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Exceptions\RecordChangedException;
use App\Lifecycle\Tournaments\TournamentBranchReset;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentOutcomeManager;
use App\Models\Tournaments\TournamentNode;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class ResetTournamentBranchAction
{
    public function __construct(private readonly TournamentLocks $locks, private readonly TournamentBranchReset $reset, private readonly TournamentOutcomeManager $outcomes) {}

    public function handle(TournamentNode $node, int $version, string $reason, User $actor, bool $includeCurrent = false): void
    {
        DB::transaction(function () use ($node, $version, $reason, $actor, $includeCurrent): void {
            $tournament = $this->locks->lock($node->tournament_id);

            if ($tournament->version !== $version) {
                throw RecordChangedException::changed();
            }

            if ($this->reset->reset($tournament, $node, $reason, $actor, $includeCurrent)) {
                $this->outcomes->synchronize($tournament, $actor);
            }
        }, attempts: 3);
    }
}
