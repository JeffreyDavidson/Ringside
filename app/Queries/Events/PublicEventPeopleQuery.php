<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Database\Eloquent\Collection;

/** The wrestlers, or the tag teams, of one promotion with the given ids, read for the public page regardless of a visitor's context. */
class PublicEventPeopleQuery
{
    /**
     * @param  class-string<Wrestler|TagTeam>  $model
     * @param  array<int, int>  $ids
     * @return Collection<int, Wrestler>|Collection<int, TagTeam>
     */
    public function get(string $model, ?int $promotionId, array $ids): Collection
    {
        return $model::query()->withoutGlobalScope(PromotionContextScope::class)->where('promotion_id', $promotionId)->whereKey($ids)->get();
    }
}
