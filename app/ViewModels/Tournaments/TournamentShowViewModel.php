<?php

declare(strict_types=1);

namespace App\ViewModels\Tournaments;

use App\Models\Matches\MatchStipulation;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds what the tournament screen lists besides the bracket: the entries, which of their competitors can
 * still be booked, and the titles and stipulations a bout can be booked with.
 *
 * @phpstan-type TournamentShowData array{
 *     entries: Collection<int, TournamentEntry>,
 *     availableIds: array<int, int>,
 *     titles: Collection<int, Title>,
 *     stipulations: Collection<int, MatchStipulation>
 * }
 */
class TournamentShowViewModel
{
    /** @return TournamentShowData */
    public function data(Tournament $tournament): array
    {
        $entries = $tournament->entries()
            ->with('competitor')
            ->get();

        return [
            'entries' => $entries,
            'availableIds' => $tournament->entrant_kind->modelClass()::query()
                ->whereKey($entries->pluck('competitor_id'))
                ->bookable()
                ->get(['id'])
                ->map(fn (Wrestler|TagTeam $competitor): int => $competitor->id)
                ->all(),
            'titles' => Title::query()
                ->where('promotion_id', $tournament->promotion_id)
                ->orderBy('name')
                ->get(),
            'stipulations' => MatchStipulation::query()
                ->orderBy('name')
                ->get(),
        ];
    }
}
