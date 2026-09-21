<?php

declare(strict_types=1);

namespace App\Enums\Titles;

enum TitleLifecycleTransition
{
    case Debut;
    case Pull;
    case Reinstate;
    case Retire;
    case Unretire;
}
