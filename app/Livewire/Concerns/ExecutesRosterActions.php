<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\Roster\RosterEntityType;
use App\Exceptions\BaseBusinessException;
use App\Services\ErrorMessageMappingService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait ExecutesRosterActions
{
    /** @param Closure(): void $action */
    protected function executeAuthorizedRosterAction(
        string $ability,
        string $actionName,
        RosterEntityType $entityType,
        Model $model,
        Closure $action,
    ): void {
        Gate::authorize($ability, $model);

        $this->executeRosterAction($actionName, $entityType, $action);
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
