<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Support\RosterErrorMessageResolver;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

trait ExecutesRosterActions
{
    use DispatchesActionFeedback;

    /** @param Closure(): void $action */
    protected function executeAuthorizedRosterAction(
        RosterLifecycleAction $lifecycleAction,
        RosterEntityType $entityType,
        Model $model,
        Closure $action,
    ): bool {
        if (! $lifecycleAction->supports($entityType)) {
            throw new InvalidArgumentException("{$lifecycleAction->value} is not a {$entityType->value} lifecycle action.");
        }

        Gate::authorize($lifecycleAction->ability(), $model);

        return $this->executeRosterAction($lifecycleAction->successAction(), $entityType, $action);
    }

    /** @param Closure(): void $action */
    protected function executeRosterAction(
        string $actionName,
        RosterEntityType $entityType,
        Closure $action,
    ): bool {
        try {
            $action();

            $message = __("{$entityType->translationNamespace()}.actions.{$actionName}");

            $this->dispatch("{$entityType->value}-updated");
            $this->dispatchActionSuccess($message);

            return true;
        } catch (BaseBusinessException $exception) {
            $message = __(RosterErrorMessageResolver::translationKey($exception, $entityType));

            $this->dispatchActionFailure($message);

            return false;
        }
    }
}
