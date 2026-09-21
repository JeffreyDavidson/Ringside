<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
     * Get past championships (already lost).
     *
     * @return MorphMany<TitleChampionship, TChampion>
     */
    public function previousTitleChampionships(): MorphMany;
}
