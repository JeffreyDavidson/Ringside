<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Exceptions\RecordChangedException;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Models\Tournaments\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RenameTournamentAction
{
    public function __construct(private readonly TournamentLocks $locks) {}

    public function handle(Tournament $tournament, int $version, string $name, ?string $description): void
    {
        Validator::make(['name' => $name, 'description' => $description], ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000']])->validate();
        DB::transaction(function () use ($tournament, $version, $name, $description): void {
            $tournament = $this->locks->lock($tournament->id);
            if ($tournament->version !== $version) {
                throw RecordChangedException::changed();
            }
            $tournament->forceFill(['name' => mb_trim($name), 'description' => $description, 'version' => $tournament->version + 1])->save();
            activity('tournaments')->withProperties(['promotion_id' => $tournament->promotion_id, 'tournament_id' => $tournament->id, 'name' => $name, 'description' => $description])->log('tournament.renamed');
        }, attempts: 3);
    }
}
