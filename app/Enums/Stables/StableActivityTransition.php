<?php

declare(strict_types=1);

namespace App\Enums\Stables;

enum StableActivityTransition
{
    case Establish;
    case Disband;
    case Reunite;
}
