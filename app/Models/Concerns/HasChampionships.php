<?php

namespace App\Models\Concerns;

use App\Models\TitleChampionship;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasChampionships
{
    /**
     * Retrieve the championships for a title.
     */
    public function championships(): HasMany
    {
        return $this->hasMany(TitleChampionship::class)->oldest('won_at');
    }

    /**
     * Retrieve the curren championship for a title.
     */
    public function currentChampionship(): HasOne
    {
        return $this->hasOne(TitleChampionship::class)->whereNull('lost_at')->latestOfMany();
    }

    /**
     * Determine if the title is vacant.
     */
    public function isVacant(): bool
    {
        return $this->currentChampionship?->champion === null;
    }
}
