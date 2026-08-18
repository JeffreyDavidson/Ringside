<?php

declare(strict_types=1);

namespace App\Enums\Roster;

enum RosterLifecycleAction: string
{
    case Employ = 'employ';
    case Heal = 'heal';
    case Injure = 'injure';
    case Reinstate = 'reinstate';
    case Release = 'release';
    case Restore = 'restore';
    case Retire = 'retire';
    case Suspend = 'suspend';
    case Unretire = 'unretire';

    public function ability(): string
    {
        return match ($this) {
            self::Heal => 'clearFromInjury',
            default => $this->value,
        };
    }

    public function successAction(): string
    {
        return match ($this) {
            self::Employ => 'employed',
            self::Heal => 'healed',
            self::Injure => 'injured',
            self::Reinstate => 'reinstated',
            self::Release => 'released',
            self::Restore => 'restored',
            self::Retire => 'retired',
            self::Suspend => 'suspended',
            self::Unretire => 'unretired',
        };
    }
}
