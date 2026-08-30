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
     * Query projections produced by `withActivityStatusState` are reused when
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
            'isRetired' => LifecycleStateReader::readProjectedBoolean($model, 'status_current_retirement_exists', fn (): bool => $model->currentRetirement()->exists()),
            'isCurrentlyActive' => LifecycleStateReader::readProjectedBoolean($model, 'status_current_activity_period_exists', fn (): bool => $model->currentActivityPeriod()->exists()),
            'hasFutureActivity' => LifecycleStateReader::readProjectedBoolean($model, 'status_future_activity_period_exists', fn (): bool => $model->futureActivityPeriod()->exists()),
            'hasActivityHistory' => LifecycleStateReader::readProjectedBoolean($model, 'status_activity_periods_exists', fn (): bool => $model->activityPeriods()->exists()),
        ];
    }
}
