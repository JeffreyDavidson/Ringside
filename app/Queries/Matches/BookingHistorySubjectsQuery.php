<?php

declare(strict_types=1);

namespace App\Queries\Matches;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

/** Reads the selected wrestlers or tag teams of one promotion, deleted ones included, for the booking history. */
final class BookingHistorySubjectsQuery
{
    /**
     * @param  array<int>  $ids
     * @return Collection<int, covariant Wrestler|TagTeam>
     */
    public function get(Wrestler|TagTeam $model, array $ids, int $promotionId): Collection
    {
        return $model::query()
            ->withTrashed()
            ->where('promotion_id', $promotionId)
            ->whereKey($ids)
            ->with('promotion')
            ->get();
    }
}
