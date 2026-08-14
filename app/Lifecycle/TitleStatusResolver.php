<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Enums\Titles\TitleStatus;

final class TitleStatusResolver
{
    public static function resolve(
        bool $isCurrentlyActive,
        bool $hasFutureActivity,
        bool $hasActivityHistory,
    ): TitleStatus {
        return match (true) {
            $isCurrentlyActive => TitleStatus::Active,
            $hasFutureActivity => TitleStatus::PendingDebut,
            $hasActivityHistory => TitleStatus::Inactive,
            default => TitleStatus::Undebuted,
        };
    }
}
