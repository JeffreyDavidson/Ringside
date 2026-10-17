<?php

declare(strict_types=1);

namespace App\Services\Titles;

use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Carbon;

class TitleReignService
{
    public function lengthInDays(TitleChampionship $championship, ?Carbon $asOf = null): int
    {
        $reignEnd = $championship->lost_at ?? ($asOf ?? now());

        return max(0, (int) $championship->won_at->diffInDays($reignEnd));
    }
}
