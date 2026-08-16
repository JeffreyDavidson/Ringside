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
    public function forTitleId(int $titleId): static
    {
        $this->where('title_id', $titleId);

        return $this;
    }

    public function current(): static
    {
        $this->whereNull('lost_at');

        return $this;
    }

    public function previous(): static
    {
        $this->whereNotNull('lost_at');

        return $this;
    }

    public function forChampion(Wrestler|TagTeam $champion): static
    {
        return $this->whereMorphedTo('champion', $champion);
    }

    public function earliestWonFirst(): static
    {
        $this->orderBy('won_at');

        return $this;
    }

    public function mostRecentlyLostFirst(): static
    {
        $this->orderByDesc('lost_at');

        return $this;
    }

    public function withPreviousChampionshipId(): static
    {
        $this->addSelect([
            'previous_championship_id' => TitleChampionship::query()
                ->from('titles_championships as previous_championships')
                ->select('previous_championships.id')
                ->whereColumn('previous_championships.title_id', 'titles_championships.title_id')
                ->whereColumn('previous_championships.won_at', '<', 'titles_championships.won_at')
                ->orderByDesc('previous_championships.won_at')
                ->limit(1),
        ]);

        return $this;
    }
}
