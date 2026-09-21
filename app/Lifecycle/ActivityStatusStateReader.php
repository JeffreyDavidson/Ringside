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

        $state = LifecycleStateReader::readProjectedBooleans($model, [
            'isRetired' => [
                'attribute' => 'status_current_retirement_exists',
                'fallback' => fn (): bool => $model->currentRetirement()->exists(),
            ],
            'isCurrentlyActive' => [
                'attribute' => 'status_current_activity_period_exists',
                'fallback' => fn (): bool => $model->currentActivityPeriod()->exists(),
            ],
            'hasFutureActivity' => [
                'attribute' => 'status_future_activity_period_exists',
                'fallback' => fn (): bool => $model->futureActivityPeriod()->exists(),
            ],
            'hasActivityHistory' => [
                'attribute' => 'status_activity_periods_exists',
                'fallback' => fn (): bool => $model->activityPeriods()->exists(),
            ],
        ]);

        return [
            'isRetired' => $state['isRetired'],
            'isCurrentlyActive' => $state['isCurrentlyActive'],
            'hasFutureActivity' => $state['hasFutureActivity'],
            'hasActivityHistory' => $state['hasActivityHistory'],
        ];
    }
}
