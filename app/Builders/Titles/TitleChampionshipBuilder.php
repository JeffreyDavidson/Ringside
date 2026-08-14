<?php

declare(strict_types=1);

namespace App\Builders\Titles;

use App\Models\TagTeams\TagTeam;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of TitleChampionship
 *
 * @extends Builder<TModel>
 */
class TitleChampionshipBuilder extends Builder
{
    public function current(): static
    {
        return $this->whereNull('lost_at');
    }

    public function previous(): static
    {
        return $this->whereNotNull('lost_at');
    }

    public function forChampion(Wrestler|TagTeam $champion): static
    {
        return $this->whereMorphedTo('champion', $champion);
    }
}
