<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

/** Which of the given wrestlers, tag teams or referees can be booked right now. */
class BookableIdsQuery
{
    /**
     * @param  class-string<Wrestler|TagTeam|Referee>  $model
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    public function get(string $model, array $ids): array
    {
        return $model::query()->bookable()->whereKey($ids)->get()
            ->map(fn (Wrestler|TagTeam|Referee $person): int => $person->id)->values()->all();
    }
}
