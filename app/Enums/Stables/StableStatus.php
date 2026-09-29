<?php

declare(strict_types=1);

namespace App\Enums\Stables;

enum StableStatus: string
{
    case Unformed = 'unformed';                // Created but not yet established or populated
    case PendingEstablishment = 'pending_establishment'; // Has enough members but waiting on debut
    case Active = 'active';                   // Currently established and bookable
    case Inactive = 'inactive';               // Previously established, now under threshold
    case Retired = 'retired';                 // Permanently retired from wrestling

    public function label(): string
    {
        return match ($this) {
            self::Unformed => 'Not Yet Formed',
            self::PendingEstablishment => 'Not Yet Established',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Retired => 'Retired',
        };
    }
}
