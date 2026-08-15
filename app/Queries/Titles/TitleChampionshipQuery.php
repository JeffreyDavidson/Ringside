<?php

declare(strict_types=1);

namespace App\Queries\Titles;

use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Model;

final class TitleChampionshipQuery
{
    public static function currentChampionship(Title $title): ?TitleChampionship
    {
        if ($title->relationLoaded('currentChampionship')) {
            return $title->currentChampionship;
        }

        return $title->currentChampionship()->first();
    }

    public static function currentChampion(Title $title): ?Model
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

    public static function previousChampion(Title $title): ?Model
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

    public static function firstChampion(Title $title): ?Model
    {
        return self::firstChampionship($title)?->champion;
    }

    public static function longestChampionship(Title $title): ?TitleChampionship
    {
        return $title->championships()
            ->get()
            ->sortByDesc(self::reignLengthInDays(...))
            ->first();
    }

    public static function reignLengthInDays(TitleChampionship $championship): int
    {
        $reignEnd = $championship->lost_at ?? now();

        return (int) $championship->won_at->diffInDays($reignEnd);
    }

    public static function longestChampion(Title $title): ?Model
    {
        return self::longestChampionship($title)?->champion;
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
