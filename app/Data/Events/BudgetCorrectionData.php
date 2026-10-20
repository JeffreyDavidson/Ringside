<?php

declare(strict_types=1);

namespace App\Data\Events;

/** A request to void one budget movement, optionally recording a replacement in the same step. */
readonly class BudgetCorrectionData
{
    public function __construct(public int $movementId, public string $reason, public ?BudgetMovementData $replacement = null) {}
}
