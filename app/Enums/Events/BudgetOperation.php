<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum BudgetOperation: string
{
    case SaveLine = 'save_line';
    case ArchiveLine = 'archive_line';
    case RecordMovement = 'record_movement';
    case CorrectMovement = 'correct_movement';

    public function label(): string
    {
        return __("event-budget.{$this->value}");
    }
}
