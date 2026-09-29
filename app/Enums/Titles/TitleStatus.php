<?php

declare(strict_types=1);

namespace App\Enums\Titles;

use App\Support\Enums\ProvidesFilterOptions;

enum TitleStatus: string
{
    use ProvidesFilterOptions;

    case Undebuted = 'undebuted';          // Title exists, but has never debuted
    case PendingDebut = 'pending_debut';   // Scheduled to debut in the future
    case Active = 'active';                // Currently active and defendable
    case Inactive = 'inactive';            // Temporarily out of circulation
    case Retired = 'retired';              // Currently retired from circulation

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
