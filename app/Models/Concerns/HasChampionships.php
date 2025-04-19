<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TitleChampionship;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait HasChampionships
{
    /**
     * Retrieve the championships for a title.
     *
     * @return HasMany<TitleChampionship, $this>
     */
    public function championships(): HasMany
    {
        return $this->hasMany(TitleChampionship::class)->oldest('won_at');
    }

    /**
     * Retrieve the current champion for a title.
     *
     * @return MorphTo<Model, $this>
     */
    public function currentChampion(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'champion_type', 'champion_id');
    }

    /**
     * Retrieve the previous champion for a title.
     *
     * @return MorphTo<Model, $this>
     */
    public function previousChampion(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'previous_champion_type', 'previous_champion_id');
    }

    /**
     * Determine if the title is vacant.
     */
    public function isVacant(): bool
    {
        return $this->currentChampionship?->currentChampion === null;
    }
}
