<?php

declare(strict_types=1);

namespace App\Builders\Tournaments;

use App\Models\Tournaments\TournamentMatchLink;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of TournamentMatchLink
 *
 * @extends Builder<TModel>
 */
class TournamentMatchLinkBuilder extends Builder
{
    public function forMatchId(int $matchId): static
    {
        return $this->where('match_id', $matchId);
    }
}
