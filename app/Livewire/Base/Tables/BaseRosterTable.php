<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Concerns\ExecutesRosterActions;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends BaseTable<TModel>
 */
abstract class BaseRosterTable extends BaseTable
{
    use ExecutesRosterActions;

    abstract protected function rosterEntityType(): RosterEntityType;

    /** @return TModel */
    abstract protected function findRosterModel(RosterLifecycleAction $lifecycleAction, int $modelId): Model;

    /** @param Closure(TModel): void $action */
    protected function executeRosterLifecycleAction(
        RosterLifecycleAction $lifecycleAction,
        int $modelId,
        Closure $action,
    ): bool {
        $model = $this->findRosterModel($lifecycleAction, $modelId);

        return $this->executeAuthorizedRosterAction(
            $lifecycleAction,
            $this->rosterEntityType(),
            $model,
            fn () => $action($model),
        );
    }
}
