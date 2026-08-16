<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exceptions\BaseBusinessException;
use App\Services\ErrorMessageMappingService;
use Closure;

trait ExecutesRosterActions
{
    /** @param Closure(): void $action */
    protected function executeRosterAction(
        string $actionName,
        string $entityType,
        Closure $action,
    ): void {
        try {
            $action();

            $this->dispatch("{$entityType}-updated");
            session()->flash('success', __("{$entityType}s.actions.{$actionName}"));
        } catch (BaseBusinessException $exception) {
            session()->flash('error', __($this->mapException($exception, $entityType)));
        }
    }

    private function mapException(BaseBusinessException $exception, string $entityType): string
    {
        return match ($entityType) {
            'wrestler' => ErrorMessageMappingService::mapWrestlerException($exception),
            'manager' => ErrorMessageMappingService::mapManagerException($exception),
            'referee' => ErrorMessageMappingService::mapRefereeException($exception),
            'tag-team' => ErrorMessageMappingService::mapTagTeamException($exception),
            default => "{$entityType}s.errors.general_error",
        };
    }
}
