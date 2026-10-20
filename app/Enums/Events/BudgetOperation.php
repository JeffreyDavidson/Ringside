<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum BudgetOperation: string
{
    case SaveLine = 'save_line';
    case ArchiveLine = 'archive_line';
    case RecordMovement = 'record_movement';
    case CorrectMovement = 'correct_movement';

    public function requiresReason(): bool
    {
        return in_array($this, [self::ArchiveLine, self::CorrectMovement], true);
    }

    /** Recording a movement always reads the movement form; correcting one only when it is replaced. */
    public function needsMovementInput(bool $replacingMovement): bool
    {
        return $this === self::RecordMovement || ($this === self::CorrectMovement && $replacingMovement);
    }

    public function label(): string
    {
        return __("event-budget.{$this->value}");
    }
}
