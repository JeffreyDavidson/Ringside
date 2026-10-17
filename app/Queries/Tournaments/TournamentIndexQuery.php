<?php

declare(strict_types=1);

namespace App\Queries\Tournaments;

use App\Models\Tournaments\Tournament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/** Reads one page of the tournament list with each tournament's entry and recorded result counts. */
final class TournamentIndexQuery
{
    public const int PER_PAGE = 20;

    /** @return LengthAwarePaginator<int, Tournament> */
    public function get(): LengthAwarePaginator
    {
        return Tournament::query()
            ->withCount([
                'entries',
                'links as recorded_results_count' => fn (Builder $links) => $links->whereHas(
                    'match',
                    fn (Builder $match) => $match->whereNotNull('match_finish'),
                ),
            ])
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);
    }
}
