<?php

declare(strict_types=1);

namespace App\Queries\Titles;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

final class CurrentChampionQuery
{
    public function get(Title $title): Wrestler|TagTeam|null
    {
        return $this->currentChampionship($title)?->champion;
    }

    private function currentChampionship(Title $title): ?TitleChampionship
    {
        if ($title->relationLoaded('currentChampionship')) {
            return $title->currentChampionship;
        }

        return $title->currentChampionship()->first();
    }
}
