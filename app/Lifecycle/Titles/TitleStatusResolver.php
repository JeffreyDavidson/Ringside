<?php

declare(strict_types=1);

namespace App\Lifecycle\Titles;

use App\Enums\Titles\TitleStatus;

final class TitleStatusResolver
{
    public static function resolve(
        bool $isRetired,
        bool $isCurrentlyActive,
        bool $hasFutureActivity,
        bool $hasActivityHistory,
    ): TitleStatus {
        return match (true) {
            $isRetired => TitleStatus::Retired,
            $isCurrentlyActive => TitleStatus::Active,
            $hasFutureActivity => TitleStatus::PendingDebut,
            $hasActivityHistory => TitleStatus::Inactive,
            default => TitleStatus::Undebuted,
        };
    }
}
