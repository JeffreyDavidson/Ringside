<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TitleChampionship;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait CanWinTitles
{
    /**
     * Retrieve the titles won by the model.
     *
     * @return MorphMany<TitleChampionship, $this>
     */
    public function titleChampionships(): MorphMany
    {
        return $this->morphMany(TitleChampionship::class, 'champion');
    }

    /**
     * Retrieve the titles won by the model.
     *
     * @return MorphMany<TitleChampionship, $this>
     */
    public function previousTitleChampionships(): MorphMany
    {
        return $this->morphMany(TitleChampionship::class, 'former_champion')
            ->whereNotNull('lost_at');
    }

    /**
     * Retrieve the current championship held by the model.
     *
     * @return MorphOne<TitleChampionship, $this>
     */
    public function currentChampionship(): MorphOne
    {
        return $this->morphOne(TitleChampionship::class, 'new_champion')
            ->whereNull('lost_at');
    }

    /**
     * Retrieve the current championships held by the model.
     *
     * @return MorphMany<TitleChampionship, $this>
     */
    public function currentChampionships(): MorphMany
    {
        return $this->morphMany(TitleChampionship::class, 'new_champion')
            ->whereNull('lost_at');
    }
}
