<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasChampionships
{
    /** @return HasMany<TitleChampionship, $this> */
    public function championships(): HasMany
    {
        return $this->hasMany(TitleChampionship::class)->oldest('won_at');
    }

    /** @return HasOne<TitleChampionship, $this> */
    public function currentChampionship(): HasOne
    {
        return $this->hasOne(TitleChampionship::class)
            ->whereNull('lost_at')
            ->latest('won_at');
    }
}
