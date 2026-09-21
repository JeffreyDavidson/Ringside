<?php

declare(strict_types=1);

namespace App\Lifecycle\Titles;

use App\Enums\Titles\TitleStatus;
use App\Lifecycle\ActivityStatusStateReader;
use Illuminate\Database\Eloquent\Model;

final class TitleStatusResolver
{
    public static function resolveFor(Model $model): TitleStatus
    {
        return self::resolve(...ActivityStatusStateReader::read($model));
    }

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
