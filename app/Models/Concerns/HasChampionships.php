<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Provides championship-related behavior for models that can track title reigns.
 *
 * Intended for use on the Title model to access current, previous, and historical champions.
 */
trait HasChampionships
{
    /**
     * Retrieve all championship reigns for this title, ordered by when they were won.
     *
     * @return HasMany<TitleChampionship, $this>
     */
    public function championships(): HasMany
    {
        return $this->hasMany(TitleChampionship::class)->oldest('won_at');
    }

    /**
     * Get the current active championship as a relationship.
     *
     * @return HasOne<TitleChampionship, $this>
     */
    public function currentChampionship(): HasOne
    {
        return $this->hasOne(TitleChampionship::class)
            ->whereNull('lost_at')
            ->latest('won_at');
    }

    /**
     * Get the current active championship as a model instance.
     */
    public function getCurrentChampionship(): ?TitleChampionship
    {
        return $this->currentChampionship()->first();
    }

    /**
     * Get the model currently holding the title (e.g., Wrestler or TagTeam).
     */
    public function currentChampion(): ?Model
    {
        return $this->getCurrentChampionship()?->champion;
    }

    /**
     * Get the most recent completed championship reign (with a loss date).
     */
    public function previousChampionship(): ?TitleChampionship
    {
        return $this->championships()
            ->whereNotNull('lost_at')
            ->latest('lost_at')
            ->first();
    }

    /**
     * Get the model that previously held the title before the current reign.
     */
    public function previousChampion(): ?Model
    {
        return $this->previousChampionship()?->champion;
    }

    /**
     * Get the earliest recorded title reign.
     */
    public function firstChampionship(): ?TitleChampionship
    {
        return $this->championships()
            ->orderBy('won_at')
            ->first();
    }

    /**
     * Get the first champion to ever win the title.
     */
    public function firstChampion(): ?Model
    {
        return $this->firstChampionship()?->champion;
    }

    /**
     * Get the title reign with the longest duration.
     */
    public function longestChampionship(): ?TitleChampionship
    {
        return $this->championships()
            ->get()
            ->sortByDesc(fn (TitleChampionship $c) => $c->lengthInDays())
            ->first();
    }

    /**
     * Get the model that held the title for the longest reign.
     */
    public function longestChampion(): ?Model
    {
        return $this->longestChampionship()?->champion;
    }

    /**
     * Get the total number of title reigns this title has had.
     */
    public function reignCount(): int
    {
        return $this->championships()->count();
    }

    /**
     * Determine if the title is currently vacant (i.e., has no active champion).
     */
    public function isVacant(): bool
    {
        return $this->currentChampion() === null;
    }
}
