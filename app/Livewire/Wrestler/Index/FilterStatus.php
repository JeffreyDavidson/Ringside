<?php

declare(strict_types=1);

namespace App\Livewire\Wrestler\Index;

use App\Enums\WrestlerStatus;

enum FilterStatus: string
{
    case All = 'all';
    case Bookable = WrestlerStatus::Bookable->value;
    case Injured = WrestlerStatus::Injured->value;
    case FutureEmployment = WrestlerStatus::FutureEmployment->value;
    case Released = WrestlerStatus::Released->value;
    case Retired = WrestlerStatus::Retired->value;
    case Suspended = WrestlerStatus::Suspended->value;
    case Unemployed = WrestlerStatus::Unemployed->value;

    public function label(): string
    {
        return match ($this) {
            self::All => 'All',
            self::Bookable => WrestlerStatus::Bookable->label(),
            self::Injured => WrestlerStatus::Injured->label(),
            self::FutureEmployment => WrestlerStatus::FutureEmployment->label(),
            self::Released => WrestlerStatus::Released->label(),
            self::Retired => WrestlerStatus::Retired->label(),
            self::Suspended => WrestlerStatus::Suspended->label(),
            self::Unemployed => WrestlerStatus::Unemployed->label(),
        };
    }
}
