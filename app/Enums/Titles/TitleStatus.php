<?php

declare(strict_types=1);

namespace App\Enums\Titles;

enum TitleStatus: string
{
    case Undebuted = 'undebuted';          // Title exists, but has never debuted
    case PendingDebut = 'pending_debut';   // Scheduled to debut in the future
    case Active = 'active';                // Currently active and defendable
    case Inactive = 'inactive';            // Temporarily out of circulation
    case Retired = 'retired';              // Currently retired from circulation

    public function color(): string
    {
        return match ($this) {
            self::Undebuted => 'bg-gray-500 text-white',
            self::PendingDebut => 'bg-blue-500 text-white',
            self::Active => 'bg-green-600 text-white',
            self::Inactive => 'bg-yellow-500 text-black',
            self::Retired => 'bg-red-600 text-white',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Undebuted => 'Not Yet Debuted',
            self::PendingDebut => 'Schedule to Debut',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Retired => 'Retired',
        };
    }
}
