<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\TitleChampionship;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasChampionships
{
    use HasBelongsToOne;

    /**
     * Retrieve the championships for a title.
     *
     * @return HasMany<TitleChampionship>
     */
    public function championships(): HasMany
    {
        return $this->hasMany(TitleChampionship::class)->oldest('won_at');
    }

    /**
     * Retrieve the curren championship for a title.
     *
     * @return HasOne<TitleChampionship>
     */
    public function currentChampionship(): HasOne
    {
        return $this->hasOne(TitleChampionship::class)->whereNull('lost_at')->latestOfMany();
    }

    /**
     * Retrieve the curren championship for a title.
     */
    public function previousChampionship(): BelongsToOne
    {
        return $this->belongsToOne(TitleChampionship::class, 'title_championships')
            ->wherePivotNotNull('lost_at')
            ->orderByPivot('lost_at', 'desc');
    }

    /**
     * Determine if the title is vacant.
     */
    public function isVacant(): bool
    {
        return $this->currentChampionship?->currentChampion === null;
    }
}
