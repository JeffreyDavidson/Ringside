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
    public static function currentChampionship(Title $title): ?TitleChampionship
    {
        if ($title->relationLoaded('currentChampionship')) {
            return $title->currentChampionship;
        }

        return $title->currentChampionship()->first();
    }

    public static function currentChampion(Title $title): Wrestler|TagTeam|null
    {
        return self::currentChampionship($title)?->champion;
    }

    public static function previousChampionship(Title $title): ?TitleChampionship
    {
        return TitleChampionship::query()
            ->forTitleId($title->id)
            ->previous()
            ->mostRecentlyLostFirst()
            ->first();
    }

    public static function previousChampion(Title $title): Wrestler|TagTeam|null
    {
        return self::previousChampionship($title)?->champion;
    }

    public static function firstChampionship(Title $title): ?TitleChampionship
    {
        return TitleChampionship::query()
            ->forTitleId($title->id)
            ->earliestWonFirst()
            ->first();
    }

    public static function firstChampion(Title $title): Wrestler|TagTeam|null
    {
        return self::firstChampionship($title)?->champion;
    }

    public static function longestChampionship(Title $title, ?Carbon $asOf = null): ?TitleChampionship
    {
        return $title->championships()
            ->get()
            ->sortByDesc(fn (TitleChampionship $championship): int => self::reignLengthInDays($championship, $asOf))
            ->first();
    }

    public static function reignLengthInDays(TitleChampionship $championship, ?Carbon $asOf = null): int
    {
        $reignEnd = $championship->lost_at ?? ($asOf ?? now());

        return (int) $championship->won_at->diffInDays($reignEnd);
    }

    public static function longestChampion(Title $title, ?Carbon $asOf = null): Wrestler|TagTeam|null
    {
        return self::longestChampionship($title, $asOf)?->champion;
    }

    public static function reignCount(Title $title): int
    {
        return $title->championships()->count();
    }

    public static function isVacant(Title $title): bool
    {
        return ! $title->currentChampionship()->exists();
    }
}
