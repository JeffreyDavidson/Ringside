<?php

declare(strict_types=1);

namespace App\Enums\Tournaments;

enum TournamentOperation: string
{
    case Book = 'book';
    case Plan = 'plan';
    case Reset = 'reset';
    case Unbook = 'unbook';
    case Withdraw = 'withdraw';
    case Replace = 'replace';
    case Walkover = 'walkover';
    case Cancel = 'cancel';

    public function label(): string
    {
        return __("tournaments.operations.{$this->value}");
    }

    /** What the operation acts on, which decides the row the panel is opened for. */
    public function target(): TournamentOperationTarget
    {
        return match ($this) {
            self::Book, self::Plan, self::Reset, self::Unbook, self::Walkover => TournamentOperationTarget::Node,
            self::Withdraw, self::Replace => TournamentOperationTarget::Entry,
            self::Cancel => TournamentOperationTarget::Tournament,
        };
    }

    /** Whether the operation needs a written reason and an explicit confirmation. */
    public function needsReason(): bool
    {
        return ! in_array($this, [self::Book, self::Plan], true);
    }
}
