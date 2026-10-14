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
}
