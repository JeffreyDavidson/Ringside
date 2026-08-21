<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Exceptions\BaseBusinessException;
use App\Services\ErrorMessageMappingService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;

trait ExecutesRosterActions
{
    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel
     */
    protected function findRosterModel(
        RosterLifecycleAction $lifecycleAction,
        string $modelClass,
        int $modelId,
    ): Model {
        $model = new $modelClass();

        $query = $model->newQuery();

        if ($lifecycleAction->usesTrashedModel()) {
            $query
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->whereNotNull($model->getDeletedAtColumn());
        }

        return $query->findOrFail($modelId);
    }

    /** @param Closure(): void $action */
    protected function executeAuthorizedRosterAction(
        RosterLifecycleAction $lifecycleAction,
        RosterEntityType $entityType,
        Model $model,
        Closure $action,
    ): void {
        Gate::authorize($lifecycleAction->ability(), $model);

        $this->executeRosterAction($lifecycleAction->successAction(), $entityType, $action);
    }

    /** @param Closure(): void $action */
    protected function executeRosterAction(
        string $actionName,
        RosterEntityType $entityType,
        Closure $action,
    ): void {
        try {
            $action();

            $this->dispatch("{$entityType->value}-updated");
            session()->flash('success', __("{$entityType->translationNamespace()}.actions.{$actionName}"));
        } catch (BaseBusinessException $exception) {
            session()->flash('error', __(ErrorMessageMappingService::map($exception, $entityType)));
        }
    }
}
