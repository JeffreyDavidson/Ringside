<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Titles\Title;

/**
 * Name-conflict lookups for models that keep their name unique within a promotion.
 */
trait FiltersByNameInPromotion
{
    /**
     * Restrict to records of the promotion that already use the given name; a null promotion matches records
     * without one.
     */
    public function whereNameInPromotion(string $name, ?int $promotionId): static
    {
        return $this->whereName($name)->where('promotion_id', $promotionId);
    }

    /**
     * Restrict to other records that share the given record's name and promotion.
     */
    public function whereNameConflictsWith(Stable|TagTeam|Title $record): static
    {
        return $this->whereNameInPromotion($record->name, $record->promotion_id)
            ->whereKeyNot($record->getKey());
    }
}
