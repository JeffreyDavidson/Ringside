<?php

declare(strict_types=1);

namespace App\Enums\Tournaments;

enum TournamentOperationTarget: string
{
    case Node = 'node';
    case Entry = 'entry';
    case Tournament = 'tournament';
}
