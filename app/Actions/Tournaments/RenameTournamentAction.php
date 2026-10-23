<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Exceptions\RecordChangedException;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Models\Tournaments\Tournament;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class RenameTournamentAction
{
    public function __construct(private readonly TournamentLocks $locks) {}

    public function handle(Tournament $tournament, int $version, string $name, ?string $description, User $actor): void
    {
        DB::transaction(function () use ($tournament, $version, $name, $description, $actor): void {
            $tournament = $this->locks->lock($tournament->id);
            if ($tournament->version !== $version) {
                throw RecordChangedException::changed();
            }

            $tournament->increment('version', extra: [
                'name' => mb_trim($name),
                'description' => $description,
            ]);

            activity('tournaments')
                ->causedBy($actor)
                ->withProperties([
                    'promotion_id' => $tournament->promotion_id,
                    'tournament_id' => $tournament->id,
                    'name' => $name,
                    'description' => $description,
                ])
                ->log('tournament.renamed');
        }, attempts: 3);
    }
}
