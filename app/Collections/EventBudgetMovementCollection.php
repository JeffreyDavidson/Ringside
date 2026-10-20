<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\Events\EventBudgetMovement;
use Illuminate\Database\Eloquent\Collection;

/** @extends Collection<int, EventBudgetMovement> */
class EventBudgetMovementCollection extends Collection
{
    public function unvoided(): static
    {
        return $this->filter(fn (EventBudgetMovement $movement): bool => $movement->voided_at === null)->values();
    }

    /**
     * The movements whose source is the given payment, voided ones included; chain unvoided() for those still in force.
     */
    public function refundsOf(EventBudgetMovement|int $payment): static
    {
        $paymentId = $payment instanceof EventBudgetMovement ? $payment->id : $payment;

        return $this->filter(fn (EventBudgetMovement $movement): bool => $movement->source_id === $paymentId)->values();
    }
}
