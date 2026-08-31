<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Collection;

/** @extends Collection<int, TitleChampionship> */
class TitleChampionshipCollection extends Collection
{
    public function forTitleId(int $titleId): static
    {
        return $this->where('title_id', $titleId)->values();
    }

    public function active(): static
    {
        return $this->filter(fn (TitleChampionship $reign): bool => $reign->deleted_at === null)->values();
    }
}
