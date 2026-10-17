<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

/** The wrestlers, or the referees, of a promotion with the given ids, including deleted ones, keyed by id. */
class EventTalentPeopleQuery
{
    /**
     * @param  class-string<Wrestler|Referee>  $model
     * @param  array<int, int>  $ids
     * @return Collection<int|string, Wrestler>|Collection<int|string, Referee>
     */
    public function get(string $model, ?int $promotionId, array $ids): Collection
    {
        return $model::query()->withTrashed()->where('promotion_id', $promotionId)->whereKey($ids)->get()->keyBy('id');
    }
}
