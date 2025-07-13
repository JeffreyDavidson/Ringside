<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Interface for models that can win and hold championships.
 *
 * @template TChampion of \Illuminate\Database\Eloquent\Model
 */
interface CanBeChampion
{
    /**
     * Get all title reigns (past and present) held by the model.
     *
     * @return MorphMany<TitleChampionship, TChampion>
     */
    public function titleChampionships(): MorphMany;

    /**
     * Get all current championships (not yet lost).
     *
     * @return MorphMany<TitleChampionship, TChampion>
     */
    public function currentChampionships(): MorphMany;

    /**
     * Get the current championship being held.
     *
     * @return MorphOne<TitleChampionship, TChampion>
     */
    public function currentChampionship(): MorphOne;

    /**
     * Get past championships (already lost).
     *
     * @return MorphMany<TitleChampionship, TChampion>
     */
    public function previousTitleChampionships(): MorphMany;

    /**
     * Determine if the model currently holds any title.
     */
    public function isChampion(): bool;
}
