<?php

declare(strict_types=1);

namespace App\Queries\Titles;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Carbon;

final class TitleChampionshipQuery
{
    public static function currentChampion(Title $title): Wrestler|TagTeam|null
    {
        return self::currentChampionship($title)?->champion;
    }

    public static function reignLengthInDays(TitleChampionship $championship, ?Carbon $asOf = null): int
    {
        $reignEnd = $championship->lost_at ?? ($asOf ?? now());

        return max(0, (int) $championship->won_at->diffInDays($reignEnd));
    }

    private static function currentChampionship(Title $title): ?TitleChampionship
    {
        if ($title->relationLoaded('currentChampionship')) {
            return $title->currentChampionship;
        }

        return $title->currentChampionship()->first();
    }
}
