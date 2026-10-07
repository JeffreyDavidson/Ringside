<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Models\Contracts\HasActivityPeriods;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class ActivityStatusStateReader
{
    /**
     * Read the relationship-backed facts used by stable and title status resolvers.
     *
     * Query projections produced by `ACTIVITY_STATUS_STATE` are reused when
     * available so status rendering does not introduce additional relationship queries.
     *
     * @return array{isRetired: bool, isCurrentlyActive: bool, hasFutureActivity: bool, hasActivityHistory: bool}
     */
    public static function read(Model $model): array
    {
        if (! $model instanceof HasActivityPeriods || ! $model instanceof Retirable) {
            throw new LogicException('Activity status requires an active, retirable model.');
        }

        return [
            'isRetired' => $model->hasCurrentRetirement(),
            'isCurrentlyActive' => $model->hasCurrentActivityPeriod(),
            'hasFutureActivity' => $model->hasFutureActivityPeriod(),
            'hasActivityHistory' => $model->hasActivityHistory(),
        ];
    }
}
