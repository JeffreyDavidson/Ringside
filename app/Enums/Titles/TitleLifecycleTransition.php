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

    public function ability(): string
    {
        return match ($this) {
            self::Debut => 'debut',
            self::Pull => 'pull',
            self::Reinstate => 'reinstate',
            self::Retire => 'retire',
            self::Unretire => 'unretire',
        };
    }
}
