<?php

declare(strict_types=1);

namespace App\Collections;

use App\Enums\Events\BudgetDirection;
use App\Models\Events\EventBudgetLine;
use Illuminate\Database\Eloquent\Collection;

/** @extends Collection<int, EventBudgetLine> */
class EventBudgetLineCollection extends Collection
{
    public function unarchived(): static
    {
        return $this->filter(fn (EventBudgetLine $line): bool => $line->archived_at === null)->values();
    }

    public function archived(): static
    {
        return $this->filter(fn (EventBudgetLine $line): bool => $line->archived_at !== null)->values();
    }

    public function inDirection(BudgetDirection $direction): static
    {
        return $this->filter(fn (EventBudgetLine $line): bool => $line->direction === $direction)->values();
    }
}
