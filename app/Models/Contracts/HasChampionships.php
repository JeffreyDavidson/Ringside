<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface HasChampionships
{
    /**
     * Get all title championship reigns for the model.
     *
     * @return HasMany<TitleChampionship, Title>
     */
    public function championships(): HasMany;

    /**
     * Get the current active title championship.
     */
    public function currentChampionship(): ?TitleChampionship;

    /**
     * Get the model (e.g., wrestler or tag team) currently holding the title.
     */
    public function currentChampion(): ?Model;

    /**
     * Get the last completed title championship.
     */
    public function previousChampionship(): ?TitleChampionship;

    /**
     * Get the model that previously held the title.
     */
    public function previousChampion(): ?Model;

    /**
     * Get the first title championship on record.
     */
    public function firstChampionship(): ?TitleChampionship;

    /**
     * Get the model that first held the title.
     */
    public function firstChampion(): ?Model;

    /**
     * Get the title championship with the longest reign (by days).
     */
    public function longestChampionship(): ?TitleChampionship;

    /**
     * Get the model with the longest reign.
     */
    public function longestChampion(): ?Model;

    /**
     * Get the total number of title reigns.
     */
    public function reignCount(): int;

    /**
     * Determine whether the title currently has no champion.
     */
    public function isVacant(): bool;
}
