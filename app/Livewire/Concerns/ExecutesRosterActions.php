<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\Roster\RosterEntityType;
use App\Exceptions\BaseBusinessException;
use App\Services\ErrorMessageMappingService;
use Closure;

trait ExecutesRosterActions
{
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
