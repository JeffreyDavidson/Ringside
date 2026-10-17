<?php

declare(strict_types=1);

namespace App\Queries\Tournaments;

use App\Models\Tournaments\Tournament;
use Illuminate\Database\Eloquent\Builder;

/** Reads the promotion's tournaments, newest first, with each tournament's entry and recorded result counts. */
final class TournamentIndexQuery
{
    /** @return Builder<Tournament> The caller paginates it. */
    public function get(): Builder
    {
        return Tournament::query()
            ->withCount([
                'entries',
                'links as recorded_results_count' => fn (Builder $links) => $links->whereHas(
                    'match',
                    fn (Builder $match) => $match->whereNotNull('match_finish'),
                ),
            ])
            ->orderByDesc('id');
    }
}
