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
        return $title->currentChampionship()->first();
    }

    public static function currentChampion(Title $title): ?Model
    {
        return self::currentChampionship($title)?->champion;
    }

    public static function previousChampionship(Title $title): ?TitleChampionship
    {
        return $title->championships()
            ->whereNotNull('lost_at')
            ->reorder()
            ->latest('lost_at')
            ->first();
    }

    public static function previousChampion(Title $title): ?Model
    {
        return self::previousChampionship($title)?->champion;
    }

    public static function firstChampionship(Title $title): ?TitleChampionship
    {
        return $title->championships()->oldest('won_at')->first();
    }

    public static function firstChampion(Title $title): ?Model
    {
        return self::firstChampionship($title)?->champion;
    }

    public static function longestChampionship(Title $title): ?TitleChampionship
    {
        return $title->championships()
            ->get()
            ->sortByDesc(fn (TitleChampionship $championship): int => $championship->lengthInDays())
            ->first();
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
