<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use App\Enums\Stables\StableStatus;
use App\Lifecycle\ActivityStatusStateReader;
use Illuminate\Database\Eloquent\Model;

final class StableStatusResolver
{
    public static function resolveFor(Model $model): StableStatus
    {
        return self::resolve(...ActivityStatusStateReader::read($model));
    }

    public static function resolve(
        bool $isRetired,
        bool $isCurrentlyActive,
        bool $hasFutureActivity,
        bool $hasActivityHistory,
    ): StableStatus {
        return match (true) {
            $isRetired => StableStatus::Retired,
            $isCurrentlyActive => StableStatus::Active,
            $hasFutureActivity => StableStatus::PendingEstablishment,
            $hasActivityHistory => StableStatus::Inactive,
            default => StableStatus::Unformed,
        };
    }
}
