<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\CanBeChampion;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @phpstan-require-implements CanBeChampion<$this>
 */
trait HasChampionshipReigns
{
    /** @return MorphMany<TitleChampionship, $this> */
    public function titleChampionships(): MorphMany
    {
        return $this->morphMany(TitleChampionship::class, 'champion');
    }

    /** @return MorphMany<TitleChampionship, $this> */
    public function currentChampionships(): MorphMany
    {
        return $this->titleChampionships()->whereNull('lost_at');
    }

    /** @return MorphMany<TitleChampionship, $this> */
    public function previousTitleChampionships(): MorphMany
    {
        return $this->titleChampionships()->whereNotNull('lost_at');
    }
}
