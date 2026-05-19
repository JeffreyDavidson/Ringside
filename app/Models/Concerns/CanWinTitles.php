<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\CanBeChampion;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Enables the model to act as a champion in title championship records.
 *
 * This trait provides functionality for models that can win and hold titles,
 * such as wrestlers and tag teams. It manages both current and historical
 * championship relationships through polymorphic relationships.
 *
 * @template TChampionship of Model The championship model class
 *
 * @phpstan-require-implements CanBeChampion<TChampionship>
 *
 * @example
 * ```php
 * class Wrestler extends Model implements CanBeChampion
 * {
 *     use CanWinTitles;
 * }
 *
 * $wrestler = Wrestler::find(1);
 * $allTitles = $wrestler->titleChampionships;
 * $currentTitles = $wrestler->currentChampionships;
 * ```
 */
trait CanWinTitles
{
    /**
     * Get all title reigns (past and present) held by this model.
     *
     * Returns all championship records associated with this model,
     * regardless of whether they are currently active or have been lost.
     *
     * @return MorphMany<TitleChampionship, TitleChampionship>
     *                                                         A relationship instance for accessing all title championships
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allTitles = $wrestler->titleChampionships;
     * $titleCount = $wrestler->titleChampionships()->count();
     * ```
     */
    public function titleChampionships(): MorphMany
    {
        /** @var MorphMany<TitleChampionship, TitleChampionship> $relation */
        $relation = $this->morphMany(TitleChampionship::class, 'champion');

        return $relation;
    }

    /**
     * Get all currently held championships (i.e. not yet lost).
     *
     * Returns championship records where the 'lost_at' field is null,
     * indicating that the title is still actively held.
     *
     * @return MorphMany<TitleChampionship, TitleChampionship>
     *                                                         A relationship instance for accessing current championships
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentTitles = $wrestler->currentChampionships;
     *
     * if ($wrestler->currentChampionships()->exists()) {
     *     echo "Wrestler is currently a champion";
     * }
     * ```
     */
    public function currentChampionships(): MorphMany
    {
        /** @var MorphMany<TitleChampionship, TitleChampionship> $relation */
        $relation = $this->morphMany(TitleChampionship::class, 'champion')
            ->whereNull('lost_at');

        return $relation;
    }

    /**
     * Get the most recent active title championship (if any).
     *
     * Returns the most recently won championship that is still active
     * (has not been lost). Uses 'won_at' to determine the most recent.
     *
     * @return MorphOne<TitleChampionship, TitleChampionship>
     *                                                        A relationship instance for accessing the current championship
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentTitle = $wrestler->currentChampionship;
     *
     * if ($wrestler->currentChampionship()->exists()) {
     *     echo "Current champion since: " . $wrestler->currentChampionship->won_at;
     * }
     * ```
     */
    public function currentChampionship(): MorphOne
    {
        /** @var MorphOne<TitleChampionship, TitleChampionship> $relation */
        $relation = $this->morphOne(TitleChampionship::class, 'champion')
            ->whereNull('lost_at')
            ->latestOfMany('won_at');

        return $relation;
    }

    /**
     * Get all past title reigns that have been lost.
     *
     * Returns championship records where the 'lost_at' field is not null,
     * indicating that the title has been lost or relinquished.
     *
     * @return MorphMany<TitleChampionship, TitleChampionship>
     *                                                         A relationship instance for accessing previous championships
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $formerTitles = $wrestler->previousTitleChampionships;
     * $titleHistory = $wrestler->previousTitleChampionships()->orderBy('lost_at', 'desc')->get();
     * ```
     */
    public function previousTitleChampionships(): MorphMany
    {
        /** @var MorphMany<TitleChampionship, TitleChampionship> $relation */
        $relation = $this->morphMany(TitleChampionship::class, 'champion')
            ->whereNotNull('lost_at');

        return $relation;
    }

    /**
     * Determine if the model currently holds any title.
     *
     * Checks if there are any active championship records (where 'lost_at' is null).
     * This is a convenience method for quickly checking championship status.
     *
     * @return bool True if the model currently holds any title, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->isChampion()) {
     *     echo "Wrestler is currently a champion";
     * }
     * ```
     */
    public function isChampion(): bool
    {
        return $this->currentChampionships()->exists();
    }
}
